<?php

declare(strict_types=1);

use Illuminate\Support\Env;

return [
    'token' => Env::get('LINKY_TOKEN', ''),
    'prm' => Env::get('LINKY_PRM', ''),
    'base_url' => Env::get('LINKY_BASE_URL', 'https://conso.boris.sh'),
    'timeout' => Env::get('LINKY_TIMEOUT', 15),
    'user_agent' => Env::get('LINKY_USER_AGENT', 'LaraLinky'),
    'storage' => [
        'database' => [
            'connection' => null,
            'table_prefix' => 'laralinky_',
        ],
    ],
];
