<?php

return [
    'user' => [
        'name' => [
            'min' => 2,
            'max' => 255,
        ],
        'email' => [
            'max' => 255,
            'email' => true,
        ],
    ],
    'orphans' => [
        // Single fields that are not part of any group
    ],
];
