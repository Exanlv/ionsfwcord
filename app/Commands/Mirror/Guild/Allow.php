<?php

namespace App\Commands\Mirror\Guild;

use App\Commands\_Command;
use App\Commands\_Commandable;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Role;

class Allow extends _Command implements _Commandable
{
    public static $command = 'mirror:guild:allow';
    public static $description = 'Allow current guild to be mirrored';

    public function handle()
    {
        if ($this->serverConfig->data->channels === []) {
            $guild = &$this->message->channel->guild;

            $everyoneRoleId = $guild->roles->find(function (Role $role) {
                return $role->position === 0;
            })->id;

            /**
             * @var Discord\Helpers\Collection<Channel>
             */
            $textChannels = $guild->channels->filter(function (Channel $channel) {
                return $channel->type === Channel::TYPE_TEXT;
            });

            /**
             * @var array[] $channels
             * ["channelId" => ["send_messages" => bool, "view" => bool]][]
             */
            $mirrorableChannels = [];

            /**
             * @var Channel $channel
             * Text Channels
             */
            foreach ($textChannels as $channel) {
                $permissionOverwrites = $channel->overwrites->get('id', $everyoneRoleId);

                /**
                 * Whether @everyone can send messages in this channel
                 */
                $sendMessageAllowed = !$permissionOverwrites->deny->send_messages;

                /**
                 * The line between these is kinda blurred, espescially with how "read_message_history" cant work when mirrored
                 * Hence why these permissions are combined
                 */
                $viewAllowed = !($permissionOverwrites->deny->read_message_history || $permissionOverwrites->deny->view_channel);

                /**
                 * Determine whether this channel should be mirrorable
                 */
                if ($viewAllowed || $sendMessageAllowed) {
                    $mirrorableChannels[$channel->id] = [
                        'send_messages' => $sendMessageAllowed,
                        'attach_files' => !$permissionOverwrites->deny->attach_files,
                        'view' => $viewAllowed,
                    ];
                }
            }

            /**
             * Store mirrorable channels in server config
             */
            $this->serverConfig->data->channels = $mirrorableChannels;

            $additionalMessage = 'All channels available to everyone have been made mirrorable.';
        } else {
            $additionalMessage = 'Mirrorable channels have been restored from previous configuration.';
        }

        /**
         * Enable server mirroring
         */
        $this->serverConfig->data->mirrorable = true;

        $totalMessage = "Server mirroring is now allowed for this guild.\n\n";
        $totalMessage .= $additionalMessage . "\n\n";
        $totalMessage .= 'To add/remove channels from this list, use `mirror:channel:allow/disallow #channel`';

        /**
         * Save configuration
         */
        $this->serverConfig->save();

        $this->message->channel->sendMessage($totalMessage);
    }

    public function hasPermission(): bool
    {
        return $this->message->member->getPermissions()['administrator'];
    }
}