<?php

namespace App\Handlers;

use App\Handlers\_Handler;
use App\Helpers\WebhooksHelper;
use App\Ionsfwcord;
use App\ServerConfigRepository;
use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\Parts\Channel\Webhook;

class FeedMirrorsHandler extends _Handler
{
    private ServerConfigRepository $serverConfigRepository;

    public function __construct()
    {
        parent::__construct();

        $this->serverConfigRepository = ServerConfigRepository::getInstance();

        $this->ionsfwcord->discord->on('feed_mirrors', function ($feedingInfo, Message $message, Discord $discord) {
            $this->handleFeeding($feedingInfo, $message);
        });
    }

    private function handleFeeding($feedingInfo, Message $message)
    {
        /**
         * Guild in which the original message was sent to
         */
        $feederServerConfig = &$this->serverConfigRepository->configs[$message->guild_id];

        $this->serverConfigRepository->ensureExists($feedingInfo['guildId']);

        /**
         * Guild to feed the message into
         */
        $seederServerConfig = &$this->serverConfigRepository->configs[$feedingInfo['guildId']];

        $seederChannelConfig = &$seederServerConfig->data->channels[$feedingInfo['channelId']];

        $mirrorTo = array_filter($seederServerConfig->data->mirroredBy[$feedingInfo['channelId']], function ($channelInfo) use ($message) {
            return $channelInfo['channelId'] !== $message->channel_id;
        });

        $webhooks = array_map(function ($mirrorInfo) use ($feedingInfo) {
            $this->serverConfigRepository->ensureExists($mirrorInfo['guildId']);

            var_dump($this->serverConfigRepository->configs[$mirrorInfo['guildId']]->data->mirroring, $feedingInfo['channelId']);

            $webhookInfo = &$this->serverConfigRepository->configs[$mirrorInfo['guildId']]->data->mirroring[$feedingInfo['channelId']];

            return ['token' => $webhookInfo['webhookToken'], 'id' => $webhookInfo['webhookId']];
        }, $mirrorTo);

        $webhooks[] = $seederChannelConfig['webhook'];

        if (!$seederChannelConfig['send_messages']) {
            return;
        }

        $webhookData = WebhooksHelper::messageToWebhookData($message, $seederChannelConfig['attach_files']);

        $this->sendWebhooks($webhooks, $webhookData);
    }

    private function sendWebhooks(array $webhooks, array $webhookData)
    {
        $discord = &Ionsfwcord::getInstance()->discord;

        foreach ($webhooks as $webhook) {
            $this->sendSingleWebhook(new Webhook($discord, $webhook), $webhookData);
        }
    }

    private function sendSingleWebhook(Webhook $webhook, array $webhookData)
    {
        return $webhook->execute($webhookData);
    }
}