<?php

namespace App\Handlers;

use App\ServerConfigRepository;
use Discord\Discord;
use Discord\Parts\Channel\Message;

class MessageHandler extends _Handler
{
    private static string $prefix = '!!';

    private ServerConfigRepository $serverConfigRepository;

    public function __construct()
    {
        parent::__construct();

        $this->serverConfigRepository = ServerConfigRepository::getInstance();

        
        $this->ionsfwcord->discord->on('message', function (Message $message, Discord $discord) {

            $this->serverConfigRepository->ensureExists($message->guild_id);

            if (str_starts_with($message->content, self::$prefix)) {
                $message->content = substr($message->content, strlen(self::$prefix));

                $discord->emit('command', [self::$prefix, $message, $discord]);

                return;
            } elseif (isset($this->serverConfigRepository->configs[$message->guild_id]->data->mirroredBy[$message->channel_id])) {
                $discord->emit('seed_mirrors', [
                    $this->serverConfigRepository->configs[$message->guild_id]->data->mirroredBy[$message->channel_id],
                    $message,
                    $discord,
                ]);
            }
        });
    }
}