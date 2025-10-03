<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Clean up any test files
    $testPath = base_path('tests/fixtures/rules');
    if (File::exists($testPath . '/test_rule.php')) {
        File::delete($testPath . '/test_rule.php');
    }
});

afterEach(function () {
    // Clean up test files
    $testPath = base_path('tests/fixtures/rules');
    if (File::exists($testPath . '/test_rule.php')) {
        File::delete($testPath . '/test_rule.php');
    }
});

test('make:rule command creates a new rule file', function () {
    $this->artisan('make:rule', ['name' => 'test_rule'])
        ->expectsOutput('🎉 Validation rules file created successfully!')
        ->assertExitCode(0);
    
    $filePath = base_path('tests/fixtures/rules/test_rule.php');
    expect(File::exists($filePath))->toBeTrue();
});

test('make:rule command creates file with correct content', function () {
    $this->artisan('make:rule', ['name' => 'test_rule'])
        ->assertExitCode(0);
    
    $filePath = base_path('tests/fixtures/rules/test_rule.php');
    $content = File::get($filePath);
    
    expect($content)->toContain('<?php')
        ->and($content)->toContain('return [')
        ->and($content)->toContain('USAGE:')
        ->and($content)->toContain('test_rule');
});

test('make:rule command fails if file already exists', function () {
    // Create the file first
    $this->artisan('make:rule', ['name' => 'test_rule'])
        ->assertExitCode(0);
    
    // Try to create it again
    $this->artisan('make:rule', ['name' => 'test_rule'])
        ->expectsOutput('❌ Error: Rule file \'test_rule.php\' already exists!')
        ->assertExitCode(1);
});

test('make:rule command sanitizes file name', function () {
    $this->artisan('make:rule', ['name' => 'TestRule'])
        ->assertExitCode(0);
    
    // Should create test_rule.php (snake_case)
    $filePath = base_path('tests/fixtures/rules/test_rule.php');
    expect(File::exists($filePath))->toBeTrue();
});

test('make:rule command removes .php extension if provided', function () {
    $this->artisan('make:rule', ['name' => 'test_rule.php'])
        ->assertExitCode(0);
    
    $filePath = base_path('tests/fixtures/rules/test_rule.php');
    expect(File::exists($filePath))->toBeTrue();
    
    // Should not create test_rule.php.php
    $wrongPath = base_path('tests/fixtures/rules/test_rule.php.php');
    expect(File::exists($wrongPath))->toBeFalse();
});

test('make:rule command creates directory if it does not exist', function () {
    // Set a non-existent directory
    config(['laravel-rules.rules_path' => 'tests/fixtures/new_rules_dir']);
    
    $this->artisan('make:rule', ['name' => 'test_rule'])
        ->assertExitCode(0);
    
    $dirPath = base_path('tests/fixtures/new_rules_dir');
    expect(File::isDirectory($dirPath))->toBeTrue();
    
    // Clean up
    File::deleteDirectory($dirPath);
});

