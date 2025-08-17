<?php

return [
    'user' => [
        'name' => [
            'string' => true,
            'min' => 2,
            'max' => 255,
        ],
        'email' => [
            'string' => true,
            'max' => 255,
            'email' => true,
        ],
    ],
    'orphans' => [
        // Single fields that are not part of any group
    ],
];
