<?php

namespace App\Commands\Mirror\Channel;

use App\Commands\_Command;
use App\Commands\_Commandable;
use App\Ionsfwcord;
use App\ServerConfigRepository;
use Discord\Http\Exceptions\NoPermissionsException;
use Discord\Parts\Channel\Webhook;
use React\Promise\Promise;

class Set extends _Command implements _Commandable
{
    public static $command = 'mirror:channel:set {Channel EID}';

    public static $description = 'Mirror specified (foreign) channel to current channel';

    public function handle(): Promise
    {
        /**
         * 1. Check server mirroring enabled
         * 2. Check channel mirrorable
         * 3. Create channel webhook
         * 4. Save to config
         */
        return new Promise(function ($resolve) {
            /**
             * Make sure an EID was provided
             */
            try {
                /**
                 * EID format guild_id.channel_id
                 */
                [$guildId, $channelId] = explode('.', explode(' ', $this->message->content)[1]);

                if (!isset($guildId) || !isset($channelId)) {
                    throw new \Exception();
                }
            } catch (\Exception $e) {
                $this->message->channel->sendMessage('Please enter a channel EID.')->then(function () use ($resolve) {
                    $resolve();
                });

                return;
            }

            $ionsfwcord = Ionsfwcord::getInstance();


            /**
             * @var Discord\Parts\Guild\Guild $guild
             */
            $guild = $ionsfwcord->discord->guilds->get('id', $guildId);

            if (!$guild) {
                $this->message->channel->sendMessage('Invalid guild.')->then(function () use ($resolve) {
                    $resolve();
                });

                return;
            }

            /**
             * @var Discord\Parts\Channel\Channel $channel
             */
            $channel = $guild->channels->get('id', $channelId);

            if (!$channel) {
                $this->message->channel->sendMessage('Invalid channel.')->then(function () use ($resolve) {
                    $resolve();
                });

                return;
            }

            $serverConfigRepository = ServerConfigRepository::getInstance();

            /**
             * Server config may not be loaded yet at this point, unlikely, however
             */
            $serverConfigRepository->ensureExists($guild->id);

            /**
             * Server config of the guild to be mirrored
             */
            $serverConfig = &$serverConfigRepository->configs[$guild->id];

            if (!$serverConfig->data->mirrorable) {
                $this->message->channel->sendMessage('This guild is not mirrorable.')->then(function () use ($resolve) {
                    $resolve();
                });

                return;
            }

            if (!isset($serverConfig->data->channels[$channel->id])) {
                $this->message->channel->sendMessage('This channel is not mirrorable.')->then(function () use ($resolve) {
                    $resolve();
                });

                return;
            }

            if (isset($this->serverConfig->data->mirroring[$channelId])) {
                // @TODO check if channel mirror was deleted
                $this->message->channel->sendMessage('This channel is already being mirrored on this guild.')->then(function () use ($resolve) {
                    $resolve();
                });

                return;
            }

            /**
             * Create a webhook for current channel
             */
            $ionsfwcord->discord->http->post('channels/' . $this->message->channel_id . '/webhooks', [
                'name' => 'Ionsfwcord',
                'avatar' => null,
            ])->then(function ($webhookData) use ($resolve, $ionsfwcord, &$serverConfig, $channel) {
                $webhook = new Webhook($ionsfwcord->discord, (array) $webhookData);

                if (!isset($serverConfig->data->mirroredBy[$channel->id])) {
                    $serverConfig->data->mirroredBy[$channel->id] = [];
                }

                $serverConfig->data->mirroredBy[$channel->id][] = [
                    'channelId' => $this->message->channel_id,
                    'guildId' => $this->message->guild_id,
                ];

                $this->serverConfig->data->mirroring[$channel->id] = [
                    'channelId' => $this->message->channel_id,
                    'webhookToken' => $webhook->token,
                    'webhookId' => $webhook->id,
                ];

                $serverConfig->save();

                $this->serverConfig->save();

                $this->message->channel->sendMessage('Channel mirroring set up successfully.')->then(function () use ($resolve) {
                    $resolve();
                });
            })->otherwise(function (NoPermissionsException $e) use ($resolve) {
                $this->message->channel->sendMessage('Missing manage webhook permissions.')->then(function () use ($resolve) {
                    $resolve();
                });
            })->otherwise(function (\Exception $e) use ($resolve) {
                $this->message->channel->sendMessage('Unable to create webhook.')->then(function () use ($resolve) {
                    $resolve();
                });
            });

        });
    }

    public function hasPermission(): bool
    {
        return $this->message->channel->guild->owner_id === $this->message->author->id;
    }
}