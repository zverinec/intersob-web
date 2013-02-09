<?php
namespace Intersob\Models;

use Nette,
	Nette\Security;

/**
 * Users authenticator.
 */
class Team extends BaseModel implements Security\IAuthenticator {
	
	const TEAM = 'team';

	private $salt;
	
	private $year;

	public function __construct(Nette\Database\Connection $connection, $salt) {
		parent::__construct($connection);
		if (empty($salt)) {
			throw new Nette\InvalidStateException("Non valid security salt. Cannot continue.");
		}
		$this->salt = $salt;
	}
	
	public function setYear($year) {
		$this->year = $year;
	}

	/**
	 * Performs an authentication.
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials) {
		if(empty($this->year)) {
			throw new Security\AuthenticationException('Nastala chyba při přihlašování, vyberte správný ročník.');
		}
		list($username, $password) = $credentials;
		$row = $this->findAll()->where("name = ? AND id_year = ?", $username, $this->year)->fetch();

		if (!$row) {
			throw new Security\AuthenticationException('Účet s touto přezdívkou neexistuje.', self::IDENTITY_NOT_FOUND);
		}

		if ($row->password !== $this->calculateHash($password)) {
			throw new Security\AuthenticationException('Hesla nesouhlasí.', self::INVALID_CREDENTIAL);
		}
		unset($row->password);
		return new Security\Identity($row->id_team, self::TEAM,  $row->toArray());
	}
	
	public function calculateHash($password) {
		return hash('sha256', $this->salt."#".$password);
	}

}
