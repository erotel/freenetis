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
 * CSRF protection helper.
 *
 * Generates a per-session token, injects it into POST forms via form::open(),
 * and provides a check() method for controllers that do not use Forge.
 *
 * Usage in a non-Forge controller POST handler:
 *   if (!Csrf::check()) { status::error('Invalid request.'); return; }
 *
 * Usage in a raw <form> view:
 *   <?php echo Csrf::field() ?>
 *
 * @package Libraries
 */
class Csrf
{
	const TOKEN_NAME  = 'csrf_token';
	const TOKEN_BYTES = 32;

	/**
	 * Returns the current session CSRF token, generating one if it does not
	 * exist yet.
	 *
	 * @return string  64-character hex token
	 */
	public static function token()
	{
		$session = Session::instance();
		$token   = $session->get(self::TOKEN_NAME, '');

		if ($token === '')
		{
			$token = bin2hex(random_bytes(self::TOKEN_BYTES));
			$session->set(self::TOKEN_NAME, $token);
		}

		return $token;
	}

	/**
	 * Validates the CSRF token submitted via POST against the session token.
	 *
	 * @return bool  TRUE if valid, FALSE otherwise
	 */
	public static function check()
	{
		$session   = Session::instance();
		$stored    = $session->get(self::TOKEN_NAME, '');
		$submitted = array_key_exists(self::TOKEN_NAME, $_POST)
			? (string) $_POST[self::TOKEN_NAME]
			: '';

		return $stored !== '' && hash_equals($stored, $submitted);
	}

	/**
	 * Returns an HTML hidden input tag for use in raw <form> templates.
	 *
	 * @return string
	 */
	public static function field()
	{
		return '<input type="hidden" name="' . self::TOKEN_NAME
			. '" value="' . html::specialchars(self::token()) . '" />' . "\n";
	}
}
