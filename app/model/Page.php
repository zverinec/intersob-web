<?php
namespace Intersob\Models;

class Page extends BaseModel {
	public function findByYearAndUrl($year,$url) {
		return $this->findAll()->where('id_year = ? AND url = ?', $year,$url)->fetch();
	}
	
	public function findByYear($year) {
		return $this->findAll()->where('id_year = ?', $year);
	}
}
