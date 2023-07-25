<?php

use Intersob\Models\Admin;
use Intersob\Models\Page;
use Nette\Application\BadRequestException;
use Nette\Application\UI;
use Nette\Utils\Strings;

class PagePresenter extends BasePresenter {

	/** @var Page */
	private $page;

	public function injectPage(Page $page) {
		$this->page = $page;
	}

	public function actionDefault($year = NULL) {
//		if($year == NULL) {
//			$event = $this->prepareLastEvent();
//		} else {
//			$event = $this->prepareEvent($year);
//		}
	}

	public function actionShow($year, $url) {
		$event = $this->prepareEvent($year);
		$page = $this->page->findByYearAndUrl($event->id_year, $url);
		if(!$page) {
			throw new BadRequestException();
		}
		if($page->hidden && !$this->user->isInRole(Admin::ADMIN)) {
			throw new BadRequestException();
		}
		$this->template->page = $page;
	}

	public function actionList($year) {
		$yearData = $this->year->findByYear($year);
		if(!$yearData) {
			throw new BadRequestException();
		}
		$pages = $this->page->findByYear($yearData->id_year);

		$this->template->pages = $pages;
	}

	public function actionCreate($year) {
		if(empty($year)) {
			throw new BadRequestException();
		}
		$this->ensureAdminRight();

	}
	public function createComponentCreateForm($name) {
		$form = $this->sharedYearForm($name);
		$form->addSubmit('send','Přidat');
		$form->onSuccess[] = $this->createFormSent(...);
		return $form;
	}
	public function createFormSent(Nette\Forms\Form $form) {
		$values = $form->values;
		$this->prepareEvent($this->getParameter('year'));
		$values['id_year'] = $this->template->event->id_year;
		$values['url'] = Nette\Utils\Strings::webalize($values['url']);
		try {
			$this->page->insert($values);
		} catch(\Exception $e) {
			$form->addError('Vložení nové stránky selhalo, stránka s daným URL již v ročníku existuje.');
			return;
		}
		$this->flashMessage('Nová stránka byla úspěšně vytvořena.', 'success');
		$this->redirect('list', $this->getParameter('year'));
	}
	public function actionUpdate($id) {
		$this->ensureAdminRight();

		$data = $this->page->find($id)->toArray();

		$this->getComponent('updateForm')->setDefaults($data);
	}
	public function createComponentUpdateForm($name) {
		$form = $this->sharedYearForm($name);
		$form->addSubmit('send','Upravit');
		$form->onSuccess[] = [$this, 'updateFormSent'];
		return $form;
	}
	public function updateFormSent(Nette\Forms\Form $form) {
		$values = $form->values;
		$values['url'] = Strings::webalize($values['url']);
		$id = $this->getParameter('id');
		try {
			$new = $this->page->update($id, $values);
		} catch(\Exception $e) {
			$form->addError('Upravení stránky selhalo, stránka s daným URL již v ročníku existuje.');
			return;
		}
		$this->flashMessage('Stránky byla úspěšně upravena.', 'success');
		$year = $this->year->find($new->id_year);
		$this->redirect('list', $year->date->format('Y'));
	}
	public function actionDelete($id) {
		$this->ensureAdminRight();

		$data = $this->page->find($id);
		if(!$data) {
			throw new BadRequestException();
		}
		$this->template->data = $data;
	}
	public function createComponentDeleteForm($name) {
		$form = new UI\Form($this,$name);
		$form->addSubmit('yes', 'Ano');
		$form->addSubmit('no', 'Ne');
		$form->onSuccess[] = [$this, 'deleteFormSent'];
		return $form;
	}
	public function deleteFormSent(Nette\Forms\Form $form): never {
		$id = $this->getParameter('id');
		$model = $this->page;
		$page = $model->find($id);
		if($form['yes']->isSubmittedBy()) {
			$model->delete($id);
			$this->flashMessage('Stránka byla úspěšně smazána.', 'success');
		} else {
			$this->flashMessage('Nic nebylo provedeno.', 'info');
		}
		$year = $this->year->find($page->id_year);
		$this->redirect('list', $year->date->format('Y'));
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
		$form->addText('icon', 'Ikonka v zápatí');
		return $form;
	}

}
