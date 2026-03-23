<?php
return [
    'default'         => 'mysql',
    'connections'     => [
        'mysql' => [
            'type'      => env('DATABASE.TYPE', 'mysql'),
            'hostname'  => env('DATABASE.HOSTNAME', '127.0.0.1'),
            'database'  => env('DATABASE.DATABASE', 'mutu'),
            'username'  => env('DATABASE.USERNAME', 'mutu'),
            'password'  => env('DATABASE.PASSWORD', 'mutu888'),
            'hostport'  => env('DATABASE.HOSTPORT', '3306'),
            'params'    => [],
            'charset'   => env('DATABASE.CHARSET', 'utf8mb4'),
            'prefix'    => env('DATABASE.PREFIX', ''),
            'deploy'    => 0,
            'rw_separate' => false,
        ],
        // 可以添加其他连接
    ],
];