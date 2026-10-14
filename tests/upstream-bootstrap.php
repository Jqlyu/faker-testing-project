<?php
// Only the upstream runner uses this. No upstream test or source is edited.
require dirname(__DIR__) . '/vendor/autoload.php';
date_default_timezone_set('UTC');
spl_autoload_register(function ($class) {
    $prefix = 'Faker\\Test\\';
    if (strpos($class, $prefix) === 0) {
        $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
        $file = dirname(__DIR__) . '/upstream/faker/test/Faker/' . $relative . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});
