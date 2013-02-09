<?php

use Nette\Application\UI;

class TeamPresenter extends BasePresenter {

	public function actionDefault($year) {
		
		$event = $this->prepareEvent($year);
		$model = $this->context->createTeam();
		$this->template->teams = $model->findAll()->where("id_year = ?", $event->id_year);
		
		$model2 = $this->context->createTeamMember();
		$this->template->members = $model2->findFromYear($event->id_year);
	}
	
	public function actionRegistration($year) {
		$this->ensureNonTeamRight();
		$event = $this->prepareEvent($year);
		$current = new \Nette\DateTime;
		if($current > $event->reg_open && $current < $event->reg_closed) {
			$this->template->registrationOpen = true;
		}
	}
	public function createComponentRegForm($name) {
		$form = $this->sharedTeamForm($name);
		
		$form->addSubmit('sent', 'Zaregistrovat');
		$form->onSuccess[] = $this->regFormSent;
		return $form;
	}
	public function regFormSent(\Nette\Forms\Form $form) {
		// Check if the registration is open
		$event = $this->prepareEvent($this->getParam('year'));
		$current = new \Nette\DateTime;
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
		
		$event = $this->prepareEvent($this->getparam('year'));
		$values['id_year'] = $event->id_year;
		$values['password'] = $this->user->getAuthenticator()->calculateHash($values['password']);
		
		$model = $this->context->createTeam();
		try {
			$row = $model->insert($values);
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
		
		$model2 = $this->context->createTeamMember();
		$model2->insert($m1);
		$model2->insert($m2);
		$model2->insert($m3);
		$model2->insert($m4);
		
		$this->flashMessage('Váš tým byl úspěšně zaregistrován. Těšíme se.', 'success');
		$this->redirect('login', $this->getParam('year'));
	}
	
	private function sharedTeamForm($name, $update = false) {
		$form = new UI\Form($this,$name);
		$form->addGroup('Obecné informace o týmu');
		$form->addText('name','Název týmu:',40)
				->setRequired('Vyplňte, prosím, jméno týmu.')
				->addRule(\Nette\Forms\Form::MAX_LENGTH, "Maximální délka názvu týmu může být 255 znaků.", 255)
				->setOption('description','Musí být unikátní, používá se pro přihlašování.');
		if(!$update) {
			$form->addPassword('password', 'Heslo:')
				->setRequired('Vyplňte, prosím, heslo pro přihlášení.');
			$form->addPassword('password2', 'Heslo znovu:')
				->setRequired('Vyplňte, prosím, heslo pro kontrolu shody.')
					->addRule(\Nette\Forms\Form::EQUAL, 'Hesla musí souhlasit.', $form['password']);
		}
		
		$form->addText('contact_phone','Kontaktní telefon:')
				->addRule(\Nette\Forms\Form::MAX_LENGTH, 'Maximální délka kontaktního telefonu je 20 znaků.', 20)
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
		
		return $form;
	}
	
	private function getTeamMemberInfo($container, $short, $long) {
		$container->addText('name','Jméno a příjmení: ')
				;//->setRequired('Vyplňte, prosím, jméno a příjmení '.$long.' člena.');
		$container->addText('age','Věk:',4)
				->addCondition(\Nette\Forms\Form::FILLED)
				->addRule(\Nette\Forms\Form::NUMERIC, 'Věk '.$long.' člena musí být celé číslo.')
				->addRule(\Nette\Forms\Form::RANGE, 'Věk '.$long.' člena musí být mezi 10 a 22.', array(10,22))
				;//->setRequired('Vyplňte, prosím, věk '.$long.' člena.');
		$container->addText('school', 'Škola:')
			;//->setRequired('Vyplňte, prosím, školu '.$long.' člena.');
		$container->addText('email', 'E-mailová adresa:')
			->addCondition(\Nette\Forms\Form::FILLED)
			->addRule(\Nette\Forms\Form::EMAIL, 'Vyplňte, prosím, e-mail '.$long.' člena ve správném tvaru e-mailové adresy.')
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

		$form->onSuccess[] = $this->loginFormSucceded;
		return $form;
	}

	public function loginFormSucceded($form) {
		$values = $form->getValues();

		try {
			$model = $this->context->createTeam();
			$event = $this->prepareEvent($this->getParam('year'));
			$model->setYear($event->id_year);
			$this->getUser()->setAuthenticator($model);
			$this->getUser()->login($values->nickname, $values->password);
		} catch (Nette\Security\AuthenticationException $e) {
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
		
		$model = $this->context->createTeam();
		$values = $model->find($this->user->getId())->toArray();
		
		$model2 = $this->context->createTeamMember();
		$members = $model2->findFromTeam($this->user->getId());
		$temp = array();
		$i = 1;
		foreach($members as $member) {
			$temp['m'.$i++] = $member->toArray();
		}
		
		$merged = array_merge_recursive($values, $temp);
		
		$this->getComponent('settingsForm')->setDefaults($merged);
		
		$event = $this->prepareEvent($year);
		$current = new \Nette\DateTime;
		if(! ($current > $event->reg_open && $current < $event->reg_closed)) {
			$this->template->registrationOpen = false;
		}
	}
	
	public function createComponentSettingsForm($name) {
		$form = $this->sharedTeamForm($name, true);
		
		$form->addSubmit('sent', 'Změnit údaje');
		$form->onSuccess[] = $this->settingsFormSent;
		return $form;
	}
	
	public function settingsFormSent(\Nette\Forms\Form $form) {
		// Check if the registration is open
		$event = $this->prepareEvent($this->getParam('year'));
		$current = new \Nette\DateTime;
		if(! ($current > $event->reg_open && $current < $event->reg_closed)) {
			$this->flashMessage('Registrace neprobíhá, nelze provést úpravu údajů.', 'error');
			$this->redirect('settings', $this->getParam('year'));
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
		
		$model = $this->context->createTeam();
		try {
			$row = $model->update($this->user->getId(), $values);
		} catch(\Exception $ex)  {
			$form->addError('Tým s tímto názvem je již zaregistrován. Zvolte jiný název.');
			return;
		}
		if(!$row) {
			$form->addError('Nepodařilo se upravit údaje týmu. Prosím, kontaktujte nás.');
			return;
		}
		
		$model2 = $this->context->createTeamMember();
		$members = $model2->findFromTeam($this->user->getId());
		$i = 1;
		foreach($members as $member) {
			$temp = 'm'.$i++;
			$model2->update($member->id_team_member, $$temp);
		}
		
		$this->flashMessage('Registrační údaje vašeho týmu byly úspěšně změněny.', 'success');
		$this->redirect('settings', $this->getParam('year'));
	}
	
	public function createComponentChangePassword($name) {
		$form = new UI\Form($this,$name);
		$form->addPassword('old', 'Staré heslo:')
				->setRequired('Vyplňte, prosím, heslo pro ověření.');
		$form->addPassword('password', 'Nové heslo:')
				->setRequired('Vyplňte, prosím, nové heslo.');
		$form->addPassword('password2', 'Nové heslo znovu:')
			->setRequired('Vyplňte, prosím, heslo pro kontrolu shody.')
			->addRule(\Nette\Forms\Form::EQUAL, 'Hesla musí souhlasit.', $form['password']);
		$form->addSubmit('send', 'Změnit');
		$form->onSuccess[] = $this->changePassword;
		return $form;
	}
	
	public function changePassword(\Nette\Forms\Form $form) {
		$values = $form->getValues();
		if($this->user->getAuthenticator()->calculateHash($values['old']) != $this->user->getIdentity()->password) {
			$form->addError('Vámi zadané staré heslo neodpovídá skutečnému heslu.');
			return;
		}
		$values['password'] = $this->user->getAuthenticator()->calculateHash($values['password']);
		unset($values['old']);
		unset($values['password2']);
		$model = $this->context->createTeam();
		$model->update($this->user->getId(), $values);
		$this->flashMessage('Vaše heslo bylo úspěšně změněno.', 'success');
		$this->redirect('settings', $this->getParam('year'));
	}
	
	public function actionContacts($year) {
		$this->ensureAdminRight();
		$this->actionDefault($year);
	}
}
