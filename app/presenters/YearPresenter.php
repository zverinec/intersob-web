<?php

use Nette\Application\UI;

class YearPresenter extends BasePresenter {
	
	public function actionDefault() {
		$model = $this->context->createYear();
		$years = $model->findAll()->order('date DESC');
		$this->template->years = $years;
	}
	
	public function actionCreate() {
		$this->ensureAdminRight();
		
	}
	public function actionUpdate($id) {
		$this->ensureAdminRight();
		
		$model = $this->context->createYear();
		$data = $model->find($id);
		if(!$data) {
			throw new Nette\Application\BadRequestException();
		}
		$data = $data->toArray();
		$data["date"] = $data['date']->format("Y-m-d");
		$this->getComponent('updateForm')->setDefaults($data);
	}
	public function actionDelete($id) {
		$this->ensureAdminRight();
		
		$model = $this->context->createYear();
		$data = $model->find($id);
		if(!$data) {
			throw new Nette\Application\BadRequestException();
		}
		$this->template->data = $data;
	}
	
	public function createComponentDeleteForm($name) {
		$form = new UI\Form($this,$name);
		$form->addSubmit('yes', 'Ano');
		$form->addSubmit('no', 'Ne');
		$form->onSuccess[] = $this->deleteFormSent;
		return $form;
	}
	public function deleteFormSent(Nette\Forms\Form $form) {
		$id = $this->getParam('id');
		if($form['yes']->isSubmittedBy()) {
			$model = $this->context->createYear();
			$model->delete($id);
			$this->flashMessage('Ročník byl úspěšně smazán.', 'success');
		} else {
			$this->flashMessage('Nic nebylo provedeno.', 'info');
		}
		$this->redirect('default');
		return;
	}
	
	public function createComponentCreateForm($name) {
		$form = $this->sharedYearForm($name); 
		$form->addSubmit('send','Přidat');
		$form->onSuccess[] = $this->createFormSent;
		return $form;
	}
	public function createFormSent(Nette\Forms\Form $form) {
		$values = $form->values;
		$date = new \Nette\DateTime($values['date']);
		$model = $this->context->createYear();
		if($model->findByYear($date->format("Y")) != FALSE) {
			$form->addError('Ve stejném roce už akce jednou proběhla.');
			return;
		}
		
		$model->insert($values);
		$this->flashMessage('Nový ročník byl úspěšně vytvořen.', 'success');
		$this->redirect('default');
	}
	
	public function createComponentUpdateForm($name) {
		$form = $this->sharedYearForm($name); 
		$form->addSubmit('send','Upravit');
		$form->onSuccess[] = $this->updateFormSent;
		return $form;
	}
	public function updateFormSent(Nette\Forms\Form $form) {
		$id = $this->getParam('id');
		$values = $form->values;
		$model = $this->context->createYear();
		$date = new \Nette\DateTime($values['date']);
		if(($temp = $model->findByYear($date->format("Y"))) != FALSE && $temp->id_year != $id) {
			$form->addError('Ve stejném roce už akce jednou proběhla.');
			return;
		}
		$model->update($id,$values);
		$this->flashMessage('Ročník byl úspěšně upraven.', 'success');
		$this->redirect('default');
	}
	
	private function sharedYearForm($name) {
		$form = new UI\Form($this,$name);
		$form->addGroup('Statické informace');
		$form->addText('name','Jméno ročníku:')
				->setRequired('Vyplňte, prosím, jméno ročníku.')
				->addRule(Nette\Forms\Form::MAX_LENGTH, 'Délka ročníku může být maximálně 255 znaků.', 255)
				->setOption('description', 'Zobrazuje se například v seznamu minulých ročníků.');
		$form->addTextArea('description', 'Krátký popis:', 50,5)
				->setRequired('Vyplňte, prosím, krátký popis nového ročníku.')
				->setOption('description', 'Používá se v seznamu minulých ročníků a pro vyhledávače.');
		$form->addText('date','Datum konání:', 10)
				->setRequired('Vyplňte, prosím, datum konání soutěže.')
				->addRule(callback('\Intersob\Models\Helpers','validateDate'), 'Vyplňte, prosím, datum konání ve správném tvaru.')
				->setOption('description', 'Ve tvaru 2013-03-23');
		$form->addText('reg_open', 'Datum otevření registrace:')
				->setRequired('Vyplňte, prosím, datum otevření registrace.')
				->addRule(callback('\Intersob\Models\Helpers','validateDateTime'), 'Vyplňte, prosím, datum otevření ve správném tvaru.')
				->setOption('description', 'Ve tvaru 2013-03-23 10:11:12');
		$form->addText('reg_closed', 'Datum uzavření registrace:')
				->setRequired('Vyplňte, prosím, datum uzavření registrace.')
				->addRule(callback('\Intersob\Models\Helpers','validateDateTime'), 'Vyplňte, prosím, datum uzavření ve správném tvaru.')
				->setOption('description', 'Ve tvaru 2013-03-23 10:11:12');
		$form->addGroup('Obsah');
		$form->addTextArea('menu1', 'Pravý sloupec menu:', 50,10)
				->setOption('description', 'Položky v pravém menu daného ročníku.');
		$form->addTextArea('menu2', 'Levý sloupec menu:', 50,10)
				->setOption('description', 'Položky v levém menu daného ročníku.');
		$form->addTextArea('content', 'Obsah titulní stránky:', 50,20)
				->setRequired('Vyplňte, prosím, obsah titulní stránky.')
				->setOption('description', 'Zobrazí se jako první stránka.');
		$form->setCurrentGroup();
		return $form;
	}
}
