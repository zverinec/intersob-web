<?php
namespace App\Utils;

use Nette\InvalidArgumentException;
use Nette\InvalidStateException;
use Nette\Utils\Html;
use Texy;

class Helpers
{
	/** @var Texy */
	private static $texy;

	public static function getHelper($helper) {
		if (empty($helper)) {
			throw new InvalidArgumentException("helper");
		}
		switch ($helper) {
			case "texy": return array(__CLASS__, 'texyHelper');
				break;
			default:
				throw new InvalidStateException("The helper [$helper] does not exist.");
		}
	}

	public static function texyHelper($input) {
		if (empty(self::$texy)) {
			self::$texy = new Texy();
		}
		return self::$texy->process($input);
	}
}
