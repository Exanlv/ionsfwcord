<?php

namespace App\Commands;

use App\ServerConfig;
use Discord\Parts\Channel\Message;

abstract class _Command
{
    public static $command = '';
    public static $hidden = '';
    public static $description = 'No description';

    protected string $prefix;
    protected Message $message;

    protected ServerConfig $serverConfig;

    public function __construct(string $prefix, Message $message, ServerConfig &$serverConfig)
    {
        $this->prefix = $prefix;
        $this->message = $message;
        $this->serverConfig = $serverConfig;
    }
}