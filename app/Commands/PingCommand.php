<?php

namespace App\Commands;

class PingCommand extends _Command implements _Commandable
{
    public static $command = 'debug:ping';

    public function handle()
    {
        $this->message->channel->sendMessage('Pong!');
    }

    public function hasPermission(): bool
    {
        return true;
    }
}