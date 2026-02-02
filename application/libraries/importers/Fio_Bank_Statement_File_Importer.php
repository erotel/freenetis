<?php defined('SYSPATH') or die('No direct script access.');
/*
 * This file is part of open source system FreenetIS
 * and it is released under GPLv3 licence.
 *
 * More info about licence can be found:
 * http://www.gnu.org/licenses/gpl-3.0.html
 *
 * More info about project can be found:
 * http://www.freenetis.org/
 *
 */

/**
 * FIO abstract importer that handles storing of transfers. Subclass add method
 * for handling different input file types and format.
 *
 * @author Ondrej Fibich, Jiri Svitak
 * @since 1.1
 */
abstract class Fio_Bank_Statement_File_Importer extends Bank_Statement_File_Importer
{
	/*
	 * Sets last succesfully transfered transaction.
	 * Download of new transaction will start from this transaction.
	 * Transactions are identified by their transaction codes that are stored
	 * in the bank transfer model.
	 *
	 * @Override
	 */
	protected function before_download(
		Bank_account_Model $bank_account,
		Bank_Account_Settings $settings
	) {
		// get last transaction ID of this bank account that is stored in database
		$bt_model = new Bank_transfer_Model();
		$ltc = $bt_model->get_last_transaction_code_of($bank_account->id);

		if (empty($ltc) || $ltc <= 0) {
			$ltc = 0; // no transaction for this account
		}

		// set a start transaction for downloading of next transactions
		$url = $settings->get_download_base_url() . 'set-last-id/'
			. $settings->api_token . '/' . $ltc . '/';

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		$response = curl_exec($ch);
		$response_error = curl_error($ch);
		$response_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		// done?
		if ($response !== FALSE && $response_http_code < 300) {
			return TRUE;
		}

		// error in downloading
		$m = __('Setting of the last downloaded transaction has failed');
		throw new Exception($m . ' (E' . $response_http_code . ' ' . $response_error . ')');
	}

	/**
	 * Gets parsed data.
	 *
	 * @return array Contains array of transactions
	 */
	protected abstract function get_parsed_transactions();

