<?php

namespace Intersob\Models;

use Nette;
use Nette\Utils\DateTime;
use Nette\Utils\Validators;

class Helpers {

	public static function validateDate(Nette\Forms\Control $control) {
		$date = $control->getValue();
		return self::validateDateFromString($date);
	}

	private static function validateDateFromString($date) {
		try {
			$check = new DateTime($date);
			$date = explode('-', $date);
			if (count($date) != 3) {
				return false;
			}
			return checkdate($date[1], $date[2], $date[0]);
		} catch (\Exception $e) {
			return false;
		}
	}

	public static function validateDateTime(Nette\Forms\Control $control) {
		$date = $control->getValue();
		try {
			$check = new DateTime($date);
			$date = explode(' ', $date);
			if (count($date) != 2) {
				return false;
			}
			if(!self::validateDateFromString($date[0])) {
				return false;
			}
			$time = explode(':', $date[1]);
			if(count($time) != 3) {
				return false;
			}
			if(!Validators::isNumericInt($time[0]) || !Validators::isNumericInt($time[1]) || !Validators::isNumericInt($time[2])) {
				return false;
			}
			if($time[0] < 0 || $time[0] > 23) {
				return false;
			}
			if($time[1] < 0 || $time[1] > 59) {
				return false;
			}
			if($time[2] < 0 || $time[2] > 59) {
				return false;
			}
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}



}

?>
