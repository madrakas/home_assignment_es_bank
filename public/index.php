<?php

use Bank\App\App;

require '../vendor/autoload.php';
define('ROOT', __DIR__ . '/../');
define('URL', 'http://easyseo.bit');
echo App::run();

?>
