<?php

if (!is_file($autoloadFile = __DIR__.'/../vendor/autoload.php')) {
    throw new \RuntimeException('Must install dependencies to run test suite.');
}

require_once $autoloadFile;