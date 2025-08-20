<?php

namespace SimoneBianco\LaravelRules\Facades;

use Illuminate\Support\Facades\Facade;
use SimoneBianco\LaravelRules\Rules as RulesInstance;

/**
 * Facade to access the rules builder without manual instantiation.
 *
 * Usage:
 * use SimoneBianco\LaravelRules\Facades\Rules;
 * $rules = Rules::for('user')->injectRuleForEmail('required|email')->toArray();
 *
 * @method static RulesInstance for(string $group) Creates a Rules instance for the specified group.
 * @method static RulesInstance forOrphansField(string $field) Creates a Rules instance for a field in the orphans group.
 */
class Rules extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-rules.factory';
    }
}
