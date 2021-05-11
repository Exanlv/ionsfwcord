<?php

namespace App\Handlers;

use App\Helpers\OptimizedMirrorHelper;
use App\Helpers\SendWebhooksHelper;
use App\Helpers\WebhooksHelper;
use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\Parts\Channel\Webhook;

class MirrorHandler extends _Handler
{
    public function __construct()
    {
        parent::__construct();

        $this->ionsfwcord->discord->on('mirror', function ($mirrorConfig, Message $message, Discord $discord) {
            $this->mirror($mirrorConfig, $message, $discord);
        });
    }

    private function mirror($mirrorConfig, Message $message, Discord $discord)
    {
        $webhooksData = WebhooksHelper::messageToWebhookData($message, $mirrorConfig['mirrorAttachments']);

        foreach ($mirrorConfig['mirrorTo'] as $channel) {
            if (!isset(OptimizedMirrorHelper::$optimizedWebhookConfig[$channel])) {
                continue;
            }

            $webhookConfig = &OptimizedMirrorHelper::$optimizedWebhookConfig[$channel];

            $webhook = new Webhook($discord, [
                'token' => $webhookConfig['wht'],
                'id' => $webhookConfig['whid'],
            ]);

            new SendWebhooksHelper($webhook, $webhooksData);
        }
    }
}