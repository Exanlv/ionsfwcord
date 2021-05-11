<?php

namespace App\Handlers;

use App\Helpers\SendWebhooksHelper;
use App\Helpers\WebhooksHelper;
use App\ServerConfigRepository;
use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\Parts\Channel\Webhook;
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
        return new Promise(function ($resolve) use ($channelInfo, $message) {
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

            $webhooksData = WebhooksHelper::messageToWebhookData($message);

            new SendWebhooksHelper($webhook, $webhooksData);

            $resolve();
        });
    }
}
