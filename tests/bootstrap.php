<?php

ini_set('memory_limit', -1);
ini_set('error_reporting', -1);
ini_set('log_errors_max_len', 0);
ini_set('assert.exception', 1);
ini_set('xdebug.show_exception_trace', 0);

if (!($loader = @include __DIR__.'/../vendor/autoload.php')) {
    echo 'You need to install the project dependencies using composer'."\n";
    exit(1);
}

// Cast env vars
$_ENV['IMAP_PORT'] = (int) $_ENV['IMAP_PORT'];
$_ENV['IMAP_FLAGS'] = json_decode($_ENV['IMAP_FLAGS']);
$_ENV['IMAP_LAZY'] = boolval($_ENV['IMAP_LAZY']);

