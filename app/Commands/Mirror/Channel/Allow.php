<?php

namespace App\Commands\Mirror\Channel;

use App\Commands\_Command;
use App\Commands\_Commandable;
use App\Helpers\WebhooksHelper;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Webhook;
use React\Promise\Promise;

use function React\Promise\all;

class Allow extends _Command implements _Commandable
{
    public static $command = 'mirror:channel:allow #channels';
    public static $description = 'Allow additional channels to be mirrored';

    public function handle(): Promise
    {
        return new Promise(function ($resolve) {
            /**
             * It shouldnt be possible to tag any channels other than text channels but you never know ¯\_(ツ)_/¯
             */
            $mentionedTextChannels = $this->message->mention_channels->filter(function (Channel $channel) {
                // @TODO filter out already enabled channels
                return $channel->type === Channel::TYPE_TEXT;
            });

            $promises = [];
            $failedWebhooksChannels = [];

            /**
             * @var Channel $channel
             */
            foreach ($mentionedTextChannels as $channel) {
                $promises[] = new Promise(function ($resolve) use ($channel, &$failedWebhooksChannels) {
                    return WebhooksHelper::createWebhook($channel, 'Ionsfwcord')->then(function (Webhook $webhook) use ($resolve) {
                        $this->serverConfig->data->channels[$webhook->channel_id] = [
                            'send_messages' => true,
                            'attach_files' => true,
                            'view' => true,
                            'webhook' => [
                                'id' => $webhook->id,
                                'token' => $webhook->token,
                            ]
                        ];

                        $resolve();
                    })->otherwise(function ($e) use ($channel, &$failedWebhooksChannels) {
                        $failedWebhooksChannels[] = $channel;
                    });
                });
            }

            /**
             * Await creation/failure of webhooks
             */
            all($promises)->then(function () use ($mentionedTextChannels, $failedWebhooksChannels) {
                /**
                 * Save configuration
                 */
                $this->serverConfig->save();

                $message = 'Enabled channel mirroring for ';

                if (count($failedWebhooksChannels)) {
                    $updatedChannelCount = $mentionedTextChannels->count() - count($failedWebhooksChannels);

                    $message .= $updatedChannelCount . ' channel(s)' . PHP_EOL . 'Unable to create webhooks for ';

                    $channelTags = array_map(function (Channel $channel) {
                        return '<#' . $channel->id . '>';
                    }, $failedWebhooksChannels);

                    $message .= implode(', ', $channelTags);
                } else {
                    $message .= $mentionedTextChannels->count() . ' channel(s)';
                }

                $this->message->channel->sendMessage($message);
            });
            
        });
    }

    public function hasPermission(): bool
    {
        return $this->message->member->getPermissions()['administrator'];
    }
}