<?php
// configuration
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.php';

// DB Connection
require_once '../../admin/src/RWDB.php';

// helpers
require_once SRC_PATH . 'helpers.php';

//Symfony Autoloder
require_once __DIR__.'/Symfony/Component/ClassLoader/UniversalClassLoader.php';

use Symfony\Component\ClassLoader\UniversalClassLoader;

$loader = new UniversalClassLoader();
$loader->register();
$loader->registerNamespace("CB_API", __DIR__);