<?php

namespace App\Handlers;

use Discord\Discord;
use Discord\Parts\Channel\Message;

class MessageHandler extends _Handler
{
    private static string $prefix = '!!';

    public function __construct()
    {
        parent::__construct();

        $this->ionsfwcord->discord->on('message', function (Message $message, Discord $discord) {
            if (str_starts_with($message->content, self::$prefix)) {
                $message->content = substr($message->content, strlen(self::$prefix));

                $discord->emit('command', [self::$prefix, $message, $discord]);

                return;
            }
        });
    }
}