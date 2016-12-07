<?php
require_once '../lib/vendor/smarty/smarty/libs/Smarty.class.php';

$smarty = new Smarty();

$smarty->setTemplateDir('../www/template');
$smarty->setCompileDir('../www/template_c');
$smarty->setCacheDir('../www/cache');
$smarty->setConfigDir('../www/configs');
$smarty->addPluginsDir('../www/plugins');
