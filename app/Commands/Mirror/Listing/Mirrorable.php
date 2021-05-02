<?php

namespace App\Commands\Mirror\Listing;

use App\Commands\_Command;
use App\Commands\_Commandable;
use App\Ionsfwcord;
use App\ServerConfigRepository;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Guild\Guild;
use React\Promise\Promise;

class Mirrorable extends _Command implements _Commandable
{
    public static $command = 'mirror:list {Guild ID}';

    public static $description = 'List mirrorable channels in a guild.';

    public function handle(): Promise
    {
        return new Promise(function ($resolve) {
            /**
             * Make sure a guild ID was provided
             */
            try {
                $guildId = explode(' ', $this->message->content)[1];
            } catch (\Exception $e) {
                $this->message->channel->sendMessage('Please enter a guild ID.')->then(function () use ($resolve) {
                    $resolve();
                });

                return;
            }

            $ionsfwcord = Ionsfwcord::getInstance();

            /**
             * @var Guild $guild
             */
            $guild = $ionsfwcord->discord->guilds->find(function (Guild $guild) use ($guildId) {
                return $guild->id === $guildId;
            });

            if ($guild === null) {
                $this->message->channel->sendMessage('Bot is either not in guild or guild is unavailable due to an outage.')->then(function () use ($resolve) {
                    $resolve();
                });

                return;
            }

            $ionsfwcord->discord->http->get('guilds/' . $guild->id . '/members/' . $this->message->author->id)->then(function () use ($guild, &$ionsfwcord, $resolve) {
                /**
                 * This promise returns a member stdClass that can be converted to a member object, this is not needed and thus left out to save performance
                 */
                 
                $serverConfigRepository = ServerConfigRepository::getInstance();

                /**
                 * Server config may not be loaded yet
                 */
                $serverConfigRepository->ensureExists($guild->id);

                $serverConfig = &$serverConfigRepository->configs[$guild->id];

                $embed = [
                    'title' => 'Mirrorable channels in ' . $guild->name,
                    'thumbnail' => $guild->icon,
                    'description' => 'Server mirroring enabled: ' . (string) $serverConfig->data->mirrorable,
                    'color' => $_ENV['EMBED_COLOR'],
                    'fields' => [],
                ];

                /**
                 * If a channel is deleted it may still be in the config, if this is true the server config needs to be saved later
                 */
                $dirty = false;

                foreach ($serverConfig->data->channels as $channelId => $config) {
                    /**
                     * @var Discord\Parts\Channel\Channel $channel
                     */
                    $channel = $guild->channels->get('id', $channelId);

                    /**
                     * Channel in config but no longer exists, should be deleted
                     */
                    if ($channel === null) {
                        unset($serverConfig->data->channels[$channelId]);

                        $dirty = true;

                        continue;
                    }

                    $value  = 'Sending messages: ' . ($config['send_messages'] ? 'True' : 'False') . PHP_EOL;
                    $value .= 'Attach files: ' . ($config['send_messages'] ? 'True' : 'False') . PHP_EOL;
                    $value .= 'Channel EID: ' . $guild->id . '.' . $channel->id;

                    $embed['fields'][] = [
                        'name' => $channel->name,
                        'value' => $value,
                    ];
                }

                /**
                 * Save server config in case a channel had to be deleted
                 */
                if ($dirty) {
                    $serverConfig->save();
                }

                $finalEmbed = new Embed($ionsfwcord->discord, $embed);

                $this->message->channel->sendEmbed($finalEmbed)->then(function () use ($resolve) {
                    $resolve();
                });
            })->otherwise(function ($err) use ($resolve) {
                /**
                 * If the member is not in a guild the HTTP call returns a 404, causing an error to be thrown
                 */
                $this->message->channel->sendMessage('You are not in this guild.')->then(function () use ($resolve) {
                    $resolve();
                });
            });
        });
    }

    public function hasPermission(): bool
    {
        return true;
    }
}