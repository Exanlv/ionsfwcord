<?php

namespace App\Helpers;

use Discord\Parts\Channel\Webhook;

class SendWebhooksHelper
{
    private Webhook $webhook;
    private array $webhooksData;

    public function __construct(Webhook $webhook, array $webhooksData)
    {
        $this->webhook = $webhook;
        $this->webhooksData = $webhooksData;

        if (count($webhooksData)) {
            $this->sendWebhook();
        }
    }

    private function sendWebhook()
    {
        return $this->webhook->execute($this->webhooksData[0])->then(function () {
            array_shift($this->webhooksData);

            if (count($this->webhooksData)) {
                $this->sendWebhook();
            }
        });
    }
}