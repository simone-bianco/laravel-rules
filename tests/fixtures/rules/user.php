<?php

return [
    'profile' => [
        'email' => [
            'email' => true,
            'max' => 255,
        ],
        'name' => [
            'min' => 2,
            'max' => 100,
        ],
        'age' => [
            'integer',
            'min' => 18,
            'max' => 120,
        ],
    ],
    
    'settings' => [
        'theme' => [
            'in' => ['light', 'dark', 'auto'],
        ],
        'language' => [
            'max' => 10,
        ],
    ],
];

