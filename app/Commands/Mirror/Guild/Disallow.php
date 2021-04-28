<?php

namespace App\Commands\Mirror\Guild;

use App\Commands\_Command;
use App\Commands\_Commandable;

class Disallow extends _Command implements _Commandable
{
    public static $command = 'mirror:guild:disallow';
    public static $description = 'Disallow current guild to be mirrored';

    public function handle()
    {
        $this->serverConfig->data->mirrorable = false;

        $this->serverConfig->save();

        $this->message->channel->sendMessage('Server mirroring is now disallowed for this guild.');
    }

    public function hasPermission(): bool
    {
        return $this->message->member->getPermissions()['administrator'];
    }
}