<?php

use Bank\App\App;

// session_start(); 
require '../vendor/autoload.php';

define('ROOT', __DIR__ . '/../');
define('URL', 'http://easyseo.bit');
echo App::run();

?>