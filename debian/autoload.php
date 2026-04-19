<?php

declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $prefix = 'Igloonet\\MailkitApi\\';
    $prefixLen = strlen($prefix);

    if (strncmp($class, $prefix, $prefixLen) !== 0) {
        return;
    }

    $relative = substr($class, $prefixLen);
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});
