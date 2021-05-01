<?php

namespace App;

class ServerConfigRepository
{
    private static ?ServerConfigRepository $instance = null;

    /**
     * @var ServerConfig[]
     */
    public array $configs = [];

    private function __construct()
    {
        
    }

    public static function getInstance(): ServerConfigRepository
    {
        if (self::$instance === null) {
            self::$instance = new ServerConfigRepository();
        }

        return self::$instance;
    }

    public function ensureExists(string $guildId): void
    {
        if (!isset($this->configs[$guildId])) {
            $this->configs[$guildId] = new ServerConfig($guildId);
        }
    }
}
