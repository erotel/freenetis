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
 * SMS driver for ARTIO SMS Services HTTP API.
 *
 * Based on provided ARTIO HTTP API documentation.
 */
class Sms_Artio extends Sms
{
  /**
   * Test state of driver?
   *
   * @var bool
   */
  private $test = FALSE;

  /**
   * Error report or FALSE if there is no error.
   *
   * @var mixed
   */
  private $error = FALSE;

  /**
   * Status of message or FALSE if there is no sent message.
   *
   * @var mixed
   */
  private $status = FALSE;

  /**
   * Last decoded API response
   *
   * @var array|null
   */
  private $last_response = NULL;

  /**
   * Default API URL from ARTIO docs.
   *
   * @var string
   */
  protected $hostname = 'http://www.artio.net/index.php?option=com_artiosms&controller=api';

  /**
   * Construct cannot be called from outside
   */
  protected function __construct() {}

  /**
   * Sets test mode.
   *
   * @param bool $test
   */
  public function set_test($test)
  {
    $this->test = ($test === TRUE);
  }

  /**
   * Test if connection to server is OK
   *
   * @return bool
   */
  public function test_conn()
  {
    $response = $this->api_call(array(
      'task'     => 'get_credit_info',
      'username' => $this->user,
      'api_key'  => $this->password,
    ));

    if ($response === FALSE) {
      return FALSE;
    }

    if (!empty($response['success'])) {
      $credit = isset($response['credit']) ? $response['credit'] : '?';
      $this->status = 'Connection OK, credit: ' . $credit;
      $this->error = FALSE;
      return TRUE;
    }

    $this->status = FALSE;
    $this->error = $this->extract_error_message($response);

    return FALSE;
  }

  /**
   * Try to send SMS message.
   *
   * @param string $sender
   * @param string $recipient
   * @param string $message
   * @return boolean
   */
  public function send($sender, $recipient, $message)
  {
    $recipient = preg_replace('/\D+/', '', (string) $recipient);

    if ($recipient === '') {
      $this->status = FALSE;
      $this->error = 'Wrong phone number of receiver';
      return FALSE;
    }

    $params = array(
      'task'         => 'send_sms',
      'username'     => $this->user,
      'api_key'      => $this->password,
      'to'           => $recipient,
      'text'         => $message,
      'allowUnicode' => 0,
      'src'          => 'FreenetIS',
    );

    $response = $this->api_call($params);

    if ($response === FALSE) {
      return FALSE;
    }

    if (!empty($response['success'])) {
      $this->error = FALSE;
      $this->status = 'SMS message sent';
      return TRUE;
    }

    $this->status = FALSE;
    $this->error = $this->extract_error_message($response);

    return FALSE;
  }

  /**
   * Try to receive SMS messages.
   * ARTIO docs provided by user do not describe inbound SMS receiving.
   *
   * @return boolean
   */
  public function receive()
  {
    return FALSE;
  }

  /**
   * Gets received messages after receive
   *
   * @return array
   */
  public function get_received_messages()
  {
    return array();
  }

  /**
   * Gets error report
   *
   * @return mixed
   */
  public function get_error()
  {
    return $this->error;
  }

  /**
   * Gets state of message
   *
   * @return mixed
   */
  public function get_status()
  {
    return $this->status;
  }

  /**
   * Performs HTTP API call and decodes JSON response.
   *
   * @param array $params
   * @return array|false
   */
  protected function api_call($params)
  {
    $this->last_response = NULL;

    if (empty($this->hostname)) {
      $this->hostname = 'http://www.artio.net/index.php?option=com_artiosms&controller=api';
    }

    $post = http_build_query($params);

    $ch = curl_init($this->hostname);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
    curl_setopt($ch, CURLOPT_VERBOSE, FALSE);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $out = curl_exec($ch);

    if ($out === FALSE) {
      $this->status = FALSE;
      $this->error = 'cURL error: ' . curl_error($ch);
      curl_close($ch);
      return FALSE;
    }

    $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code < 200 || $http_code >= 300) {
      $this->status = FALSE;
      $this->error = 'HTTP error ' . $http_code;
      return FALSE;
    }

    $response = json_decode($out, TRUE);

    if (!is_array($response)) {
      $this->status = FALSE;
      $this->error = 'Invalid JSON response';
      return FALSE;
    }

    $this->last_response = $response;
    return $response;
  }

  /**
   * Maps ARTIO error response to readable text.
   *
   * @param array $response
   * @return string
   */
  protected function extract_error_message($response)
  {
    if (isset($response['msg']) && $response['msg'] !== '') {
      return $response['msg'];
    }

    $err = isset($response['err']) ? (int) $response['err'] : 0;

    switch ($err) {
      case 1:
        return 'Invalid username or API key';
      case 2:
        return 'SMS Services account is disabled';
      case 3:
        return 'SMS Services account is paused';
      case 4:
        return 'Missing or invalid parameters';
      case 5:
        return 'SMS could not be sent';
      default:
        return 'Unknown API error';
    }
  }
}
