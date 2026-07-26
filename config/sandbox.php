<?php

return [
    'name' => 'Sandbox',
    'description' => 'Sandbox — Spielwiese fuer den autonomen Worker',
    'version' => '1.0.0',

    'scope_type' => 'parent',

    'routing' => [
        'prefix' => 'sandbox',
        'middleware' => ['web', 'auth'],
    ],

    'guard' => 'web',

    'navigation' => [
        'route' => 'sandbox.dashboard',
        'icon'  => 'heroicon-o-beaker',
        'order' => 50,
    ],
];
