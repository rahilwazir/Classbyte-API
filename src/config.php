<?php

//Resolve directory slashes compatiblity issues
$document_root = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, dirname(__DIR__)) . DIRECTORY_SEPARATOR;

// Document Root
define("ROOT_PATH", $document_root);

// src Directory
define("SRC_PATH", ROOT_PATH . 'src' . DIRECTORY_SEPARATOR);

// API URL
define("ROOT_URL", '/api/');