<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';
use \jimbocoder\DotenvYaml as Env;

Env::load(__DIR__);

var_dump($_SERVER);
var_dump($_ENV);

var_dump(Env::get('not.a.real.key', 'Default Scalar'));
var_dump(Env::get('not.a.real.key2', array('default array')));
var_dump(Env::get('not.a.real.key3'));
var_dump(Env::get('tak.logging.defaultLogLevel'));
var_dump(Env::get('tak.logging'));

