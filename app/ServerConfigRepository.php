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
        $this->loadAll();
    }

    private function loadAll()
    {
        $ionsfwcord = Ionsfwcord::getInstance();

        /**
         * @var \Discord\Parts\Guild\Guild $guild
         */
        foreach ($ionsfwcord->discord->guilds as $guild) {
            $this->ensureExists($guild->id);
        }
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
