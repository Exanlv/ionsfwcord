<?php

namespace App\Handlers;

use App\Ionsfwcord;

class _Handler
{
    protected Ionsfwcord $ionsfwcord;

    public function __construct()
    {
        $this->ionsfwcord = Ionsfwcord::getInstance();
    }
}