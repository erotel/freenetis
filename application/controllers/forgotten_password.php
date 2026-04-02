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
 * Controller enables generate new password for user by user.
 * Generated password is sended to user by email.
 * 
 * @package	Controller
 */
class Forgotten_password_Controller extends Controller
{
	/**
	 * Interface for getting forgotten password
	 */
	public function index()
	{
		if (!Settings::get('forgotten_password') || $this->session->get('user_id', 0)) {
			url::redirect('login');
		}

		if ($this->input->get('request')) {
			self::change_password($this->input->get('request'));
			exit();
		}

		$message = __('New password into the information system can be obtained via e-mail') . '.<br />';
		$message .= __('Please insert username, e-mail or variable symbol which is assigned to your account') . '.';
		$message_error = NULL;

		$form = new Forge();

		$form->input('data')
			->label('Username, e-mail or variable symbol')
			->rules('required');

		// submit button
		$form->submit('Send');

		$form_html = $form->html();

		if ($form->validate()) {
			$form_data = $form->as_array();

			$user = new User_Model();
			$user_contact = new Users_contacts_Model();
			$contact = new Contact_Model();

			$input = trim($form_data['data']);

			if (valid::email($input)) {
				$contact->where(array(
					'type'  => Contact_Model::TYPE_EMAIL,
					'value' => $input
				))->find();

				if ($contact->id) {
					$user_id = $user_contact->get_user_of_contact($contact->id);

					if ($user_id) {
						$user->find($user_id);
					}
				}
			} else {
				// 1) zkus login
				$user->where('login', $input)->find();

				// 2) když login nenalezen, zkus variabilní symbol
				if (!$user->id && ctype_digit($input)) {
					$db = Database::instance();

					$row = $db->query(
						"SELECT u.id
			   FROM variable_symbols vs
			   JOIN accounts a ON a.id = vs.account_id
			   JOIN members m ON m.id = a.member_id
			   JOIN users u ON u.member_id = m.id
			  WHERE vs.variable_symbol = ?
			  LIMIT 1",
						array($input)
					)->current();

					if ($row && !empty($row->id)) {
						$user->find((int) $row->id);
					}
				}
			}

			// if login was not found
			if (!$user->id) {
				$message_error = __('Login or e-mail do not match with data in information system') . '. ';
				$message_error .= __('Please contact support.') . '.';
			}
			// if user has no e-mail addresses
			else if (!$contact->count_all_users_contacts($user->id, Contact_Model::TYPE_EMAIL)) {
				$message_error = __('There is no e-mail filled in your account') . '. ';
				$message_error .= __('Please contact support.') . '.';
			} else {
				// e-mail address
				if ($contact->id) {
					$to = array($contact->value);
				} else {
					$to = array();
					$contacts = $contact->find_all_users_contacts($user->id, Contact_Model::TYPE_EMAIL);

					foreach ($contacts as $c) {
						$to[] = $c->value;
					}
				}

				// save request string
				$hash = text::random('numeric', 10);
				$user->password_request = $hash;
				$user->save();

				// From, subject and HTML message
				$from = Settings::get('email_default_email');
				$subject = Settings::get('title') . ' - ' . __('Forgotten password');

				$e_message = '<html><body>';
				$e_message .= __('Hello') . ' ';
				$e_message .= $user->get_full_name() . ',<br /><br />';
				$e_message .= __(
					'Someone from the IP address %s, probably you, requested to change the password for account with login %s',
					array(server::remote_addr(), '<b>' . $user->login . '</b>')
				) . '. ';
				$e_message .= __('New password can be changed at the following link') . ':<br /><br />';
				$e_message .= html::anchor('forgotten_password?request=' . $hash);
				$e_message .= '<br /><br />' . url_lang::lang('mail.welcome') . '<br />';
				$e_message .= '</body></html>';

				$sended = TRUE;
				$mailer = new Mailer_Wrapper();

				foreach ($to as $email) {
					try {
						$mailer->sendHtml($from, $email, $subject, $e_message);
					} catch (Exception $e) {
						Log::add_exception($e);
						$sended = FALSE;
					}
				}

				if ($sended) {
					$message = '<b>' . __('The request has been sent to your e-mail') . ' (';
					$message .= implode(', ', $to) . ').</b><br />';
					$message .= __('Please check your e-mail box') . '. ';
					$message .= __('If message does not arrive in 20 minutes, please contact support') . '.';
				} else {
					$message_error = __('Sending message failed. Please contact support.');
				}

				$form_html = '';
			}
		}

		$view = new View('forgotten_password/index');
		$view->title = __('Forgotten password');
		$view->message = $message . ($message_error ? '<br /><br /><b class="error">' . $message_error . '</b>' : '');
		$view->form = $form_html;
		$view->render(TRUE);
	}

	/**
	 * Method shows form dialog for password change.
	 * 
	 * @param string $hash
	 */
	private function change_password($hash)
	{
		$user = ORM::factory('user')->where('password_request', $hash)->find();

		if (!$user->id) {
			$view = new View('forgotten_password/index');
			$view->title = __('Forgotten password');
			$view->message = __('Reguest is invalid or expired') . '.';
			$view->form = null;
			$view->render(TRUE);
		} else {
			$pass_min_len = Settings::get('security_password_length');

			$form = new Forge('forgotten_password?request=' . htmlspecialchars($hash));

			$form->password('password')
				->label(__('New password') . ':&nbsp;' . help::hint('password'))
				->rules('required|length[' . $pass_min_len . ',50]')
				->class('main_password');

			$form->password('confirm_password')
				->label('Confirm new password')
				->rules('required|length[' . $pass_min_len . ',50]')
				->matches($form->password);

			// submit button
			$form->submit('Send');

			$message = __('Enter new password please') . '.';

			if ($form->validate()) {
				$form_data = $form->as_array(FALSE);

				$user->password = password_hash($form_data['password'], PASSWORD_BCRYPT);
				$user->password_request = null;
				$user->save();

				$view = new View('forgotten_password/index');
				$view->title = __('Forgotten password');
				$view->message = '<b>' . __('Password has been successfully changed.') . '</b>';
				$view->form = null;
				$view->render(TRUE);
			} else {
				$view = new View('forgotten_password/index');
				$view->title = __('Forgotten password');
				$view->message = $message;
				$view->form = $form->html();
				$view->render(TRUE);
			}
		}
	}
}
