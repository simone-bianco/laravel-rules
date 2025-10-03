<?php

use SimoneBianco\LaravelRules\Rules;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Rules::clearCache();
});

test('rules are cached when cache is enabled', function () {
    config(['laravel-rules.cache_enabled' => true]);
    
    // First call should cache the rules
    $rules1 = Rules::for('user.profile')->toArray();
    
    // Check if cache exists
    $cacheKey = 'laravel_rules_user.profile';
    expect(Cache::has($cacheKey))->toBeTrue();
    
    // Second call should use cache
    $rules2 = Rules::for('user.profile')->toArray();
    
    expect($rules1)->toEqual($rules2);
});

test('rules are not cached when cache is disabled', function () {
    config(['laravel-rules.cache_enabled' => false]);
    
    // Call should not cache the rules
    Rules::for('user.profile')->toArray();
    
    // Check if cache does not exist
    $cacheKey = 'laravel_rules_user.profile';
    expect(Cache::has($cacheKey))->toBeFalse();
});

test('cache respects ttl setting', function () {
    config(['laravel-rules.cache_enabled' => true]);
    config(['laravel-rules.cache_ttl' => 60]);
    
    Rules::for('user.profile')->toArray();
    
    $cacheKey = 'laravel_rules_user.profile';
    expect(Cache::has($cacheKey))->toBeTrue();
});

test('cache can be cleared', function () {
    config(['laravel-rules.cache_enabled' => true]);
    
    // Cache some rules
    Rules::for('user.profile')->toArray();
    Rules::for('user.settings')->toArray();
    
    // Verify cache exists
    expect(Cache::has('laravel_rules_user.profile'))->toBeTrue();
    expect(Cache::has('laravel_rules_user.settings'))->toBeTrue();
    
    // Clear cache
    Rules::clearCache();
    
    // Verify cache is cleared
    expect(Cache::has('laravel_rules_user.profile'))->toBeFalse();
    expect(Cache::has('laravel_rules_user.settings'))->toBeFalse();
});

test('cache with ttl zero stores forever', function () {
    config(['laravel-rules.cache_enabled' => true]);
    config(['laravel-rules.cache_ttl' => 0]);
    
    Rules::for('user.profile')->toArray();
    
    $cacheKey = 'laravel_rules_user.profile';
    expect(Cache::has($cacheKey))->toBeTrue();
});