	/*
	 * @Override
	 */
	protected function store(&$stats = array())
	{
		$statement = new Bank_statement_Model();
		$ba = $this->get_bank_account();
		$user_id = $this->get_user_id();

		try {
			/* header */

			$statement->transaction_start();
			$header = $this->get_header_data();

			// bank statement
			$statement->bank_account_id = $ba->id;
			$statement->user_id = $this->get_user_id();
			$statement->type = $this->get_importer_name();
			if ($header != NULL) {
				$statement->from = $header->dateStart;
				$statement->to = $header->dateEnd;
				$statement->opening_balance = $header->openingBalance;
				$statement->closing_balance = $header->closingBalance;
			}
			$statement->save_throwable();

			/* transactions */

			// preparation of system double-entry accounts
			$suppliers = ORM::factory('account')->get_account_by_attribute(Account_attribute_Model::SUPPLIERS);
			$member_fees = ORM::factory('account')->get_account_by_attribute(Account_attribute_Model::MEMBER_FEES);
			$operating = ORM::factory('account')->get_account_by_attribute(Account_attribute_Model::OPERATING);
			$cash = ORM::factory('account')->get_account_by_attribute(Account_attribute_Model::CASH);

			$account = $ba->get_related_account_by_attribute_id(Account_attribute_Model::BANK);
			$bank_interests = $ba->get_related_account_by_attribute_id(Account_attribute_Model::BANK_INTERESTS);

			// model preparation
			$bt = new Bank_transfer_Model();
			$fee_model = new Fee_Model();

			// statistics preparation
			$stats['unidentified_nr'] = 0;
			$stats['invoices'] = 0;
			$stats['invoices_nr'] = 0;
			$stats['member_fees'] = 0;
			$stats['member_fees_nr'] = 0;
			$stats['interests'] = 0;
			$stats['interests_nr'] = 0;
			$stats['deposits'] = 0;
			$stats['deposits_nr'] = 0;

			// miscellaneous preparation
			$now = date('Y-m-d H:i:s');
			$number = 0;

			// imported transaction codes, to check duplicities
			$transaction_codes = array();

			// saving each bank listing item
			foreach ($this->get_parsed_transactions() as $item) {
				// convert date of transfer to international format
				$datetime = $item['datum'];

				// try to find counter bank account in database
				$counter_ba = ORM::factory('bank_account')->where(array(
					'account_nr'	=> $item['protiucet'],
					'bank_nr'		=> $item['kod_banky']
				))->find();

				// counter bank account does not exist? let's create new one
				if (!$counter_ba->id) {
					$counter_ba->clear();
					$counter_ba->set_logger(FALSE);
					$counter_ba->name = $item['nazev_protiuctu'];
					$counter_ba->account_nr = $item['protiucet'];
					$counter_ba->bank_nr = $item['kod_banky'];
					$counter_ba->member_id = NULL;
					$counter_ba->save_throwable();
				}

				// determining in/out type of transfer
				if ($item['castka'] < 0) {
					// outbound transfer
					// -----------------
					$matched_transfer_id = $this->pvfree_try_mark_outgoing_paid($item); // vrací outgoing_payments.transfer_id
					if ($matched_transfer_id) {

						$db = Database::instance('default');

						// 1) bank_transfers: soft-delete + poznámka
						$db->query(
							"UPDATE bank_transfers
         SET comment = 'Poslano zpět neidentifikovaná platba',
             deleted_at = NOW()
         WHERE transfer_id = ?",
							array($matched_transfer_id)
						);

						// 2) transfers: změň origin_id tak, aby to NEBYL MEMBER_FEES a zmizelo z Neidentifikovaných
						// POZOR: 5 musí být účet, který není MEMBER_FEES.
						$db->query(
							"UPDATE transfers
         SET origin_id = ?,
             text = ?,
             creation_datetime = ?
         WHERE id = ?",
							array(
								5,
								'Přiřazení neidentifikované platby',
								date('Y-m-d H:i:s'),
								$matched_transfer_id
							)
						);

						Log_queue_Model::info(
							"BANK IMPORT: OP matched -> bank_transfers soft-deleted + transfer #{$matched_transfer_id} moved away from MEMBER_FEES"
						);
					}


					// by default we assume, it is "invoice" (this includes all expenses)
					// double-entry transfer
					$transfer_id = Transfer_Model::insert_transfer(
						$account->id,
						$suppliers->id,
						null,
						$counter_ba->member_id,
						$user_id,
						null,
						$datetime,
						$now,
						$item['zprava'],
						abs($item['castka'])
					);
					// bank transfer
					$bt->clear();
					$bt->set_logger(false);
					$bt->origin_id = $ba->id;
					$bt->destination_id = $counter_ba->id;
					$bt->transfer_id = $transfer_id;
					$bt->bank_statement_id = $statement->id;
					$bt->transaction_code = $item['id_pohybu'];
					$bt->number = $number;
					$bt->constant_symbol = $item['ks'];
					$bt->variable_symbol = $item['vs'];
					$bt->specific_symbol = $item['ss'];
					$bt->save();





					// stats
					$stats['invoices'] += abs($item['castka']);
					$stats['invoices_nr']++;
				} else {
					// inbound transfer
					// ----------------

					// interest transfer
					if ($item['typ'] == 'Připsaný úrok') {
						// let's create interest transfer
						$transfer_id = Transfer_Model::insert_transfer(
							$bank_interests->id,
							$account->id,
							null,
							null,
							$user_id,
							null,
							$datetime,
							$now,
							$item['typ'],
							abs($item['castka'])
						);
						$bt->clear();
						$bt->set_logger(false);
						$bt->origin_id = null;
						$bt->destination_id = $ba->id;
						$bt->transfer_id = $transfer_id;
						$bt->bank_statement_id = $statement->id;
						$bt->transaction_code = $item['id_pohybu'];
						$bt->number = $number;
						$bt->save();
						$stats['interests'] += abs($item['castka']);
						$stats['interests_nr']++;
					} elseif ($item['typ'] == 'Vklad pokladnou') {
						$bt_comment = null;
						$member_id = $this->find_member_by_vs($item['vs']);

						$ba = $this->get_bank_account();
						$dst = ($ba && $ba->id) ? trim($ba->account_nr . '/' . $ba->bank_nr) : 'neznámý účet';

						// když VS v DB není -> comment s protiúčtem + cílovým účtem
						if (!$member_id) {
							$origin = trim($counter_ba->account_nr . '/' . $counter_ba->bank_nr);
							$oname  = trim((string)$counter_ba->name);

							$bt_comment = sprintf(
								'Neidentifikovaný převod: VS=%s, na účet %s',
								(string)$item['vs'],
								$dst
							);
						}

						// pak tvoje kontrola "správný účet pro type2/type90"
						$member_id = $this->pvfree_filter_member_by_bank_account($member_id, $bt_comment);


						if (!$member_id) {
							$stats['unidentified_nr']++;
						}

						// double-entry incoming transfer
						$transfer_id = Transfer_Model::insert_transfer(
							$member_fees->id,
							$account->id,
							null,
							$member_id,
							$user_id,
							null,
							$datetime,
							$now,
							$item['zprava'],
							abs($item['castka'])
						);
						// incoming bank transfer
						$bt->clear();
						$bt->set_logger(false);
						$bt->origin_id = $counter_ba->id;
						$bt->destination_id = $ba->id;
						$bt->transfer_id = $transfer_id;
						$bt->bank_statement_id = $statement->id;
						$bt->transaction_code = $item['id_pohybu'];
						$bt->number = $number;
						$bt->constant_symbol = $item['ks'];
						$bt->variable_symbol = $item['vs'];
						$bt->specific_symbol = $item['ss'];
						if (!empty($bt_comment)) {
							$bt->comment = $bt_comment;
						}
						$bt->save();


						// assign transfer? (0 - invalid id, 1 - assoc id, other are ordinary members)
						if ($member_id && $member_id != Member_Model::ASSOCIATION) {
							$ca = ORM::factory('account')
								->where('member_id', $member_id)
								->find();

							// has credit account?
							if ($ca->id) {
								// add affected member for notification
								$this->add_affected_member($member_id, array(
									'bank_transfer_id' => $bt->id,
									'amount' => $item['castka'],
									'date' => $now
								));

								// assigning transfer
								$a_transfer_id = Transfer_Model::insert_transfer(
									$account->id,
									$ca->id,
									$transfer_id,
									$member_id,
									$user_id,
									null,
									$datetime,
									$now,
									__('Assigning of transfer'),
									abs($item['castka'])
								);

								// transaction fee
								$fee = $fee_model->get_by_date_type(
									$datetime,
									'transfer fee'
								);
								if ($fee && $fee->fee > 0) {
									$tf_transfer_id = Transfer_Model::insert_transfer(
										$ca->id,
										$operating->id,
										$transfer_id,
										$member_id,
										$user_id,
										null,
										$datetime,
										$now,
										__('Transfer fee'),
										$fee->fee
									);
								}
								// do not change owner if there is already
								// one (#800)
								if (!$counter_ba->member_id) {
									$counter_ba->member_id = $member_id;
									$counter_ba->save_throwable();
								}
							}
						}
						// member fee stats
						$stats['member_fees'] += abs($item['castka']);
						$stats['member_fees_nr']++;
					}
					// otherwise we assume that it is member fee
					else {
						// let's identify member
						$bt_comment = null;
						$member_id = $this->find_member_by_vs($item['vs']);

						$ba = $this->get_bank_account();
						$dst = ($ba && $ba->id) ? trim($ba->account_nr . '/' . $ba->bank_nr) : 'neznámý účet';

						// když VS v DB není -> comment s protiúčtem + cílovým účtem
						if (!$member_id) {
							$origin = trim($counter_ba->account_nr . '/' . $counter_ba->bank_nr);
							$oname  = trim((string)$counter_ba->name);

							$bt_comment = sprintf(
								'Neidentifikovaný převod: VS=%s, na účet %s',
								(string)$item['vs'],
								$dst
							);
						}

						// pak tvoje kontrola "správný účet pro type2/type90"
						$member_id = $this->pvfree_filter_member_by_bank_account($member_id, $bt_comment);


						if (!$member_id) {
							$stats['unidentified_nr']++;
						}

						// double-entry incoming transfer
						$transfer_id = Transfer_Model::insert_transfer(
							$member_fees->id,
							$account->id,
							null,
							$member_id,
							$user_id,
							null,
							$datetime,
							$now,
							$item['zprava'],
							abs($item['castka'])
						);
						// incoming bank transfer
						$bt->clear();
						$bt->set_logger(false);
						$bt->origin_id = $counter_ba->id;
						$bt->destination_id = $ba->id;
						$bt->transfer_id = $transfer_id;
						$bt->bank_statement_id = $statement->id;
						$bt->transaction_code = $item['id_pohybu'];
						$bt->number = $number;
						$bt->constant_symbol = $item['ks'];
						$bt->variable_symbol = $item['vs'];
						$bt->specific_symbol = $item['ss'];
						if (!empty($bt_comment)) {
							$bt->comment = $bt_comment;
						}
						$bt->save();

						// assign transfer? (0 - invalid id, 1 - assoc id, other are ordinary members)
						if ($member_id && $member_id != Member_Model::ASSOCIATION) {
							$ca = ORM::factory('account')
								->where('member_id', $member_id)
								->find();

							// has credit account?
							if ($ca->id) {
								// add affected member for notification
								$this->add_affected_member($member_id, array(
									'bank_transfer_id' => $bt->id,
									'amount' => $item['castka'],
									'date' => $now
								));

								// assigning transfer
								$a_transfer_id = Transfer_Model::insert_transfer(
									$account->id,
									$ca->id,
									$transfer_id,
									$member_id,
									$user_id,
									null,
									$datetime,
									$now,
									__('Assigning of transfer'),
									abs($item['castka'])
								);

								// transaction fee
								$fee = $fee_model->get_by_date_type(
									$datetime,
									'transfer fee'
								);
								if ($fee && $fee->fee > 0) {
									$tf_transfer_id = Transfer_Model::insert_transfer(
										$ca->id,
										$operating->id,
										$transfer_id,
										$member_id,
										$user_id,
										null,
										$datetime,
										$now,
										__('Transfer fee'),
										$fee->fee
									);
								}
								// do not change owner if there is already
								// one (#800)
								if (!$counter_ba->member_id) {
									$counter_ba->member_id = $member_id;
									$counter_ba->save_throwable();
								}
							}
						}
						// member fee stats
						$stats['member_fees'] += abs($item['castka']);
						$stats['member_fees_nr']++;
					}
				}

				// add item transaction code to array to check duplicities later
				$transaction_codes[] = $item['id_pohybu'];

				// line number increase
				$number++;
			}

			// let's check duplicities
			$duplicities = $bt->get_transaction_code_duplicities($transaction_codes, $ba->id);

			if (count($duplicities) > count($transaction_codes)) {
				$dm = __('Duplicate transaction codes') . ': ' . implode(', ', $duplicities);
				throw new Duplicity_Exception($dm);
			}

			// done
			$statement->transaction_commit();

			// return
			return $statement;
		} catch (Duplicity_Exception $e) {
			$statement->transaction_rollback();

			throw $e;
		} catch (Exception $e) {
			$statement->transaction_rollback();
			Log::add_exception($e);
			$this->add_exception_error($e);
			return NULL;
		}
	}

