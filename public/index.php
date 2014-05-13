<?php
session_start();

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src/autoload.php';

use CB_API\Route;

$route = new Route();
$route->callee();