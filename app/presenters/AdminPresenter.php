<?php

use Intersob\Models\Admin;
use Nette\Application\BadRequestException;
use Nette\Application\UI;
use Nette\Security\AuthenticationException;
use Nette\Utils\DateTime;

class AdminPresenter extends BasePresenter {

	/** @var Admin */
	private $admin;

	public function injectAdmin(Admin $admin) {
		$this->admin = $admin;
	}

	protected function createComponentLoginForm() {
		$form = new UI\Form;
		$form->addText('nickname', 'Přezdívka:')
				->setRequired('Zadejte prosím svoji přezdívku.');

		$form->addPassword('password', 'Heslo:')
				->setRequired('Zadejte prosím své heslo.');

		$form->addSubmit('send', 'Přihlásit');

		$form->onSuccess[] = [$this, 'loginFormSucceded'];
		return $form;
	}

	public function loginFormSucceded($form) {
		$values = $form->getValues();

		try {
			$this->getUser()->login($values->nickname, $values->password);
		} catch (AuthenticationException $e) {
			$form->addError($e->getMessage());
			return;
		}

		$this->redirect('Admin:');
	}

	public function actionDefault() {
		$this->ensureAdminRight();

	}

	public function actionList() {
		$this->ensureAdminRight();
		$this->template->admins = $this->admin->findAll();
	}

	public function actionAdd() {
		$this->ensureAdminRight();
	}

	public function actionEdit($id) {
		$this->ensureAdminRight();
		$data = $this->admin->find($id);
		if($data === FALSE) {
			throw new BadRequestException();
		}
		$this->getComponent('editForm')->setDefaults($data);
		$this->template->data = $data;
	}
	public function actionDelete($id) {
		$this->ensureAdminRight();
		$data = $this->admin->find($id);
		if($data === FALSE) {
			throw new BadRequestException();
		}
		$this->template->data = $data;
	}

	public function actionLogin() {
		$this->ensureNonAdminRight();

	}

	public function actionLogout(): never {
		$this->getUser()->logout();
		$this->flashMessage('Byli jste úspěšně odhlášeni.', 'success');
		$this->redirect('login');
	}

	private function prepareForm() {
		$form = new UI\Form;
		$form->addGroup('Přihlašování');
		$form->addText('nickname', 'Přihlašovací jméno:')
			->setRequired('Zadejte, prosím, přihlašovací jméno.');
		$form->addPassword('password', 'Heslo:');

		$form->setCurrentGroup(null);
		return $form;
	}

	protected function createComponentAddForm() {
		$form = $this->prepareForm();
		$form['password']->setRequired('Zadejte, prosím, heslo.');
		$form->addSubmit('send', 'Přidat uživatele');
		$form->onSuccess[] = [$this, 'addFormSucceeded'];
		return $form;
	}

	public function addFormSucceeded(\Nette\Forms\Form $form) {
		$values = $form->getValues();
		$values['password'] = $this->user->getAuthenticator()->calculateHash($values['password']);
		$values['inserted'] = new DateTime();

		try {
			$this->admin->insert($values);
		} catch(PDOException $ex) {
			$form->addError('Použitá přezdívka již existuje, zadejte, prosím, jinou.');
			return;
		}
		$this->flashMessage('Uživatel byl úspěšně přidán.', 'success');
		$this->redirect('list');
	}

	protected function createComponentEditForm() {
		$form = $this->prepareForm();
		$form['password']->setOption('description', '(pouze pokud se mění)');
		$form->addSubmit('send', 'Upravit uživatele');
		$form->onSuccess[] = [$this, 'editFormSucceeded'];
		return $form;
	}

	public function editFormSucceeded(\Nette\Forms\Form $form) {
		$values = $form->getValues();
		if(empty($values['password'])) {
			unset($values['password']);
		} else {
			$values['password'] = $this->user->getAuthenticator()->calculateHash($values['password']);
		}

		try {
			$this->admin->update($this->getParameter('id'), $values);
		} catch(PDOException $ex) {
			$form->addError('Použitá přezdívka již existuje, zadejte, prosím, jinou.');
			return;
		}
		$this->flashMessage('Uživatel byl úspěšně upraven.', 'success');
		$this->redirect('list');
	}

	protected function createComponentDeleteForm() {
		$form = new UI\Form();
		$form->addSubmit('yes', 'Ano');
		$form->addSubmit('no', 'Ne');
		$form->onSuccess[] = [$this, 'deleteFormSucceeded'];
		return $form;
	}

	public function deleteFormSucceeded(\Nette\Forms\Form $form) {
		$values = $form->getValues();
		$userCount = $this->admin->findAll()->count();
		if($userCount == 1) {
			$form->addError('Poslední uživatel nemůže být smazán.');
			return;
		}
		if($form['yes']->isSubmittedBy()) {
			$this->admin->delete($this->getParameter('id'));
			$this->flashMessage('Uživatel byl úspěšně smazán.', 'success');
		}
		$this->redirect('list');
	}

}
