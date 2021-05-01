<?php

namespace App;

use App\Handlers\CommandHandler;
use App\Handlers\MessageHandler;
use App\Handlers\ReadyHandler;
use App\Handlers\SeedMirrorsHandler;
use Dotenv\Dotenv;

include __DIR__.'/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$ionsfwcord = Ionsfwcord::getInstance();

new ReadyHandler();
new MessageHandler();
new CommandHandler();
new SeedMirrorsHandler();

$ionsfwcord->start();
