<?php

return [
    'enable' => env('OPCACHE_ENABLE', true),
    'precompile' => [
        'artisan',
    ],
];
