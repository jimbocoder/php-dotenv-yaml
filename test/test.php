<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

\jimbocoder\DotenvYaml::load(__DIR__);

var_dump($_SERVER);
var_dump($_ENV);

