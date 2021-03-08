<?php

spl_autoload_register(function ($class) {
    $path = $class . '.php';

    $path = str_replace('\\', '/', $path);

    if (!file_exists($path)) exit(99);

    include_once $path;
});