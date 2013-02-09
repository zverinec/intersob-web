<?php

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter {
	
	protected function ensureAdminRight() {
		if(!$this->user->isInRole(\Intersob\Models\User::ADMIN)) {
			$this->redirect("Admin:login");
			exit;
		}
	}
	protected function ensureNonAdminRight() {
		if($this->user->isInRole(\Intersob\Models\User::ADMIN)) {
			$this->redirect("Admin:");
		}
	}
	
	protected function ensureTeamRight() {
		if(!$this->user->isInRole(\Intersob\Models\Team::TEAM)) {
			$this->redirect("Team:login", $this->getParam('year'));
			exit;
		}
	}
	protected function ensureNonTeamRight() {
		if($this->user->isInRole(\Intersob\Models\Team::TEAM)) {
			$this->redirect("Team:settings", $this->getParam('year'));
		}
	}
	
	protected function prepareEvent($year) {
		if(isSet($this->template->event) && $this->template->event->date->format('Y') == $year) {
			return $this->template->event;
		}
		$model = $this->context->createYear();
		$event = $model->findByYear($year);
		if(!$event) {
			throw new Nette\Application\BadRequestException();
		}
		$this->template->event = $event;
		return $event;
	}
	
	protected function prepareLastEvent() {
		$model = $this->context->createYear();
		$event = $model->findLastYear();
		if(!$event) {
			throw new Nette\Application\BadRequestException();
		}
		$this->template->event = $event;
		return $event;
	}
	
	protected function beforeRender() {
		parent::beforeRender();
		if(empty($this->template->event)) {
			$year = $this->getParam('year');
			$model = $this->context->createYear();
			if(empty($year)) {
				$event = $model->findLastYear();
			} else {
				$event = $model->findByYear($year);
			}
			if(!$event) {
				//throw new Nette\Application\BadRequestException();
			}
			$this->template->event = $event;
		}
		if($this->template->event) {
			$this->template->menu1 = $this->template->event->menu1;
			$this->template->menu2 = $this->template->event->menu2;
			$this->template->volume = $this->template->event->date->format("Y");
		} else {
			$this->template->menu1 = $this->template->menu2 = $this->template->volume = "";
			if($this->getName() != 'Admin' && $this->getName() != "Year") {
				$this->redirect('Admin:');
			}
		}
		
		// Fetch all years
		$model = $this->context->createYear();
		$years = $model->findAll()->order('date DESC');
		$this->template->yearsSelectBox = $years;
		
		$event = $this->template->event;	
		if ($this->user->isInRole(\Intersob\Models\Team::TEAM) && $this->user->getIdentity()->id_year != $event->id_year) {
			$this->user->logout();
			$this->redirect('this');
		}
	}
	
	public function createTemplate($class = NULL) {
		// inicializace
		$texy = new \Texy();
		$texy->encoding = 'utf-8';
		//$texy->allowedTags = \Texy::NONE;
		//$texy->allowedStyles = \Texy::NONE;
		$texy->setOutputMode(\Texy::HTML4_TRANSITIONAL);

		// registrace filtru
		$template = parent::createTemplate($class);
		$template->registerHelper('texy', callback($texy, 'process'));
		return $template;
	}
	
}
