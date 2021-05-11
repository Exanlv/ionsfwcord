<?php

namespace App\Helpers;

use App\ServerConfigRepository;

class OptimizedMirrorHelper
{
    public static $optimizedConfig = [];

    private static $wipOptimizedConfig = [];

    public static $optimizedWebhookConfig = [];

    private static $wipOptimizedWebhookConfig = [];

    public static function generateOptimizedMirrorConfig()
    {
        $serverConfigRepository = ServerConfigRepository::getInstance();

        foreach ($serverConfigRepository->configs as $guildConfig) {
            foreach ($guildConfig->data->mirroredBy as $channelId => $mirrors) {
                /**
                 * Ids of the slave channels
                 * @var string[]
                 */
                $mirrorChannelIds = array_map(function ($mirror) {
                    return $mirror['channelId'];
                }, $mirrors);

                /**
                 * Whether attachments from the slave should be mirrored back to the master
                 * @var bool
                 */
                $mirrorAttachments = $guildConfig->data->channels[$channelId]["attach_files"];

                /**
                 * Whether messages from slave chats should be mirrored back to the master
                 * @var bool
                 */
                $returnMessages = $guildConfig->data->channels[$channelId]["send_messages"];

                if (!$returnMessages) {
                    self::$wipOptimizedConfig[$channelId] = [
                        'mirrorAttachments' => true,
                        'mirrorTo' => $mirrorChannelIds
                    ];

                    continue;
                }

                $hivemind = array_merge([(string) $channelId], $mirrorChannelIds);

                foreach ($hivemind as $hmChannelId) {
                    self::$wipOptimizedConfig[$hmChannelId] = [
                        'mirrorAttachments' => ($mirrorAttachments || $hmChannelId === (string) $channelId),
                        'mirrorTo' => array_filter($hivemind, function ($channelId) use ($hmChannelId) {
                            return $channelId !== $hmChannelId;
                        })
                    ];
                }

            }
        }

        self::$optimizedConfig = self::$wipOptimizedConfig;
        self::$wipOptimizedConfig = [];
    }

    public static function generateOptimizedWebhookConfig()
    {
        $serverConfigRepository = ServerConfigRepository::getInstance();

        foreach ($serverConfigRepository->configs as $guildConfig) {
            foreach ($guildConfig->data->mirroring as $mirroring) {
                self::$wipOptimizedWebhookConfig[$mirroring['channelId']] = [
                    'whid' => $mirroring['webhookId'],
                    'wht' => $mirroring['webhookToken'],
                ];
            }

            foreach ($guildConfig->data->channels as $channelId => $channelConfig) {
                self::$wipOptimizedWebhookConfig[$channelId] = [
                    'whid' => $channelConfig['webhook']['id'],
                    'wht' => $channelConfig['webhook']['token'],
                ];
            }
        }

        self::$optimizedWebhookConfig = self::$wipOptimizedWebhookConfig;
        self::$wipOptimizedWebhookConfig = [];
    }
}