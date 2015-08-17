<?php

use Nette\Application\UI;
use Nette\InvalidStateException;
use Nette\Utils\Finder;
use Nette\Utils\Strings;

class FilePresenter extends BasePresenter {

	public function startup() {
		$this->ensureAdminRight();
		parent::startup();
	}
	
	public function actionDefault($subpath = null) {
		$params = $this->context->getParameters();
		$subpath = str_replace('..', '', $subpath);
		if(empty($subpath)) {
			$files = array();
			foreach($params['uploads']['dirs'] as $dir) {
				$dir = str_replace('..', '', $dir);
				$files[] = new SplFileInfo(__DIR__ . '/../../public/' . $dir);
			}
			$path = '';
		} else {
			$path = str_replace('..', '', $subpath);
			$path = __DIR__ . '/../../public/' .$path;
			$files = iterator_to_array(Finder::find('*')->exclude('.*')->in($path));
		}
		$this->template->files = $files;
		$this->template->subpath = $subpath;
		$this->template->sizeLimit = $this->maxSizeOfFileUpload();
	}

	public function createComponentAddForm($name) {
		$form = new UI\Form();
		$form->addGroup('Vytvořit nový adresář');
		$form->addText('new', 'Jméno adresáře:')
			->setRequired('Vyplňte, prosím, jméno nového adresáře')
			->addRule(UI\Form::MAX_LENGTH,'Délka jména může být maximálně 255 znaků.', 255);
		$form->addSubmit('submitted','Vytvořit');
		$form->onSuccess[] = array($this, 'addFormSubmitted');
		return $form;
	}

	public function addFormSubmitted(\Nette\Forms\Form $form) {
		$values = $form->getValues();
		$subpath = $this->getParameter('subpath');
		$subpath = str_replace('..', '', $subpath);
		if(empty($subpath)) {
			$form->addError('Jméno adresáře nemůže být prázdné');
			return;
		}
		$path = __DIR__ . '/../../public/' .$subpath . '/' . Strings::webalize($values['new']);
		if(!mkdir($path, 0777)) {
			$this->flashMessage('Vytvoření nového adresáře se nezdařilo. Kontaktujte správce.', 'error').
			$this->redirect('this');
		} else {
			$this->flashMessage('Nový adresář byl úspěšně vytvořen.', 'success') .
			$this->redirect('this');
		}
	}

	public function actionDelete($file) {
		$file = str_replace('..','', $file);
		$path = __DIR__ . '/../../public/' .$file;

		if(file_exists($path)) {
			if(is_dir($path)) {
				$result = rmdir($path);
			} else {
				$result = unlink($path);
			}
			if ($result) {
				$this->flashMessage('Položka byla úspěšně smazána.', 'success');
			} else {
				$this->flashMessage('Položku se nepodařilo smazat. Kontaktujte správce.', 'error');
			}
		} else {
			$this->flashMessage('Položka neexistuje.','error');
		}
		$this->redirect('default', array('subpath' => $this->extractUntilLastSlash($file)));
	}

	public function createComponentUploadForm($name) {
		$form = new UI\Form();
		$form->addGroup('Nahrát nové soubory');
		$form->addUpload('file1');
		$form->addUpload('file2');
		$form->addUpload('file3');
		$form->addUpload('file4');
		$form->addUpload('file5');
		$form->addSubmit('submitted','Nahrát');
		$form->onSuccess[] = array($this, 'uploadFormSubmitted');
		return $form;
	}
	public function uploadFormSubmitted(\Nette\Forms\Form $form) {
		$values = $form->getValues();
		$subpath = $this->getParameter('subpath');
		$subpath = str_replace('..', '', $subpath);
		if(empty($subpath)) {
			$form->addError('Do tohoto adresáře nelze nahrávat.');
			return;
		}
		$files = array($values['file1'],$values['file2'],$values['file3'],$values['file4'],$values['file5']);
		//dump($files);
		$i = 1;
		foreach($files as $file) {
			if($file->isOk()) {
				$path = __DIR__ . '/../../public/' .$subpath . '/' . $file->getName();
				try {
					$file->move($path);
				} catch (InvalidStateException $ex) {
					$form->addError('Přesun souboru č. ' . $i . ' do požadované složky selhal. Pravděpodobně došlo k problému s oprávněními, kontaktujte správce.');
				}
			}
			if ($file->getTemporaryFile() === '') {
				$form->addError('Upload souboru č. ' . $i . ' selhal. Pravděpodobně překročena maximální velikost souboru či délka běhu skriptu.');
				return;
			}
			$i++;
		}
		$this->flashMessage('Upload byl úspěšně dokončen.','success');
		$this->redirect('this');
	}

	public function extractUntilLastSlash($value) {
		return Strings::substring($value, 0, mb_strrpos($value, '/'));
	}

	private function maxSizeOfFileUpload() {
		static $max_size = -1;

		if ($max_size < 0) {
			// Start with post_max_size.
			$max_size = $this->parseSize(ini_get('post_max_size'));

			// If upload_max_size is less, then reduce. Except if upload_max_size is
			// zero, which indicates no limit.
			$upload_max = $this->parseSize(ini_get('upload_max_filesize'));
			if ($upload_max > 0 && $upload_max < $max_size) {
				$max_size = $upload_max;
			}
		}
		return $max_size;
	}

	private function parseSize($size) {
		$unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
		$size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
		if ($unit) {
			// Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
			return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
		}
		else {
			return round($size);
		}
	}
}
