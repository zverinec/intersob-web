<?php
namespace Intersob\Models;

use Nette,
	Nette\Security;
use Nette\Database\Table\Selection;

class TeamMember extends BaseModel{
	public function findFromYear($id_year) {
		$data = $this->getConnection()->query("SELECT team_member.* FROM team_member INNER JOIN team USING(id_team) WHERE id_year= ?", $id_year)->fetchAll();
		$output = array();
		foreach($data as $row) {
			$output[$row->id_team][] = $row;
		}
		return $output;
	} 
	/** @return Selection */
	public function findFromTeam($id_team) {
		return $this->findAll()->where('id_team = ?', $id_team)->order("id_team_member ASC");
	}

	public function findMails($include, $exclude) {
		if (count($exclude) == 0) {
			$exclude[] = NULL;
		}
		return $this->connection->query('
			SELECT email
			FROM team_member
			WHERE
				LENGTH(email) > 0 AND
				id_team IN (SELECT id_team FROM team WHERE id_year IN (?)) AND
				email NOT IN (SELECT email FROM team_member WHERE id_team IN (SELECT id_team FROM team WHERE id_year IN (?)))
			GROUP BY email;
		', $include, $exclude);
	}
}
