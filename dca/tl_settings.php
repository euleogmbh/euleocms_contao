<?php

$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] .= ';{euleo_legend},euleo_customer,euleo_usercode,euleo_default_lang,euleo_backend';

$GLOBALS['TL_DCA']['tl_settings']['fields']['euleo_customer'] = array
(
	'inputType' => 'text',
	'label' => &$GLOBALS['TL_LANG']['tl_settings']['euleo_customer'],
	'eval' => array(
		'mandatory' => false,
		'tl_class' => 'w50'
	)
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['euleo_usercode'] = array
(
	'inputType' => 'text',
	'label' => &$GLOBALS['TL_LANG']['tl_settings']['euleo_usercode'],
	'eval' => array(
		'mandatory' => false,
		'tl_class' => 'w50'
	)
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['euleo_backend'] = array
(
	'inputType' => 'select',
	'label' => &$GLOBALS['TL_LANG']['tl_settings']['euleo_backend'],
	'options_callback' => array('euleo_tl_settings', 'getBackends'),
	'eval' => array(
		'mandatory' => false,
		'tl_class' => 'w50 clr',
	)
);



class euleo_tl_settings extends Backend
{
	public function getBackends()
	{
		$files = scandir(dirname(__FILE__) . '/../helpers/Euleo/Backend');
		
		$backends = array();
		foreach ($files as $file) {
			if ($file == '.' || $file == '..') {
				continue;
			}
			
			$name = strtolower(str_replace('.php', '', $file));
			
			$backends[$name] = &$GLOBALS['TL_LANG']['tl_settings']['euleo_backend_' . $name];
		}
		
		return $backends;
	}
}