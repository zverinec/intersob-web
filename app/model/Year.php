<?php
namespace Intersob\Models;


class Year extends BaseModel {
	public function findByYear($year) {
		return $this->findAll()->where('YEAR(date) = ?', $year)->fetch();
	}
	
	public function findLastYear() {
		return $this->findAll()->order('date DESC')->limit(1)->fetch();
	}
}
