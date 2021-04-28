<?php

namespace App\Commands;

use App\Handlers\CommandHandler;
use App\Ionsfwcord;

class HelpCommand extends _Command implements _Commandable
{
    public static $command = 'help';

    public function handle()
    {
        $commands = '';

        foreach (CommandHandler::$commands as $command) {
            $commands .= '**' . $this->prefix . $command::$command . '**' . "\n";
            $commands .= ' - ' . $command::$description . "\n\n";
        }

        $this->message->channel->sendMessage($commands);
    }
    
    public function hasPermission(): bool
    {
        return true;
    }
}