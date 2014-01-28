<?php

if ($GLOBALS['TL_CONFIG']['euleo_customer'] && $GLOBALS['TL_CONFIG']['euleo_usercode']) {
	$GLOBALS['TL_DCA']['tl_page']['list']['operations']['translate'] = array
	(
		'label' => &$GLOBALS['TL_LANG']['tl_euleo']['translate'],
		'href'  => 'do=euleo&what=tl_page',
		'icon'  => 'system/modules/euleo/assets/icon.gif',
		'attributes' => 'target="euleoCart"',
	);
}

// TODO: bei nicht übersetzbaren ausblenden statt fehlermeldung

$GLOBALS['TL_DCA']['tl_page']['fields']['title']['multilang'] = true;
$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['multilang'] = true;
$GLOBALS['TL_DCA']['tl_page']['fields']['pageTitle']['multilang'] = true;
$GLOBALS['TL_DCA']['tl_page']['fields']['description']['multilang'] = true;
