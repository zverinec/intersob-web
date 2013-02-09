<?php
namespace Intersob\Models;

use Nette,
	Nette\Security;

/**
 * Users authenticator.
 */
class User extends BaseModel implements Security\IAuthenticator {
	
	const ADMIN = 'admin';

	private $salt;

	public function __construct(Nette\Database\Connection $connection, $salt) {
		parent::__construct($connection);
		if (empty($salt)) {
			throw new Nette\InvalidStateException("Non valid security salt. Cannot continue.");
		}
		$this->salt = $salt;
	}

	/**
	 * Performs an authentication.
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials) {
		list($username, $password) = $credentials;
		$row = $this->findAll()->where("nickname = ?", $username)->fetch();

		if (!$row) {
			throw new Security\AuthenticationException('Účet s touto přezdívkou neexistuje.', self::IDENTITY_NOT_FOUND);
		}

		if ($row->password !== $this->calculateHash($password)) {
			throw new Security\AuthenticationException('Hesla nesouhlasí.', self::INVALID_CREDENTIAL);
		}
		unset($row->password);
		return new Security\Identity($row->id_user, self::ADMIN,  $row->toArray());
	}
	
	public function calculateHash($password) {
		return hash('sha256', $this->salt."#".$password);
	}

}
