<?php

namespace App\Commands;

use React\Promise\Promise;

interface _Commandable
{
    public function handle(): Promise;
    public function hasPermission();
}