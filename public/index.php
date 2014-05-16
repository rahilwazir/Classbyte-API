<?php
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src/autoload.php';

use CB_API\Route;

disable_errors();

$route = new Route();
$route->callee();