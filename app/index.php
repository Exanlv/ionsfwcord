<?php

namespace App;

use App\Handlers\ReadyHandler;
use Dotenv\Dotenv;

include __DIR__.'/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$ionsfwcord = Ionsfwcord::getInstance();

new ReadyHandler();

$ionsfwcord->start();
