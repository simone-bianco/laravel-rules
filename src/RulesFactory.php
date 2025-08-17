<?php

namespace SimoneBianco\LaravelRules;

/**
 * Factory non statica usata dalla Facade per creare istanze di Rules.
 */
class RulesFactory
{
    public function for(string $group): Rules
    {
        return Rules::for($group);
    }

    public function forOrphansField(string $field): Rules
    {
        return Rules::forOrphansField($field);
    }
}

