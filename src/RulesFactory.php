<?php

namespace SimoneBianco\LaravelRules;

/**
 * Non static factory to create instances of Rules.
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

