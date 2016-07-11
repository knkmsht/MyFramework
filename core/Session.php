<?php
class Session {
	function __construct() {}
	
	static function __start() {
		static $pass = true;
		
		if (session_status() === PHP_SESSION_NONE) {
			if (!$pass) {
				ini_set('session.use_only_cookies', false);
				ini_set('session.use_cookies', false);
				ini_set('session.use_trans_sid', false);//May be necessary in some situations, 這項當 session 在 active 中會導致 warning
				ini_set('session.cache_limiter', null);
			} else {
				$pass = false;
			}
			session_start();
		}
	}
	
	static function __end() {
		session_write_close();//會將 session_status 改為 PHP_SESSION_NONE
	}
	
	static function delete($key) {
		self::__start();
		
		if (is_array($key)) {
			foreach ($key as $v0) {
				unset($_SESSION[$v0]);
			}
		} else {
			unset($_SESSION[$key]);
		}
		
		self::__end();
	}
	
	static function get($key=null) {
		self::__start();//start 過後, $_SESSION 才會同步 server 上的 session data
		
		if ($key === null) {
			$return = $_SESSION;
		} else {
			$return = isset($_SESSION[$key])? $_SESSION[$key] : null;
		}
		
		self::__end();
		
		return $return;
	}
	
	static function getID() {
		self::__start();//start 過後, 才會有 session id
		
		$session_id = session_id();
		
		self::__end();
		
		return $session_id;
	}
	
	static function set($key, $value) {
		self::__start();
		
		$_SESSION[$key] = $value;
		
		self::__end();
		
		return $value;
	}
}