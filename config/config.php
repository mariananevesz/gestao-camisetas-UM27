<?php

return [
    'app' => [
        'name' => 'Gestão de Camisetas – UMADEPRESP 2027',
        'timezone' => 'America/Sao_Paulo',
        'pedidos_por_pagina' => 10,
    ],
    'database' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'name' => getenv('DB_NAME') ?: 'umadepresp_camisetas',
        'user' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
    ],
];
