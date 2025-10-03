<?php

use SimoneBianco\LaravelRules\Rules;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    // Clear cache before each test
    Rules::clearCache();
});

test('can load rules from directory', function () {
    $groups = Rules::getAllGroups();
    
    expect($groups)->toBeArray()
        ->and($groups)->toContain('user.profile')
        ->and($groups)->toContain('user.settings')
        ->and($groups)->toContain('common.orphans');
});

test('can get rules for a specific group', function () {
    $rules = Rules::for('user.profile')->toArray();
    
    expect($rules)->toBeArray()
        ->and($rules)->toHaveKeys(['email', 'name', 'age']);
});

test('can get rules with dot notation', function () {
    $rules = Rules::for('user.profile')->toArray();
    
    expect($rules['email'])->toContain('email')
        ->and($rules['email'])->toContain('max:255');
});

test('can filter rules with only method', function () {
    $rules = Rules::for('user.profile')->only(['email', 'name'])->toArray();
    
    expect($rules)->toHaveKeys(['email', 'name'])
        ->and($rules)->not->toHaveKey('age');
});

test('can filter rules with only method using single string', function () {
    $rules = Rules::for('user.profile')->only('email')->toArray();
    
    expect($rules)->toHaveKeys(['email'])
        ->and($rules)->not->toHaveKeys(['name', 'age']);
});

test('can exclude rules with except method', function () {
    $rules = Rules::for('user.profile')->except(['age'])->toArray();
    
    expect($rules)->toHaveKeys(['email', 'name'])
        ->and($rules)->not->toHaveKey('age');
});

test('can inject field at runtime', function () {
    $rules = Rules::for('user.profile')
        ->injectField('new_field', ['required', 'string'])
        ->toArray();
    
    expect($rules)->toHaveKey('new_field')
        ->and($rules['new_field'])->toContain('required')
        ->and($rules['new_field'])->toContain('string');
});

test('cannot inject field that already exists', function () {
    Rules::for('user.profile')
        ->injectField('email', ['required']);
})->throws(InvalidArgumentException::class);

test('can inject rules for existing field', function () {
    $rules = Rules::for('user.profile')
        ->injectRuleForField('email', 'required')
        ->toArray();
    
    expect($rules['email'])->toContain('required');
});

test('can inject rules using dynamic method', function () {
    $rules = Rules::for('user.profile')
        ->injectRuleForEmail('required')
        ->toArray();
    
    expect($rules['email'])->toContain('required');
});

test('can get field rules', function () {
    $emailRules = Rules::for('user.profile')->getFieldRules('email');
    
    expect($emailRules)->toBeArray()
        ->and($emailRules)->toContain('email')
        ->and($emailRules)->toContain('max:255');
});

test('throws exception for non-existent group', function () {
    Rules::for('non.existent.group');
})->throws(InvalidArgumentException::class);

test('throws exception for non-existent field in getFieldRules', function () {
    Rules::for('user.profile')->getFieldRules('non_existent_field');
})->throws(InvalidArgumentException::class);

test('can count fields', function () {
    $rules = Rules::for('user.profile');
    
    expect($rules)->toHaveCount(3);
});

test('can iterate over rules', function () {
    $rules = Rules::for('user.profile');
    $fields = [];
    
    foreach ($rules as $field => $fieldRules) {
        $fields[] = $field;
    }
    
    expect($fields)->toContain('email', 'name', 'age');
});

test('can convert to json', function () {
    $rules = Rules::for('user.profile');
    $json = json_encode($rules);
    
    expect($json)->toBeString()
        ->and($json)->toContain('email')
        ->and($json)->toContain('name');
});

test('can convert to string', function () {
    $rules = Rules::for('user.profile');
    $string = (string) $rules;
    
    expect($string)->toBeString()
        ->and($string)->toContain('email')
        ->and($string)->toContain('name');
});

test('can get orphan field', function () {
    $rules = Rules::forOrphansField('search')->toArray();
    
    expect($rules)->toHaveKey('search')
        ->and($rules['search'])->toContain('max:255');
});

test('throws exception for non-existent orphan field', function () {
    Rules::forOrphansField('non_existent');
})->throws(InvalidArgumentException::class);

test('processes array rules correctly', function () {
    $rules = Rules::for('user.settings')->toArray();
    
    expect($rules['theme'])->toContain('in:light,dark,auto');
});

test('processes integer key rules correctly', function () {
    $rules = Rules::for('user.profile')->toArray();
    
    expect($rules['age'])->toContain('integer')
        ->and($rules['age'])->toContain('min:18')
        ->and($rules['age'])->toContain('max:120');
});

