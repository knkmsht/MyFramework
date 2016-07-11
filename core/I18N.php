<?php
namespace Core;
class I18N {
	function __construct() {}
	
	static function cellphone($cellphone) {
		$function = function ($cellphone) {
			$cellphone = str_replace(' ', '', $cellphone);
			if (strlen($cellphone) >= 10 && strlen($cellphone) <= 14) {
				if (substr($cellphone, 0, 2) == '09') {
					$cellphone = substr_replace($cellphone, '+886', 0, 1);
				} elseif (substr($cellphone, 0, 6) == '+88609') {
					$cellphone = substr_replace($cellphone, '+886', 0, 5);
				}
			}
			
			return $cellphone;
		};
		
		return is_array($cellphone)? array_map($function, $cellphone) : $function($cellphone);
	}
}