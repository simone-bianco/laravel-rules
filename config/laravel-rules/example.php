<?php

/**
 * Example validation rules file.
 *
 * This file demonstrates how to define validation rules for the laravel-rules package.
 *
 * USAGE:
 * ------
 * Rules can be accessed using dot notation:
 * - Rules::for('example.user') - Access the 'user' group from this file
 * - Rules::for('example.user.email') - Access specific field rules
 *
 * FILE NAMING:
 * ------------
 * The filename (without .php extension) becomes the first part of the rule identifier.
 * For example, this file is named 'example.php', so all rules here are prefixed with 'example.'
 *
 * STRUCTURE:
 * ----------
 * Return an associative array where:
 * - Keys are group names (e.g., 'user', 'post', 'comment')
 * - Values are arrays of field validation rules
 *
 * RULE FORMAT:
 * ------------
 * Each field can have rules defined as:
 * - 'rule_name' => value (e.g., 'min' => 5, 'max' => 255)
 * - 'rule_name' => true (for rules without parameters, e.g., 'required' => true)
 * - Numeric keys for string rules (e.g., ['required', 'string'])
 * - Arrays for rules with multiple parameters (e.g., 'in' => ['admin', 'user'])
 *
 * CUSTOM RULES:
 * -------------
 * Use Rules::customRule() for custom validation rule classes:
 * Rules::customRule(YourCustomRule::class)
 * Rules::customRule(YourCustomRule::class, $param1, $param2)
 */

use SimoneBianco\LaravelRules\Notifications;

return [
    'user' => [
        'email' => [
            'email' => true,
            'max' => 255,
        ],
        'name' => [
            'min' => 2,
            'max' => 255,
        ],
        'password' => [
            'min' => 8,
            'max' => 255,
        ],
    ],

    'post' => [
        'title' => [
            'min' => 5,
            'max' => 255,
        ],
        'content' => [
            'max' => 10000,
        ],
        'status' => [
            'in' => ['draft', 'published', 'archived'],
        ],
    ],
];

