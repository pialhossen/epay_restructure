<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('check.admin', function ($admin) {

    return true;
}, ['guards' => ['admin']]);