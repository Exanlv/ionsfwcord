<?php

namespace App\Commands;

use App\Handlers\CommandHandler;
use React\Promise\Promise;

class HelpCommand extends _Command implements _Commandable
{
    public static $command = 'help';

    public function handle(): Promise
    {
        return new Promise(function () {
            $commands = '';

            foreach (CommandHandler::$commands as $command) {
                $commands .= '**' . $this->prefix . $command::$command . '**' . "\n";
                $commands .= ' - ' . $command::$description . "\n\n";
            }
    
            $this->message->channel->sendMessage($commands);
        });
    }
    
    public function hasPermission(): bool
    {
        return true;
    }
}