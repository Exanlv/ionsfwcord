<?php

namespace App\Commands\Mirror\Guild;

use App\Commands\_Command;
use App\Commands\_Commandable;
use React\Promise\Promise;

class Allow extends _Command implements _Commandable
{
    public static $command = 'mirror:guild:allow';
    public static $description = 'Allow current guild to be mirrored';

    public function handle(): Promise
    {
        return new Promise(function () {
            /**
             * Enable server mirroring
             */
            $this->serverConfig->data->mirrorable = true;
    
            $totalMessage = "Server mirroring is now allowed for this guild.\n\n";
            $totalMessage .= 'To add/remove channels from this list, use `mirror:channel:allow/disallow #channel`';
    
            /**
             * Save configuration
             */
            $this->serverConfig->save();
    
            $this->message->channel->sendMessage($totalMessage);
        });
    }

    public function hasPermission(): bool
    {
        return $this->message->member->getPermissions()['administrator'];
    }
}