<?php

namespace App;

class ServerConfig
{
    private string $filePath;
    private string $guildId;
    public Config $data;

    public function __construct(string $guildId)
    {
        $this->guildId = $guildId;
        $this->filePath = $_ENV['CONFIG_STORAGE'] . '/' . $this->guildId;

        $this->load();
    }

    public function save()
    {
        file_put_contents($this->filePath, json_encode($this->data));
    }

    private function load()
    {
        $this->data = new Config();
        
        if (file_exists($this->filePath)) {
            $properties = json_decode(file_get_contents($this->filePath), true);

            foreach ($properties as $key => $value) {
                $this->data->{$key} = $value;
            }
        } else {
            $this->save();
        }
    }
}

class Config
{
    /**
     * Config version, used to potentially migrate configs later on
     */
    public string $v = '1';

    /**
     * Whether a guild can be mirrored
     */
    public bool $mirrorable = false;

    /**
     * Channels allowed to be mirrored in the guild
     * ["channelId" => ["send_message" => bool, "view" => bool]][]
     * @var array[]
     */
    public array $channels = [];
}