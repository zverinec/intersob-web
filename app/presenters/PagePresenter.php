<?php

use Nette\Application\UI;

class PagePresenter extends BasePresenter {
	
	public function actionDefault($year = NULL) {
//		if($year == NULL) {
//			$event = $this->prepareLastEvent();
//		} else {
//			$event = $this->prepareEvent($year);
//		}
	}
	
	public function actionShow($year, $url) {
		$event = $this->prepareEvent($year);
		$model2 = $this->context->createPage();
		$page = $model2->findByYearAndUrl($event->id_year, $url);
		if(!$page) {
			throw new Nette\Application\BadRequestException();
		}
		if($page->hidden && !$this->user->isInRole(\Intersob\Models\User::ADMIN)) {
			throw new Nette\Application\BadRequestException();
		}
		$this->template->page = $page;
	}

	public function actionList($year) {
		$model = $this->context->createYear();
		$yearData = $model->findByYear($year);
		if(!$yearData) {
			throw new Nette\Application\BadRequestException();
		}
		$model2 = $this->context->createPage();
		$pages = $model2->findByYear($yearData->id_year);
		
		$this->template->pages = $pages;
	}
	
	public function actionCreate($year) {
		if(empty($year)) {
			throw new \Nette\Application\BadRequestException();
		}
		$this->ensureAdminRight();
		
	}
	public function createComponentCreateForm($name) {
		$form = $this->sharedYearForm($name); 
		$form->addSubmit('send','Přidat');
		$form->onSuccess[] = $this->createFormSent;
		return $form;
	}
	public function createFormSent(Nette\Forms\Form $form) {
		$values = $form->values;
		$this->prepareEvent($this->getParam('year'));
		$values['id_year'] = $this->template->event->id_year;
		$values['url'] = Nette\Utils\Strings::webalize($values['url']);
		$model = $this->context->createPage();
		try {
			$model->insert($values);
		} catch(\Exception $e) {
			$form->addError('Vložení nové stránky selhalo, stránka s daným URL již v ročníku existuje.');
			return;
		}
		$this->flashMessage('Nová stránka byla úspěšně vytvořena.', 'success');
		$this->redirect('list', $this->getParam('year'));
	}
	public function actionUpdate($id) {
		$this->ensureAdminRight();
		
		$model = $this->context->createPage();
		$data = $model->find($id)->toArray();
		
		$this->getComponent('updateForm')->setDefaults($data);
	}
	public function createComponentUpdateForm($name) {
		$form = $this->sharedYearForm($name); 
		$form->addSubmit('send','Upravit');
		$form->onSuccess[] = $this->updateFormSent;
		return $form;
	}
	public function updateFormSent(Nette\Forms\Form $form) {
		$values = $form->values;
		$values['url'] = Nette\Utils\Strings::webalize($values['url']);
		$id = $this->getParam('id');
		$model = $this->context->createPage();
		try {
			$new = $model->update($id, $values);
		} catch(\Exception $e) {
			$form->addError('Upravení stránky selhalo, stránka s daným URL již v ročníku existuje.');
			return;
		}
		$this->flashMessage('Stránky byla úspěšně upravena.', 'success');
		$model2 = $this->context->createYear();
		$year = $model2->find($new->id_year);
		$this->redirect('list', $year->date->format('Y'));
	}
	public function actionDelete($id) {
		$this->ensureAdminRight();
		
		$model = $this->context->createPage();
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
		$model = $this->context->createPage();
		$page = $model->find($id);
		if($form['yes']->isSubmittedBy()) {
			$model->delete($id);
			$this->flashMessage('Stránka byla úspěšně smazána.', 'success');
		} else {
			$this->flashMessage('Nic nebylo provedeno.', 'info');
		}
		$model2 = $this->context->createYear();
		$year = $model2->find($page->id_year);
		$this->redirect('list', $year->date->format('Y'));
		return;
	}
	
	private function sharedYearForm($name) {
		$form = new UI\Form($this,$name);
		$form->addGroup('Zařazení a obsah');
		$form->addText('heading','Nadpis:', 60)
				->setRequired('Vyplňte, prosím, nadpis stránky.');
		$form->addText('url','URL stránky:')
				->setRequired('Vyplňte, prosím, URL stránky.')
				->addRule(Nette\Forms\Form::MAX_LENGTH, 'Délka URL stránky může být maximálně 100znaků.', 100)
				->setOption('description', 'Zobrazuje v řádku adresy, musí být unikátní v rámci ročníku.');
		$form->addTextArea('content', 'Obsah stránky:', 50,20)
				->setRequired('Vyplňte, prosím, obsah stránky.')
				->setOption('description', 'Zobrazí se po otevření stránky.');
		
		$form->addGroup('Metainformace');
		$form->addTextArea('description', 'Krátký popis:', 50,3)
				->setRequired('Vyplňte, prosím, krátký popis stránky a jejího obsahu.')
				->setOption('description', 'Používá se pro vyhledávače.');
		$form->addTextArea('keywords', 'Klíčová slova:', 50,3)
				->setRequired('Vyplňte, prosím, klíčová slova, která stránku charakterizují.')
				->setOption('description', 'Používá se pro vyhledávače, jednotlivá slova oddělená čárkami.');
	
		$form->setCurrentGroup();
		$form->addCheckbox('hidden', 'Stránka je skryta');
		return $form;
	}

}
