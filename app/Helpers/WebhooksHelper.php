<?php

namespace App\Helpers;

use App\Ionsfwcord;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Channel\Webhook;

class WebhooksHelper
{
    private static function getDiscord()
    {
        return Ionsfwcord::getInstance()->discord;
    }

    public static function createWebhook(Channel $channel, string $name, ?string $avatar = null)
    {
        $discord = self::getDiscord();
        
        return $discord->http->post('channels/' . $channel->id . '/webhooks', [
            'name' => $name,
            'avatar' => $avatar
        ])->then(function ($result) use (&$discord) {
            return new Webhook($discord, (array) $result);
        });
    }

    public static function messageToWebhookData(Message $message, $allowAttachments = false)
    {
        $webhookData = [
            'username' =>  $message->author->username . '#' . $message->author->discriminator,
            'avatar_url' => $message->author->user->avatar,
            'content' => $message->content,
        ];

        $webhookData['embed'] = array_map(function ($attachment) {
            return ['image' => ['url' => $attachment->proxy_url]];
        }, $message->attachments);

        return $webhookData;
    }
}