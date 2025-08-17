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
        // Campi singoli riutilizzabili tra più contesti applicativi
    ],
];
