<?php

/*
 * Sandbox — Spielwiese fuer den autonomen Worker.
 * Leeres Modul-Grundgeruest zum Experimentieren; nicht fuer Produktiv-Features.
 */

return [
    'name' => 'Sandbox',
    'description' => 'Sandbox — leeres Modul-Grundgeruest (Spielwiese fuer den autonomen Worker)',
    'version' => '1.0.0',

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

    'sidebar' => [
        [
            'group' => 'Allgemein',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'route' => 'sandbox.dashboard',
                    'icon'  => 'heroicon-o-home',
                ],
            ],
        ],
    ],
];