	protected function pvfree_filter_member_by_bank_account(&$member_id, &$bt_comment = null)
	{
		if (!$member_id) return NULL;

		$ba = $this->get_bank_account();
		if (!$ba || !$ba->id) return $member_id;

		$expected_services_ba_id = 6160;   // internet
		$expected_members_ba_id  = 10765;  // členové

		$m = new Member_Model((int)$member_id);
		$type = (int)$m->type;

		$expected_ba_id = NULL;
		if ($type === 2)  $expected_ba_id = $expected_services_ba_id;
		if ($type === 90) $expected_ba_id = $expected_members_ba_id;

		if ($expected_ba_id !== NULL && (int)$ba->id !== (int)$expected_ba_id) {

			$bt_comment = sprintf(
				'Platba patří %s (member_id=%d), ale přišla na špatný účet (%s).',
				($type === 2 ? 'zákazníkovi / faktura' : 'členovi / členské'),
				(int)$member_id,
				$ba->account_nr . '/' . $ba->bank_nr
			);

			Log_queue_Model::info('BANK IMPORT: ' . $bt_comment);

			return NULL;
		}

		return $member_id;
	}

	/**
	 * Match odchozí bankovní položky (castka < 0) na outgoing_payments podle OP #ID ve zprávě.
	 * Po úspěchu nastaví outgoing_payments.status='paid' a vrátí outgoing_payments.transfer_id,
	 * aby šlo uklidit bank_transfers podle transfer_id.
	 */
	protected function pvfree_try_mark_outgoing_paid(array $item): ?int
	{
		// jen odchozí položky (výdaj z účtu)
		if (!isset($item['castka']) || (float)$item['castka'] >= 0) {
			return NULL;
		}

		$msg = isset($item['zprava']) ? trim((string)$item['zprava']) : '';
		if ($msg === '') return NULL;

		if (!preg_match('~\bOP\s*#\s*([0-9]+)\b~i', $msg, $m)) {
			return NULL;
		}

		$op_id = (int)$m[1];
		if ($op_id <= 0) return NULL;

		$ba = $this->get_bank_account();
		if (!$ba || !$ba->id) return NULL;

		$amount = (float)abs((float)$item['castka']);
		$now = date('Y-m-d H:i:s');

		$db = Database::instance('default');

		// načti outgoing_payment včetně transfer_id
		$res = $db->query(
			"SELECT id, bank_account_id, status, amount, transfer_id
         FROM outgoing_payments
         WHERE id=?",
			array($op_id)
		);
		$rows = method_exists($res, 'result') ? $res->result() : array();
		$op = $rows[0] ?? NULL;
		if (!$op) return NULL;

		// transfer_id musí existovat, jinak není podle čeho uklízet bank_transfers
		if (empty($op->transfer_id) || (int)$op->transfer_id <= 0) {
			Log_queue_Model::info("BANK IMPORT: OP #$op_id matched, but outgoing_payments.transfer_id is empty");
			return NULL;
		}

		// musí sedět účet
		if ((int)$op->bank_account_id !== (int)$ba->id) return NULL;

		// musí sedět částka (tolerance 0.01)
		if (abs(((float)$op->amount) - $amount) > 0.01) {
			Log_queue_Model::info(sprintf(
				"BANK IMPORT: OP #%d amount mismatch (statement=%.2f, outgoing=%.2f, ba_id=%d)",
				$op_id,
				$amount,
				(float)$op->amount,
				(int)$ba->id
			));
			return NULL;
		}

		// povolené stavy
		if (!in_array((string)$op->status, array('exported', 'approved'), true)) return NULL;

		// update (idempotentní)
		$db->query(
			"UPDATE outgoing_payments
         SET status='paid', paid_at=?, updated_at=?
         WHERE id=? AND status IN ('exported','approved')",
			array($now, $now, $op_id)
		);

		Log_queue_Model::info(sprintf(
			"BANK IMPORT: OP #%d auto-marked as PAID (amount=%.2f, ba_id=%d, transfer_id=%d)",
			$op_id,
			$amount,
			(int)$ba->id,
			(int)$op->transfer_id
		));

		return (int)$op->transfer_id;
	}
}
