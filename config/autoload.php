<?php
// $GLOBALS['TL_CONFIG']['displayErrors'] = true;

ClassLoader::addClasses(array
(
	'ModuleEuleo' => 'system/modules/euleo/modules/ModuleEuleo.php',
	'Euleo_Contao' => 'system/modules/euleo/helpers/Euleo/Contao.php',
	'Euleo_Cms' => 'system/modules/euleo/helpers/Euleo/Cms.php',
	'Euleo_Backend' => 'system/modules/euleo/helpers/Euleo/Backend.php',
	'Euleo_Backend_Dca' => 'system/modules/euleo/helpers/Euleo/Backend/Dca.php',
	'Euleo_Dma' => 'system/modules/euleo/helpers/Euleo/Dma.php',
));


TemplateLoader::addFiles(array
(
	'config'   => 'system/modules/euleo/templates',
	'help'   => 'system/modules/euleo/templates',
	'error'   => 'system/modules/euleo/templates',
));