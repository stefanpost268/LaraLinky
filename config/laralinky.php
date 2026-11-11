<?php

declare(strict_types=1);

use Illuminate\Support\Env;

return [
    'token' => (string) Env::get('LINKY_TOKEN', ''),
    'prm' => (string) Env::get('LINKY_PRM', ''),
    'base_url' => (string) Env::get('LINKY_BASE_URL', 'https://conso.boris.sh'),
    'timeout' => (float) Env::get('LINKY_TIMEOUT', 15),
    'user_agent' => (string) Env::get('LINKY_USER_AGENT', 'stefanpost/laralinky'),
];
