<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
date_default_timezone_set('UTC');
if (setlocale(LC_ALL, 'C') === false) {
    throw new RuntimeException('Could not select the controlled C locale.');
}
