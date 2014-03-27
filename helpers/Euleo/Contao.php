<?php
use Contao\Database;
use Contao\DataContainer;
use Contao\System;
use Contao\Environment;
use Contao\Config;
/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/
/**
 * Contao-Bridge for the Euleo-CMS SOAP-Client
 * @author Euleo GmbH
 */
class Euleo_Contao
{
	/**
	 * @var array
	 */
	protected $rows = array();
	
	/**
	 * @var Euleo_Cms
	 */
	protected $cms = null;
	
	/**
	 * @var Config
	 */
	protected $config = null;
	
	/**
	 * @var string
	 */
	protected $callbackUrl = '';
	
	/**
	 * @var Euleo_Backend
	 */
	protected $backend = null;
	
	public function __construct() {
		// void
	}
	
	public function addRow( $row ) {
		$this->rows[] = $row;
	}
	
	public function setRows($rows) {
		$this->rows = $rows;
	}
	
	public function output() {
		if ( ! $this->rows ) {
			throw new Exception( 'No rows found. Did you select entries?' );
		}
		
		foreach ( $this->rows as $row ) {
			echo '<pre>';
			print_r( $row );
			echo '</pre>';
		}
	}
	
	public function connect() {
		$customer = $GLOBALS['TL_CONFIG']['euleo_customer'];
		$usercode = $GLOBALS['TL_CONFIG']['euleo_usercode'];
		
		try {
			$this->cms = new Euleo_Cms($customer, $usercode, 'contao');
			
			return $this->cms->connected();
		} catch (Exception $e) {
			$this->deleteConfig();
			
			throw $e;
		}
	}
	
	public function sendRows() {
		if ( ! $this->rows ) {
			throw new Exception( 'No rows found. Did you select entries?' );
		}
		
		if ( ! $this->cms ) {
			$this->connect();
		}
		
		$response = $this->cms->sendRows( $this->rows );
	}
	
	public function startEuleoWebsite() {
		return $this->cms->startEuleoWebsite();
	}
	
	public function getRows($translationIdList) {
		if ( ! $this->cms ) {
			$this->connect();
		}
		
		$rows = $this->cms->getRows($translationIdList);
		
		return $rows;
	}
	
	public function confirmDelivery( $ids ) {
		$this->cms->confirmDelivery( $ids );
	}
	
	public function getCart() {
		return $this->cms->getCart();
	}
	
	public function setLanguages($languages) {
		$languageList = implode( ',', array_keys( $languages ) );
		return $this->cms->setLanguageList( $languageList );
	}
	
	public function addLanguage( $code, $language ) {
		return $this->cms->addLanguage( $code, $language );
	}
	
	public function removeLanguage( $code, $language ) {
		return $this->cms->removeLanguage( $code, $language );
	}
	
	public function getBackend(Controller $module)
	{
		if ($this->backend instanceof Euleo_Backend) {
			return $this->backend;
		}
		
		$strName = 'Euleo_Backend_' . ucfirst($GLOBALS['TL_CONFIG']['euleo_backend']);
		
		if (class_exists($strName)) {
			$this->backend = new $strName($module, $this);
		}
		
		return $this->backend;
	}
	
	
	// vv TODO vv
	
	public function getRegisterToken() {
		if (!$this->cms) {
			$this->connect();
		}
		
		$cmsroot = 'http://' . Environment::getInstance()->httpHost . TL_PATH;
		$returnUrl = $cmsroot . '/contao/main.php?do=euleo';
		
		$token = $this->cms->getRegisterToken( $cmsroot, $returnUrl );
		
		Config::getInstance()->add("\$GLOBALS['TL_CONFIG']['euleo_install_token']", $token);
		
		return $token;
	}
	
	public function install() {
		if (!$this->cms) {
			$this->connect();
		}
		
		$this->deleteConfig();
		
		$data = $this->cms->install($GLOBALS['TL_CONFIG']['euleo_install_token']);
		
		Config::getInstance()->delete("\$GLOBALS['TL_CONFIG']['euleo_install_token']");
		
		Config::getInstance()->add("\$GLOBALS['TL_CONFIG']['euleo_customer']", $data['customer']);
		Config::getInstance()->add("\$GLOBALS['TL_CONFIG']['euleo_usercode']", $data['usercode']);
		Config::getInstance()->add("\$GLOBALS['TL_CONFIG']['euleo_backend']", 'dca');
		
		Config::getInstance()->save();
		
		return true;
	}
	
	public function uninstall() {
		$this->deleteConfig();
	}
	
	public function getCustomerData() {
		try {
			$this->connect();
			
			return $this->cms->getCustomerData();
		} catch (Exception $e) {
			$this->deleteConfig();
		}
	}
	
	protected function deleteConfig()
	{
		Config::getInstance()->delete("\$GLOBALS['TL_CONFIG']['euleo_customer']");
		Config::getInstance()->delete("\$GLOBALS['TL_CONFIG']['euleo_usercode']");
		Config::getInstance()->delete("\$GLOBALS['TL_CONFIG']['euleo_backend']");
		
		Config::getInstance()->save();
	}
}




// FIXME: debug
ini_set('max_execution_time', 15);

function de($var)
{
	echo '<pre>';
	print_r($var);
	echo '</pre>';
}