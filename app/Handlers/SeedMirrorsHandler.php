<?php

namespace App\Handlers;

use App\ServerConfigRepository;
use Discord\Discord;
use Discord\Http\Exceptions\NoPermissionsException;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Channel\Webhook;
use Discord\Parts\User\User;
use React\Promise\Promise;

class SeedMirrorsHandler extends _Handler
{
    private ServerConfigRepository $serverConfigRepository;

    public function __construct()
    {
        parent::__construct();

        $this->serverConfigRepository = ServerConfigRepository::getInstance();

        $this->ionsfwcord->discord->on('seed_mirrors', function (array $channelIds, Message $message, Discord $discord) {
            $this->handleSeeding($channelIds, $message);
        });
    }

    /**
     * @param string[] $channelIds
     */
    private function handleSeeding(array $channelIds, Message $message)
    {
        return new Promise(function () use ($channelIds, $message) {
            foreach ($channelIds as $channelInfo) {
                $this->handleSingleSeed($channelInfo, $message);
            }
        });
    }

    private function handleSingleSeed($channelInfo, Message $message)
    {
        return new Promise(function () use ($channelInfo, $message) {
            /**
             * Server config of foreign guild may not be loaded yet, ensure it exists
             */
            $this->serverConfigRepository->ensureExists($channelInfo['guildId']);
            
            $webhookInfo = &$this->serverConfigRepository->configs[$channelInfo['guildId']]->data->mirroring[$message->channel_id];

            /**
             * Create webhook object
             */
            $webhook = new Webhook($this->ionsfwcord->discord, [
                "id" => $webhookInfo['webhookId'],
                "token" => $webhookInfo['webhookToken'],
                "channel_id" => $webhookInfo['channelId'],
            ]);

            /**
             * Send webhook
             */
            $webhook->execute([
                'username' =>  $message->author->username . '#' . $message->author->discriminator,
                'avatar_url' => $message->author->user->avatar,
                'content' => $message->content,
            ]);
        });
    }

    /*

    Code below was made to dynamically create/delete webhooks. Turns out you can overwrite username/avatar so this isnt necessary
    Some of this code will be reusable for channel mirror configuration

    private function convertMessage(Message $message)
    {
        return [
            'content' => $message->content,
        ];
    }

    private function getWebhook($channel, $user)
    {
        return new Promise(function ($resolve) use ($channel, $user) {
            $this->ionsfwcord->discord->http->get('channels/' . $channel->id . '/webhooks')->then(function ($res) use ($channel, $user, $resolve) {
                $existingWebhooks = array_map(function ($webhook) {
                    return new Webhook($this->ionsfwcord->discord, (array) $webhook);
                }, $res);
    
                $webhookUsername = $user->username . '#' . $user->discriminator;

                $webhook = (function () use ($existingWebhooks, $webhookUsername) {
                    foreach ($existingWebhooks as $webhook) {
                        if ($webhook->name === $webhookUsername) {
                            return $webhook;
                        }
                    }

                    return null;
                })();

                $resolve($webhook ?? $this->createWebhook($channel, $user, $existingWebhooks));
            });
        });
    }

    private function createWebhook($channel, $user, $existingWebhooks)
    {
        return new Promise(function ($resolve) use ($channel, $user, $existingWebhooks) {
            $this->deleteOldestWebhookIfNecessary($channel, $existingWebhooks)->then(function ($ret) use ($channel, $user, $resolve) {
                $webhookUsername = $user->username . '#' . $user->discriminator;
                
                $this->ionsfwcord->discord->http->post('channels/' . $channel->id . '/webhooks', [
                    'name' => $webhookUsername,
                    'avatar' => null,
                ])->then(function ($webhook) use ($resolve) {
                    $resolve(new Webhook($this->ionsfwcord->discord, (array) $webhook));
                })->otherwise(function (NoPermissionsException $err) use ($channel) {
                    $channel->sendMessage('Unable to create webhooks to mirror messages.');
                });
            });
        });
    }

    private function deleteOldestWebhookIfNecessary($channel, $existingWebhooks)
    {
        return new Promise(function ($resolve) {
            $resolve(true);
        });
    }
    
    */
}
