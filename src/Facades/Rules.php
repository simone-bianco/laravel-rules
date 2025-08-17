<?php

namespace SimoneBianco\LaravelRules\Facades;

use Illuminate\Support\Facades\Facade;
use SimoneBianco\LaravelRules\Rules as RulesInstance;

/**
 * Facade per accedere al builder delle regole senza istanziare manualmente.
 *
 * Uso:
 *  use SimoneBianco\LaravelRules\Facades\Rules;
 *  $rules = Rules::for('user')->injectRuleForEmail('required|email')->toArray();
 *
 * @method static RulesInstance for(string $group) Crea un'istanza Rules per il gruppo indicato.
 * @method static RulesInstance forOrphansField(string $field) Crea un'istanza Rules per un campo del gruppo orphans.
 */
class Rules extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-rules.factory';
    }
}

