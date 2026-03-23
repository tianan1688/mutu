<?php
return [
    'default' => env('CACHE_DRIVER', 'file'),
    'stores'  => [
        'file' => [
            'type' => 'File',
            'path' => '../runtime/cache/',
        ],
        'redis' => [
            'type' => 'redis',
            'host' => env('REDIS.HOST', '127.0.0.1'),
            'port' => env('REDIS.PORT', 6379),
            'password' => env('REDIS.PASSWORD', ''),
            'select' => env('REDIS.SELECT', 0),
            'timeout' => 0,
            'expire' => 0,
            'persistent' => false,
            'prefix' => 'mutu:',
        ],
    ],
];