<?php

namespace App\Commands;

use React\Promise\Promise;

class PingCommand extends _Command implements _Commandable
{
    public static $command = 'ping';

    public function handle(): Promise
    {
        return new Promise(function () {
            throw new \Exception('asdfsadf');
            $this->message->channel->sendMessage('Pong!');
        });
    }

    public function hasPermission(): bool
    {
        return true;
    }
}