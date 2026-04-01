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
 * SMS driver for SmsManager JSON API v2.
 */
class Sms_Smsmanager extends Sms
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
   * Default API base URL.
   *
   * @var string
   */
  protected $hostname = 'https://api.smsmngr.com/v2';

  /**
   * Construct cannot be called from outside
   */
  protected function __construct() {}

  /**
   * Sets test mode.
   * SmsManager docs page used here does not show a test flag on /message,
   * so this is stored only for compatibility with FreenetIS.
   *
   * @param bool $test
   */
  public function set_test($test)
  {
    $this->test = ($test === TRUE);
  }

  /**
   * Test if connection to server is OK.
   *
   * We do a lightweight authenticated request to /message with intentionally
   * invalid payload and expect JSON response instead of transport/auth failure.
   *
   * @return bool
   */
  public function test_conn()
  {
    $response = $this->api_post('/message', array(
      'body' => 'test',
      'to'   => array(),
    ), TRUE);

    if ($response === FALSE) {
      return FALSE;
    }

    $this->error = FALSE;
    $this->status = 'Connection OK';
    return TRUE;
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

    $payload = array(
      'body' => (string) $message,
      'to'   => array(
        array(
          'phone_number' => $recipient
        )
      ),
      'tag'  => 'transactional',
    );

    $response = $this->api_post('/message', $payload);

    if ($response === FALSE) {
      return FALSE;
    }

    $accepted = isset($response['accepted']) && is_array($response['accepted'])
      ? count($response['accepted'])
      : 0;

    $rejected = isset($response['rejected']) && is_array($response['rejected'])
      ? count($response['rejected'])
      : 0;

    if ($accepted > 0) {
      $request_id = isset($response['request_id']) ? $response['request_id'] : '';
      $this->error = FALSE;
      $this->status = 'Accepted by SmsManager'
        . ($request_id ? ', request_id: ' . $request_id : '')
        . ', accepted: ' . $accepted
        . ', rejected: ' . $rejected;
      return TRUE;
    }

    $this->status = FALSE;
    $this->error = $this->extract_error_message($response);

    return FALSE;
  }

  /**
   * Try to receive SMS messages.
   * This driver does not implement inbound SMS polling.
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
   * SmsManager uses x-api-key auth. FreenetIS passes generic "user",
   * but this driver does not need it.
   *
   * @param string $user
   */
  public function set_user($user)
  {
    $this->user = $user;
  }

  /**
   * Performs authenticated JSON POST request.
   *
   * @param string $path
   * @param array $payload
   * @param bool $accept_client_error_as_success Used by test_conn()
   * @return array|false
   */
  protected function api_post($path, $payload, $accept_client_error_as_success = FALSE)
  {
    $this->last_response = NULL;

    $base = rtrim((string) $this->hostname, '/');
    if ($base === '') {
      $base = 'https://api.smsmngr.com/v2';
    }

    $url = $base . $path;
    $json = json_encode($payload);

    if ($json === FALSE) {
      $this->status = FALSE;
      $this->error = 'JSON encode error';
      return FALSE;
    }

    $headers = array(
      'Content-Type: application/json',
      'Accept: application/json',
      'x-api-key: ' . $this->password,
    );

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
    curl_setopt($ch, CURLOPT_VERBOSE, FALSE);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $out = curl_exec($ch);

    if ($out === FALSE) {
      $this->status = FALSE;
      $this->error = 'cURL error: ' . curl_error($ch);
      curl_close($ch);
      return FALSE;
    }

    $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $response = json_decode($out, TRUE);

    if (!is_array($response)) {
      if ($http_code >= 200 && $http_code < 300) {
        $this->status = FALSE;
        $this->error = 'Invalid JSON response';
        return FALSE;
      }

      $this->status = FALSE;
      $this->error = 'HTTP error ' . $http_code;
      return FALSE;
    }

    $this->last_response = $response;

    if ($http_code >= 200 && $http_code < 300) {
      return $response;
    }

    if ($accept_client_error_as_success && $http_code >= 400 && $http_code < 500) {
      return $response;
    }

    $this->status = FALSE;
    $this->error = $this->extract_error_message($response);

    if (!$this->error) {
      $this->error = 'HTTP error ' . $http_code;
    }

    return FALSE;
  }

  /**
   * Extracts readable error message from API response.
   *
   * @param array $response
   * @return string
   */
  protected function extract_error_message($response)
  {
    if (isset($response['message']) && is_string($response['message']) && $response['message'] !== '') {
      return $response['message'];
    }

    if (isset($response['error']) && is_string($response['error']) && $response['error'] !== '') {
      return $response['error'];
    }

    if (isset($response['errors']) && is_array($response['errors']) && count($response['errors'])) {
      $first = reset($response['errors']);

      if (is_string($first) && $first !== '') {
        return $first;
      }

      if (is_array($first)) {
        if (isset($first['message']) && is_string($first['message']) && $first['message'] !== '') {
          return $first['message'];
        }
        if (isset($first['error']) && is_string($first['error']) && $first['error'] !== '') {
          return $first['error'];
        }
      }
    }

    if (isset($response['rejected']) && is_array($response['rejected']) && count($response['rejected'])) {
      $first = reset($response['rejected']);

      if (is_array($first)) {
        if (isset($first['reason']) && is_string($first['reason']) && $first['reason'] !== '') {
          return $first['reason'];
        }
        if (isset($first['message']) && is_string($first['message']) && $first['message'] !== '') {
          return $first['message'];
        }
      }

      return 'Message rejected';
    }

    return 'Unknown API error';
  }
}
