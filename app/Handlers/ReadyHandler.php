<?php

namespace App\Handlers;

use App\Helpers\OptimizedMirrorHelper;
use Discord\Discord;

class ReadyHandler extends _Handler
{
    public function __construct()
    {
        parent::__construct();

        $this->ionsfwcord->discord->on('ready', function (Discord $discord) {
            echo "Generating optimized mirror config...", PHP_EOL;

            $mirrorTime = -microtime(true);
            OptimizedMirrorHelper::generateOptimizedMirrorConfig();
            $mirrorTime += microtime(true);

            echo 'Generating optimized mirror config complete, time ', $mirrorTime, PHP_EOL, PHP_EOL;

            echo 'Generating optimized webhook config...', PHP_EOL;

            $webhookTime = -microtime(true);
            OptimizedMirrorHelper::generateOptimizedWebhookConfig();
            $webhookTime += microtime(true);

            echo 'Generating optimized webhook config complete, time ', $webhookTime, PHP_EOL, PHP_EOL;

            new MessageHandler();
            new CommandHandler();
            new MirrorHandler();

            echo 'Bot is ready!', PHP_EOL;
        });
    }
}