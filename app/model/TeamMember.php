<?php
namespace Intersob\Models;

use Nette,
	Nette\Security;

/**
 * Users authenticator.
 */
class TeamMember extends BaseModel{
	public function findFromYear($id_year) {
		$data = $this->getConnection()->query("SELECT team_member.* FROM team_member INNER JOIN team USING(id_team) WHERE id_year= ?", $id_year)->fetchAll();
		$output = array();
		foreach($data as $row) {
			$output[$row->id_team][] = $row;
		}
		return $output;
	} 
	/** @return Nette\Database\Table\Selection */
	public function findFromTeam($id_team) {
		return $this->findAll()->where('id_team = ?', $id_team)->order("id_team_member ASC");
	}
}
