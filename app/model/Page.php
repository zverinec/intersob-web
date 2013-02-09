<?php
namespace Intersob\Models;

/**
 * Users authenticator.
 */
class Page extends BaseModel {
	public function findByYearAndUrl($year,$url) {
		return $this->findAll()->where('id_year = ? AND url = ?', $year,$url)->fetch();
	}
	
	public function findByYear($year) {
		return $this->findAll()->where('id_year = ?', $year);
	}
}
