<?php
use Contao\Environment;
class ModuleEuleo extends BackendModule
{
	protected $strTemplate = 'config';
	
	/**
	 * @var Euleo_Contao
	 */
	protected $bridge = false;
	
	public function generate()
	{
		try {
			System::loadLanguageFile('tl_euleo');
		
			$this->bridge = new Euleo_Contao();
		
			$connected = $this->bridge->connect();
		} catch (Exception $e) {
			$this->error = $e->getMessage();
		}

		
		if (Input::get('action') == 'install') {
			if ($connected) {
				$this->bridge->uninstall();
			}
			$token = $this->bridge->getRegisterToken();
		
			if ( $_SERVER['SERVER_ADDR'] == '192.168.1.10' ) {
				$link = 'http://euleo/registercms/' . $token;
			} else {
				$link = 'https://www.euleo.com/registercms/' . $token;
			}
		
			$this->redirect($link);
		} else if ($GLOBALS['TL_CONFIG']['euleo_customer']) {
			try {
				if (Input::get('what') && Input::get('id')) {
					$backend = $this->bridge->getBackend($this);
			
					$languageSettings = $backend->getLanguages();
			
					$languages = $languageSettings['languages'];
			
					$result = $this->bridge->setLanguages($languages);
			
					$rows = $backend->getRows(Input::get('what'), Input::get('id'));
			
					$this->bridge->setRows($rows);
			
					$this->bridge->sendRows();
					$this->redirect($this->bridge->startEuleoWebsite());
				}
			} catch (Exception $e) {
				$this->error = $e->getMessage();
			}
		}
		

		return parent::generate();
	}
	
	public function compile()
	{
		if ($GLOBALS['TL_CONFIG']['euleo_customer']) {
			$this->Template->userdata = $this->bridge->getCustomerData();
		}
		
		if ($this->error) {
			$this->Template->error = $this->error;
		}
	}
}
