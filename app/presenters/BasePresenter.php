<?php
use Intersob\Models\Admin;
use Intersob\Models\Team;
use Intersob\Models\Year;
use App\Utils\Helpers;
use Nette\Application\BadRequestException;

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter {

	/** @var Year */
	public $year;

	public function injectBase(Year $year) {
		$this->year = $year;
	}
	
	protected function ensureAdminRight() {
		if(!$this->user->isInRole(Admin::ADMIN)) {
			$this->redirect("Admin:login");
			exit;
		}
	}
	protected function ensureNonAdminRight() {
		if($this->user->isInRole(Admin::ADMIN)) {
			$this->redirect("Admin:");
		}
	}
	
	protected function ensureTeamRight() {
		if(!$this->user->isInRole(Team::TEAM)) {
			$this->redirect("Team:login", $this->getParameter('year'));
			exit;
		}
	}
	protected function ensureNonTeamRight() {
		if($this->user->isInRole(Team::TEAM)) {
			$this->redirect("Team:settings", $this->getParameter('year'));
		}
	}
	
	protected function prepareEvent($year) {
		if(isSet($this->template->event) && $this->template->event->date->format('Y') == $year) {
			return $this->template->event;
		}
		$event = $this->year->findByYear($year);
		if(!$event) {
			throw new BadRequestException;
		}
		$this->template->event = $event;
		return $event;
	}
	
	protected function prepareLastEvent() {
		$event = $this->year->findLastYear();
		if(!$event) {
			throw new Nette\Application\BadRequestException();
		}
		$this->template->event = $event;
		return $event;
	}
	
	protected function beforeRender() {
		parent::beforeRender();
		if($this->user->isInRole(Admin::ADMIN)) {
			$this->template->isAdmin = TRUE;
		}
		if(empty($this->template->event)) {
			$year = $this->getParameter('year');
			if(empty($year)) {
				$event = $this->year->findLastYear();
			} else {
				$event = $this->year->findByYear($year);
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
		$years = $this->year->findAll()->order('date DESC');
		$this->template->yearsSelectBox = $years;
		
		$event = $this->template->event;
		if ($this->user->isInRole(Team::TEAM) && $this->user->getIdentity()->id_year != $event->id_year) {
			$this->user->logout();
			$this->redirect('this');
		}
	}
	
	public function createTemplate($class = NULL) {
		$template = parent::createTemplate($class);
		$template->getLatte()->addFilter('texy', Helpers::getHelper('texy'));
		return $template;
	}
}
