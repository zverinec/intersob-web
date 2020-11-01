<?php

use Intersob\Models\Admin;
use Intersob\Models\MultiAuthenticator;
use Intersob\Models\Team;
use Intersob\Models\TeamMember;
use Nette\Application\UI;
use Nette\Forms\Form;
use Nette\Security\AuthenticationException;
use Nette\Utils\DateTime;

class TeamPresenter extends BasePresenter {

	/** @var Team */
	private $team;
	/** @var TeamMember */
	private $teamMember;

	public function injectTeam(Team $team, TeamMember $teamMember) {
		$this->team = $team;
		$this->teamMember = $teamMember;
	}

	public function beforeRender() {
		parent::beforeRender();
		$this->template->icon = 'teams';
	}

	public function actionDefault($year, $order = NULL, $members2 = NULL) {
		
		$event = $this->prepareEvent($year);
		$teams = $this->team->findAll()->where("id_year = ?", $event->id_year);
		$members = $this->teamMember->findFromYear($event->id_year);

		if ($this->user->isInRole(Admin::ADMIN)) {
			if ($order === 'inserted') {
				$teams = $teams->order('inserted ASC');
			} else {
				$teams = $teams->order('name ASC');
			}
		}

		$this->template->teams = $teams;
		$this->template->members = $members;

	}
	
	public function actionRegistration($year) {
		$this->ensureNonTeamRight();
		$event = $this->prepareEvent($year);
		$current = new DateTime;
		if($current > $event->reg_open && $current < $event->reg_closed) {
			$this->template->registrationOpen = true;
		}
	}
	public function createComponentRegForm($name) {
		$form = $this->sharedTeamForm($name);
		
		$form->addSubmit('sent', 'Zaregistrovat');
		$form->onSuccess[] = [$this, 'regFormSent'];
		return $form;
	}
	public function regFormSent(Form $form) {
		// Check if the registration is open
		$event = $this->prepareEvent($this->getParameter('year'));
		$current = new DateTime;
		if(! ($current > $event->reg_open && $current < $event->reg_closed)) {
			$this->flashMessage('Registrace neprobíhá, nelze provést registraci.', 'error');
			$this->redirect('registration', $this->getParam('year'));
			return;
		}
		$values = $form->getValues();
		$m1 = $values['m1'];
		unset($values['m1']);
		$m2 = $values['m2'];
		unset($values['m2']);
		$m3 = $values['m3'];
		unset($values['m3']);
		$m4 = $values['m4'];
		unset($values['m4']);
		unset($values['password2']);

		$event = $this->prepareEvent($this->getparameter('year'));
		$values['inserted'] = new DateTime();
		$values['id_year'] = $event->id_year;
		$values['password'] = $this->user->getAuthenticator()->calculateHash($values['password']);

		try {
			$row = $this->team->insert($values);
		} catch(\Exception $ex)  {
			$form->addError('Tým s tímto názvem je již zaregistrován. Zvolte jiný název. Pokud jste zapomněli heslo, napište nám.');
			return;
		}
		if(!$row) {
			$form->addError('Nepodařilo se zaregistrovat tým. Prosím, kontaktujte nás.');
			return;
		}
		
		$m1['id_team'] = $row->id_team;
		$m2['id_team'] = $row->id_team;
		$m3['id_team'] = $row->id_team;
		$m4['id_team'] = $row->id_team;
		
		$model2 = $this->teamMember;
		$model2->insert($m1);
		$model2->insert($m2);
		$model2->insert($m3);
		$model2->insert($m4);
		
		$this->flashMessage('Váš tým byl úspěšně zaregistrován. Těšíme se.', 'success');
		$this->redirect('login', $this->getParameter('year'));
	}
	
