<?php
namespace Intersob\Models;


use Nette\Security\AuthenticationException;
use Nette\Security\IAuthenticator;
use Nette\Security\Identity;

class MultiAuthenticator implements IAuthenticator {

	const ADMIN = 0;
	const TEAM = 1;

	/** @var Team */
	private $team;
	/** @var Admin */
	private $admin;

	private $year;
	private $salt;
	private $type;


	public function __construct(Team $team, Admin $admin, $salt) {
		$this->team = $team;
		$this->admin = $admin;
		$this->salt = $salt;

		$this->type = self::ADMIN;
	}
	
	public function setYear($year) {
		$this->year = $year;
	}

	public function setType($type) {
		if ($type === self::ADMIN || $type === self::TEAM) {
			$this->type = $type;
		}
	}

	public function authenticate(array $credentials) {
		if ($this->type === self::ADMIN) {
			return $this->authenticateAdmin($credentials);
		} else {
			return $this->authenticateTeam($credentials);
		}
	}
	
	public function calculateHash($password) {
		return hash('sha256', $this->salt."#".$password);
	}

	public function authenticateTeam(array $credentials) {
		if(empty($this->year)) {
			throw new AuthenticationException('Nastala chyba při přihlašování, vyberte správný ročník.');
		}
		list($username, $password) = $credentials;
		$row = $this->team->getTableSelection()->where("name = ? AND id_year = ?", $username, $this->year)->fetch();

		if (!$row) {
			throw new AuthenticationException('Tým s tímto názvem neexistuje.', self::IDENTITY_NOT_FOUND);
		}

		if ($row->password !== $this->calculateHash($password)) {
			throw new AuthenticationException('Hesla nesouhlasí.', self::INVALID_CREDENTIAL);
		}
		$temp = $row->toArray();
		unset($temp['password']);
		return new Identity($row->id_team, Team::TEAM,  $temp);
	}

	public function authenticateAdmin(array $credentials) {
		list($username, $password) = $credentials;
		$row = $this->admin->getTableSelection()->where("nickname = ?", $username)->fetch();

		if (!$row) {
			throw new AuthenticationException('Účet s touto přezdívkou neexistuje.', self::IDENTITY_NOT_FOUND);
		}

		if ($row->password !== $this->calculateHash($password)) {
			throw new AuthenticationException('Hesla nesouhlasí.', self::INVALID_CREDENTIAL);
		}
		$temp = $row->toArray();
		unset($temp['password']);
		return new Identity($row->id_user, Admin::ADMIN,  $temp);
	}

}
