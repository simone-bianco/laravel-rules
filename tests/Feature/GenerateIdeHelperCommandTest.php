<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Clean up any test files
    $helperPath = base_path('_ide_helper_rules.php');
    if (File::exists($helperPath)) {
        File::delete($helperPath);
    }
});

afterEach(function () {
    // Clean up test files
    $helperPath = base_path('_ide_helper_rules.php');
    if (File::exists($helperPath)) {
        File::delete($helperPath);
    }
});

test('rules:generate-ide-helper command creates helper file', function () {
    $this->artisan('rules:generate-ide-helper')
        ->expectsOutput('🎉 IDE Helper file generated successfully!')
        ->assertExitCode(0);
    
    $helperPath = base_path('_ide_helper_rules.php');
    expect(File::exists($helperPath))->toBeTrue();
});

test('rules:generate-ide-helper command creates file with correct content', function () {
    $this->artisan('rules:generate-ide-helper')
        ->assertExitCode(0);
    
    $helperPath = base_path('_ide_helper_rules.php');
    $content = File::get($helperPath);
    
    expect($content)->toContain('<?php')
        ->and($content)->toContain('@method')
        ->and($content)->toContain('SimoneBianco\LaravelRules\Rules')
        ->and($content)->toContain('injectRuleFor');
});

test('rules:generate-ide-helper command includes all fields from rules', function () {
    $this->artisan('rules:generate-ide-helper')
        ->assertExitCode(0);
    
    $helperPath = base_path('_ide_helper_rules.php');
    $content = File::get($helperPath);
    
    // Should include methods for fields from our test fixtures
    expect($content)->toContain('injectRuleForEmail')
        ->and($content)->toContain('injectRuleForName')
        ->and($content)->toContain('injectRuleForAge');
});

test('rules:generate-ide-helper respects custom output path', function () {
    config(['laravel-rules.ide_helper_path' => 'custom_helper.php']);
    
    $this->artisan('rules:generate-ide-helper')
        ->assertExitCode(0);
    
    $customPath = base_path('custom_helper.php');
    expect(File::exists($customPath))->toBeTrue();
    
    // Clean up
    File::delete($customPath);
});

test('rules:generate-ide-helper handles empty rules gracefully', function () {
    // Set a directory with no rules
    config(['laravel-rules.rules_path' => 'tests/fixtures/empty_rules']);
    File::makeDirectory(base_path('tests/fixtures/empty_rules'), 0755, true);
    
    $this->artisan('rules:generate-ide-helper')
        ->expectsOutput('⚠️  No validation rules found. An empty helper file will be generated.')
        ->assertExitCode(0);
    
    // Clean up
    File::deleteDirectory(base_path('tests/fixtures/empty_rules'));
});