	private function sharedTeamForm($name, $update = false) {
		$form = new UI\Form($this,$name);
		$form->addGroup('Obecné informace o týmu');
		$form->addText('name','Název týmu:',40)
				->setRequired('Vyplňte, prosím, jméno týmu.')
				->addRule(Form::MAX_LENGTH, "Maximální délka názvu týmu může být 255 znaků.", 255)
				->setOption('description','Musí být unikátní, používá se pro přihlašování.');
		if(!$update) {
			$form->addPassword('password', 'Heslo:')
				->setRequired('Vyplňte, prosím, heslo pro přihlášení.');
			$form->addPassword('password2', 'Heslo znovu:')
				->setRequired('Vyplňte, prosím, heslo pro kontrolu shody.')
					->addRule(Form::EQUAL, 'Hesla musí souhlasit.', $form['password']);
		}
		
		$form->addText('contact_phone','Kontaktní telefon:')
				->addRule(Form::MAX_LENGTH, 'Maximální délka kontaktního telefonu je 20 znaků.', 20)
				->setRequired('Vyplňte, prosím, kontaktní telefon.')
				->setOption('description','Telefon, který budete mít v průběhu hry u sebe.');
		
		$form->addGroup('1. člen');
		$m1 = $form->addContainer('m1');
		$this->getTeamMemberInfo($m1, '1', 'prvního');
		$form->addGroup('2. člen');
		$m2 = $form->addContainer('m2');
		$this->getTeamMemberInfo($m2, '2', 'druhého');
		$form->addGroup('3. člen');
		$m3 = $form->addContainer('m3');
		$this->getTeamMemberInfo($m3, '3', 'třetího');
		$form->addGroup('4. člen');
		$m4 = $form->addContainer('m4');
		$this->getTeamMemberInfo($m4, '4', 'čtvrtého');
		
		$form->setCurrentGroup();

		$form->onError[] = [$this, 'showErrorMessage'];
		
		return $form;
	}


	public function showErrorMessage(Form $form) {
		$form->addError('Ve formuláři jsou některé pole nevyplněna nebo vyplněna chybně. Podrobnější popis naleznete níže.');
	}
	private function getTeamMemberInfo($container, $short, $long) {
		$container->addText('name','Jméno a příjmení: ')
				;//->setRequired('Vyplňte, prosím, jméno a příjmení '.$long.' člena.');
		$container->addText('age','Věk:',4)
				->addCondition(Form::FILLED)
				->addRule(Form::NUMERIC, 'Věk '.$long.' člena musí být celé číslo.')
				->addRule(Form::RANGE, 'Věk '.$long.' člena musí být mezi 10 a 22.', array(10,22))
				;//->setRequired('Vyplňte, prosím, věk '.$long.' člena.');
		$container->addText('school', 'Škola:')
			;//->setRequired('Vyplňte, prosím, školu '.$long.' člena.');
		$container->addText('email', 'E-mailová adresa:')
			->addCondition(Form::FILLED)
			->addRule(Form::EMAIL, 'Vyplňte, prosím, e-mail '.$long.' člena ve správném tvaru e-mailové adresy.')
			;//->setRequired('Vyplňte, prosím, e-mail '.$long.' člena.');
	}
	
	public function actionLogin($year) {
		$this->ensureNonTeamRight();
		
	}
	protected function createComponentLoginForm() {
		$form = new UI\Form;
		$form->addText('nickname', 'Název týmu:')
				->setRequired('Zadejte prosím jméno svého týmu.');

		$form->addPassword('password', 'Heslo:')
				->setRequired('Zadejte prosím své heslo.');

		$form->addSubmit('send', 'Přihlásit');

		$form->onSuccess[] = [$this, 'loginFormSucceded'];
		return $form;
	}

	public function loginFormSucceded($form) {
		$values = $form->getValues();

		try {
			$event = $this->prepareEvent($this->getParameter('year'));

			$authenticator = $this->user->getAuthenticator();
			$authenticator->setType(MultiAuthenticator::TEAM);
			$authenticator->setYear($event->id_year);

			$this->getUser()->login($values->nickname, $values->password);
		} catch (AuthenticationException $e) {
			$form->addError($e->getMessage());
			return;
		}

		$this->redirect('Team:settings', $this->getParam('year'));
	}
	
	public function actionLogout($year) {
		$this->ensureTeamRight();
		
		$this->getUser()->logout();
		$this->flashMessage('Byli jste úspěšně odhlášeni.', 'success');
		$this->redirect('login', $year);
	}
	
	public function actionSettings($year) {
		$this->ensureTeamRight();

		$values = $this->team->find($this->user->getId())->toArray();

		$members = $this->teamMember->findFromTeam($this->user->getId());
		$temp = array();
		$i = 1;
		foreach($members as $member) {
			$temp['m'.$i++] = $member->toArray();
		}
		
		$merged = array_merge_recursive($values, $temp);
		
		$this->getComponent('settingsForm')->setDefaults($merged);
		
		$event = $this->prepareEvent($year);
		$current = new DateTime;
		if(! ($current > $event->reg_open && $current < $event->info_embargo)) {
			$this->template->registrationOpen = false;
		}
	}
	
