<?php
namespace Core;
class Lang {
	public static $lang;
	public static $support;
	public static $default = 'zh_TW';
	public static $_i18n = [];
	
	function __construct() {
		if (!empty($_GET['lang'])) {
			$this->set($_GET['lang']);
		} elseif (!empty(\Session::get('lang'))) {
			self::$lang = \Session::get('lang');
		} else {
			if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
				$http_accept_language = strtolower(explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE'])[0]);
				switch (substr($http_accept_language, 0, 2)) {
					case 'en':
						$lang = 'en_US';
						break;
				
					case 'ja':
						$lang = 'ja_JP';
						break;
							
					case 'zh':
						foreach (['cn', 'hk', 'sg', 'tw'] as $v0) {
							if (strpos($http_accept_language, $v0) !== false) {
								$lang = 'zh_'.strtoupper($v0);
								break;
							}
						}
						break;
				
					default:
						$lang = self::$default;
						break;
				}
			} else {
				$lang = self::$default;
			}
			$this->set($lang);
		}
	}
	
	static function get() {
		return self::$lang;
	}
	
	static function i18n($keyword) {
		if ($keyword === null || trim($keyword) === '') {
			$return = null;
		} elseif (isset(self::$_i18n[$keyword])) {
			$return = self::$_i18n[$keyword];
		} else {
			$m_i18n = Model('i18n')->column(['lang_id', 'value'])->where([[[['keyword', '=', $keyword], ['lang_id', '=', self::$lang]], 'and']])->fetch();
			$return = self::$_i18n[$keyword] = empty($m_i18n)? $keyword : $m_i18n['value'];
		}
		
		return $return;
	}
	
	function set($lang) {
		if (self::$support === null) {
			self::$support = [];
			$m_lang = Model('lang')->column(['lang_id'])->where([[[['act', '=', 'open']], 'and']])->fetchAll();
			
			self::$support = array_column($m_lang, 'lang_id');
		}
		
		if (!in_array($lang, self::$support)) $lang = self::$default;
		
		self::$lang = \Session::set('lang', $lang);
		
		return true;
	}
}