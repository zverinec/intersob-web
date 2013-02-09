<?php

use Nette\Application\UI;

class AdminPresenter extends BasePresenter {
	
	protected function createComponentLoginForm() {
		$form = new UI\Form;
		$form->addText('nickname', 'Přezdívka:')
				->setRequired('Zadejte prosím svoji přezdívku.');

		$form->addPassword('password', 'Heslo:')
				->setRequired('Zadejte prosím své heslo.');

		$form->addSubmit('send', 'Přihlásit');

		$form->onSuccess[] = $this->loginFormSucceded;
		return $form;
	}

	public function loginFormSucceded($form) {
		$values = $form->getValues();

		try {
			$this->getUser()->login($values->nickname, $values->password);
		} catch (Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
			return;
		}

		$this->redirect('Admin:');
	}
	
	public function actionDefault() {
		$this->ensureAdminRight();
		
	}
	
	public function actionLogin() {
		$this->ensureNonAdminRight();

	}

	public function actionLogout() {
		$this->getUser()->logout();
		$this->flashMessage('Byli jste úspěšně odhlášeni.', 'success');
		$this->redirect('login');
	}

}