	public function createComponentSettingsForm($name) {
		$form = $this->sharedTeamForm($name, true);
		
		$form->addSubmit('sent', 'Změnit údaje');
		$form->onSuccess[] = [$this, 'settingsFormSent'];
		return $form;
	}
	
	public function settingsFormSent(Form $form) {
		// Check if the registration is open
		$event = $this->prepareEvent($this->getParameter('year'));
		$current = new DateTime;
		if(! ($current > $event->reg_open && $current < $event->info_embargo)) {
			$this->flashMessage('Již nelze provést změnu údajů. Sdělte nám, prosím, změny při registraci na startu. Díky.', 'error');
			$this->redirect('settings', $this->getParameter('year'));
			return;
		}
		$values = $form->getValues();
		$m1 = $values['m1'];
		unset($values['m1']);
		$m2 = $values['m2'];
		unset($values['m2']);
		$m3 = $values['m3'];
		unset($values['m3']);
		$m4 = $values['m4'];
		unset($values['m4']);

		try {
			$row = $this->team->update($this->user->getId(), $values);
		} catch(\Exception $ex)  {
			$form->addError('Tým s tímto názvem je již zaregistrován. Zvolte jiný název.');
			return;
		}
		if(!$row) {
			$form->addError('Nepodařilo se upravit údaje týmu. Prosím, kontaktujte nás.');
			return;
		}

		$members = $this->teamMember->findFromTeam($this->user->getId());
		$i = 1;
		foreach($members as $member) {
			$temp = 'm'.$i++;
			$this->teamMember->update($member->id_team_member, $$temp);
		}
		
		$this->flashMessage('Registrační údaje vašeho týmu byly úspěšně změněny.', 'success');
		$this->redirect('settings', $this->getParameter('year'));
	}
	
	public function createComponentChangePassword($name) {
		$form = new UI\Form($this,$name);
		$form->addPassword('old', 'Staré heslo:')
				->setRequired('Vyplňte, prosím, heslo pro ověření.');
		$form->addPassword('password', 'Nové heslo:')
				->setRequired('Vyplňte, prosím, nové heslo.');
		$form->addPassword('password2', 'Nové heslo znovu:')
			->setRequired('Vyplňte, prosím, heslo pro kontrolu shody.')
			->addRule(Form::EQUAL, 'Hesla musí souhlasit.', $form['password']);
		$form->addSubmit('send', 'Změnit');
		$form->onSuccess[] = [$this, 'changePassword'];
		return $form;
	}
	
	public function changePassword(Form $form) {
		$values = $form->getValues();
		if($this->user->getAuthenticator()->calculateHash($values['old']) != $this->user->getIdentity()->password) {
			$form->addError('Vámi zadané staré heslo neodpovídá skutečnému heslu.');
			return;
		}
		$values['password'] = $this->user->getAuthenticator()->calculateHash($values['password']);
		unset($values['old']);
		unset($values['password2']);
		$this->team->update($this->user->getId(), $values);
		$this->flashMessage('Vaše heslo bylo úspěšně změněno.', 'success');
		$this->redirect('settings', $this->getParameter('year'));
	}
	
	public function actionContacts($year, $order = NULL, $members2 = NULL) {
		$this->ensureAdminRight();
		$this->actionDefault($year, $order, $members2);

		$this->template->order = $order;
		$this->template->members2 = $members2;
		$this->template->year = $year;
	}

	public function actionMails() {
		$this->ensureAdminRight();
	}

	public function createComponentMails() {
		$form = new UI\Form();
		$form->addMultiSelect('include', 'Zahrnout ročníky', $this->year->findAll()->order('date DESC')->fetchPairs('id_year', 'name'), 5)
			->setRequired();
		$form->addMultiSelect('exclude', 'Vyjmout ročníky', $this->year->findAll()->order('date DESC')->fetchPairs('id_year', 'name'), 5);

		$form->addSubmit('submitted', 'Vypsat');
		$form->onSuccess[] = [$this, 'mailsSubmitted'];

		return $form;
	}

	public function mailsSubmitted(UI\Form $form) {
		$values = $form->getValues();
		if (count($values['include']) == 0) {
			$form->addError('Musíte vybrat alespoň jeden ročník.');
			return;
		}
		$result = $this->teamMember->findMails($values['include'], $values['exclude']);
		$this->template->mails = $result->fetchAll();

	}
}
