<?php

namespace App;

use Discord\Discord;

class Ionsfwcord
{
    private static ?IonsfwCord $instance = null;

    public Discord $discord;

    private function __construct()
    {
        $this->discord = new Discord([
            'token' => $_ENV['DISCORD_TOKEN'],
            'logger' => new \Psr\Log\NullLogger(),
        ]);
    }

    public static function getInstance(): Ionsfwcord
    {
        if (self::$instance === null) {
            self::$instance = new IonsfwCord();
        }

        return self::$instance;
    }

    public function start()
    {
        $this->discord->run();
    }
}