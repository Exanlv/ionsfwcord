<?php

namespace App\Commands\Mirror\Channel;

use App\Commands\_Command;
use App\Commands\_Commandable;
use Discord\Parts\Channel\Channel;
use React\Promise\Promise;

class Allow extends _Command implements _Commandable
{
    public static $command = 'mirror:channel:allow #channels';
    public static $description = 'Allow additional channels to be mirrored';

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
                $this->serverConfig->data->channels[$channel->id] = [
                    'send_messages' => true,
                    'attach_files' => true,
                    'view' => true,
                ];
            }

            /**
             * Save configuration
             */
            $this->serverConfig->save();

            $this->message->channel->sendMessage('Enabled/updated channel mirroring for ' . $mentionedTextChannels->count() . ' channel(s)');
        });
    }

    public function hasPermission(): bool
    {
        return $this->message->member->getPermissions()['administrator'];
    }
}