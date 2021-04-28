<?php

namespace App\Handlers;

use Discord\Discord;

class ReadyHandler extends _Handler
{
    public function __construct()
    {
        parent::__construct();

        $this->ionsfwcord->discord->on('ready', function (Discord $discord) {
            echo "Bot is ready!\n";
        });
    }
}