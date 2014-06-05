<?php
namespace CB_API;

if (!session_id()) {
    session_start();    
}

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src/autoload.php';

#disable_errors(true);

$route = new Route();
$route->callee();