<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';
use \jimbocoder\DotenvYaml as Env;

Env::load(__DIR__);

// var_dump($_SERVER);
// var_dump($_ENV);

try {
    // Should throw exception
    var_dump(Env::need('not.a.real.key'));
} catch ( \OutOfBoundsException $e) {
    // Good!
}

// Test that ::want() can return a default scalar when keys dont exist
 $default = mt_rand();
if ( $default !== Env::want('not.a.real.key', $default) ) {
    throw new \Exception();
}

// Test that ::want() can return a default array when keys dont exist
 $default = array(time());
if ( $default !== Env::want('not.a.real.key', $default) ) {
    throw new \Exception();
}

// Test that required values don't blow up when they DO exist.
$logLevel = Env::need('tak.logging.defaultLogLevel');

