<?php
use Contao\Frontend;
use Contao\Input;
define('TL_MODE', 'FE');
require '../../../initialize.php';

class Euleo_Callback extends Frontend
{
	public function callback($translationIdList)
	{
		$bridge = new Euleo_Contao();
		
		if ($GLOBALS['TL_CONFIG']['euleo_install_token']) {
			return $bridge->install();
		} else {
			
			$rows = $bridge->getRows($translationIdList);
			
			$backend = $bridge->getBackend($this);
			
			return $backend->callback($rows);
		}
	}
}


$callback = new Euleo_Callback();


if ($callback->callback(Input::get('translationIdList'))) {
	echo 'callback response';
}
