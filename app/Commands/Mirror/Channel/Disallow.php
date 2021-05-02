<?php

namespace App\Commands\Mirror\Channel;

use App\Commands\_Command;
use App\Commands\_Commandable;
use Discord\Parts\Channel\Channel;
use React\Promise\Promise;

class Disallow extends _Command implements _Commandable
{
    public static $command = 'mirror:channel:disallow #channels';
    public static $description = 'Remove channels from the list of mirrorable channels';

    public function handle(): Promise
    {
        return new Promise(function () {
            /**
             * It shouldnt be possible to tag any channels other than text channels but you never know ¯\_(ツ)_/¯
             */
            $mentionedTextChannels = $this->message->mention_channels->filter(function (Channel $channel) {
                return $channel->type === Channel::TYPE_TEXT;
            });

            /**
             * @var Channel $channel
             */
            foreach ($mentionedTextChannels as $channel) {
                unset($this->serverConfig->data->channels[$channel->id]);
            }

            /**
             * Save configuration
             */
            $this->serverConfig->save();

            $this->message->channel->sendMessage('Disabled channel mirroring for ' . $mentionedTextChannels->count() . ' channel(s)');
        });
    }

    public function hasPermission(): bool
    {
        return $this->message->member->getPermissions()['administrator'];
    }
}