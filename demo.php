<?php

require_once 'vendor/autoload.php';

use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;
use kafene\Monolog\Handler\PdoHandler;

$logger = new Logger('pdo');

$logger->pushProcessor(new IntrospectionProcessor());

$pdo = new PDO('sqlite:log.sqlite', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$logger->pushHandler(new PdoHandler($pdo));

$logger->warning('this is a warning');
