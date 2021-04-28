<?php

namespace App\Commands;

interface _Commandable
{
    public function handle();
    public function hasPermission();
}