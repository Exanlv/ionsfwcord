<?php

namespace App\Handlers;

use App\Exceptions\CommandNotFoundException;
use App\ServerConfig;
use Discord\Discord;
use Discord\Parts\Channel\Message;

class CommandHandler extends _Handler
{

    public static $commands = [
        \App\Commands\PingCommand::class,
        \App\Commands\HelpCommand::class,
        \App\Commands\Mirror\Guild\Allow::class,
        \App\Commands\Mirror\Guild\Disallow::class,
        \App\Commands\Mirror\Channel\Allow::class,
        \App\Commands\Mirror\Channel\Disallow::class,
    ];

    private array $commandNamespaces = [];

    private array $serverConfigs = [];

    public function __construct()
    {
        parent::__construct();

        $this->populate();

        $this->ionsfwcord->discord->on('command', function (string $prefix, Message $message, Discord $discord) {
            $this->handleIncomingCommand($prefix, $message);
        });
    }

    private function populate()
    {
        foreach (self::$commands as $command) {
            $trigger = explode(' ', $command::$command)[0];

            $splitTrigger = explode(':', $trigger);

            $this->registerNamespace($splitTrigger, $this->commandNamespaces, $command);
        }
    }

    private function registerNamespace($triggerParts, &$namespaceArray, $command)
    {
        if (!isset($namespaceArray[$triggerParts[0]])) {
            if (count($triggerParts) === 1) {
                $namespaceArray[$triggerParts[0]] = $command;

                return;
            } else {
                $namespaceArray[$triggerParts[0]] = [];
            }
        } elseif (!is_array($namespaceArray[$triggerParts[0]])) {
            throw new \Exception("Conflicting command namespaces, $command & " . $namespaceArray[$triggerParts[0]]);
        }

        $namespaceArrayReference = &$namespaceArray[$triggerParts[0]];

        array_shift($triggerParts);

        $this->registerNamespace($triggerParts, $namespaceArrayReference, $command);
    }

    private function handleIncomingCommand(string $prefix, Message $message)
    {
        $command = explode(' ', $message->content)[0];

        if ($command === '') {
            return;
        }

        $commandParts = explode(':', $command);

        try {
            $commandClass = $this->getCommand($commandParts, $this->commandNamespaces);
        } catch (CommandNotFoundException $e) {
            echo "Unkown command `" . $command . "`\n"; // @TODO
            
            return;
        }



        if (!isset($this->serverConfigs[$message->guild_id])) {
            $this->serverConfigs[$message->guild_id] = new ServerConfig($message->guild_id);
        }

        $commandObj = new $commandClass($prefix, $message, $this->serverConfigs[$message->guild_id]);

        if ($commandObj->hasPermission()) {
            $commandObj->handle()->otherwise(function (\Exception $e) {
                var_dump($e);
            });
        } else {
            echo "No permissions\n"; // @TODO
        }
    }

    /**
     * @throws CommandNotFoundException
     */
    private function getCommand(array $commandParts, array $namespaceArray): string
    {
        if (!isset($namespaceArray[$commandParts[0]])) {
            throw new CommandNotFoundException();
        }

        if (count($commandParts) === 1) {
            if (!is_string($namespaceArray[$commandParts[0]])) {
                throw new CommandNotFoundException();
            } else {
                return $namespaceArray[$commandParts[0]];
            }
        } else {
            $namespaceArrayReference = &$namespaceArray[$commandParts[0]];

            array_shift($commandParts);

            return $this->getCommand($commandParts, $namespaceArrayReference);
        }
    }
}