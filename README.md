# Laravel Rules

[![Latest Version](https://img.shields.io/packagist/v/simone-bianco/laravel-rules.svg?style=flat-square)](https://packagist.org/packages/simone-bianco/laravel-rules)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

A powerful Laravel package for managing validation rules in a centralized, organized, and DRY way. Define your validation rules in separate files, organize them by domain, and access them with a fluent API.

## Features

✨ **File-based Organization** - Store validation rules in separate PHP files for better organization
🎯 **Dot Notation Access** - Access rules using intuitive dot notation (e.g., `user.profile.email`)
⚡ **Smart Caching** - Configurable caching system for optimal performance
🔧 **Artisan Commands** - Generate new rule files with helpful templates
💡 **IDE Autocompletion** - Generate helper files for full IDE support
🧪 **Fully Tested** - Comprehensive test suite with Pest
🎨 **Fluent API** - Chain methods for clean, readable code

## Installation

Install the package via Composer:

```bash
composer require simone-bianco/laravel-rules
```

Publish the configuration file:

```bash
php artisan vendor:publish --provider="SimoneBianco\LaravelRules\LaravelRulesServiceProvider"
```

This creates `config/laravel-rules.php` with the following options:

```php
return [
    'rules_path' => 'config/laravel-rules',  // Where your rule files are stored
    'cache_enabled' => true,                  // Enable/disable caching
    'cache_ttl' => 3600,                      // Cache TTL in seconds (0 = forever)
    'ide_helper_path' => '_ide_helper_rules.php', // IDE helper file location
];
```

## Quick Start

### 1. Create Your First Rule File

```bash
php artisan make:rule user
```

This creates `config/laravel-rules/user.php` with a helpful template:

```php
<?php

use SimoneBianco\LaravelRules\Rules;

return [
    'profile' => [
        'email' => [
            'email' => true,
            'max' => 255,
        ],
        'name' => [
            'min' => 2,
            'max' => 100,
        ],
        'password' => [
            'min' => 8,
            'max' => 255,
        ],
    ],

    'settings' => [
        'theme' => [
            'in' => ['light', 'dark', 'auto'],
        ],
        'language' => [
            'max' => 10,
        ],
    ],
];
```

### 2. Use Rules in Your Code

```php
use SimoneBianco\LaravelRules\Rules;

// Get all rules for a group
$rules = Rules::for('user.profile')->toArray();

// Get specific fields only
$rules = Rules::for('user.profile')->only(['email', 'name'])->toArray();

// Inject contextual rules (required, unique, etc.)
$rules = Rules::for('user.profile')
    ->injectRuleForEmail('required|unique:users,email')
    ->injectRuleForName('required')
    ->toArray();
```

### 3. Use in Form Requests

```php
use SimoneBianco\LaravelRules\Rules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function rules(): array
    {
        return Rules::for('user.profile')
            ->only(['email', 'name'])
            ->injectRuleForEmail('required|unique:users,email,' . $this->user->id)
            ->injectRuleForName('required')
            ->toArray();
    }
}
```


## Core Concepts

### File-based Organization

Rules are organized in separate PHP files within the configured directory (default: `config/laravel-rules`). Each file can contain multiple groups of rules.

**File structure:**
```
config/laravel-rules/
├── user.php          # User-related rules
├── post.php          # Post-related rules
├── common.php        # Common/shared rules
└── ai_operations.php # AI-specific rules
```

**Accessing rules:**
- `Rules::for('user.profile')` - Access the 'profile' group from user.php
- `Rules::for('post.content')` - Access the 'content' group from post.php
- `Rules::for('common.orphans')` - Access the 'orphans' group from common.php

### Rule Definition Format

Rules can be defined in multiple ways:

```php
return [
    'example' => [
        // Key-value pairs for rules with parameters
        'field1' => [
            'min' => 5,
            'max' => 255,
            'regex' => '/^[a-z0-9]+$/',
        ],

        // Boolean true for rules without parameters
        'field2' => [
            'required' => true,
            'email' => true,
        ],

        // Numeric keys for string rules
        'field3' => [
            'required',
            'string',
            'email',
        ],

        // Arrays for rules with multiple parameters
        'field4' => [
            'in' => ['admin', 'user', 'guest'],
            'mimes' => 'jpeg,png,jpg',
        ],

        // Custom validation rules
        'field5' => [
            'string',
            Rules::customRule(YourCustomRule::class),
            'max' => 100,
        ],

        // Custom rules with parameters
        'field6' => [
            Rules::customRule(YourCustomRule::class, $param1, $param2),
        ],
    ],
];
```

## API Reference

### Static Methods

#### `Rules::for(string $group, array $only = []): Rules`

Create a Rules instance for a specific group.

```php
// Get all rules for a group
$rules = Rules::for('user.profile')->toArray();

// Get only specific fields
$rules = Rules::for('user.profile', ['email', 'name'])->toArray();
```

#### `Rules::forOrphansField(string $field): Rules`

Get rules for a single orphan field (fields that don't belong to a specific entity).

```php
$searchRules = Rules::forOrphansField('search')->toArray();
```

#### `Rules::getAllGroups(): array`

Get all available rule groups.

```php
$groups = Rules::getAllGroups();
// ['user.profile', 'user.settings', 'post.content', ...]
```

#### `Rules::clearCache(): void`

Clear all cached rules.

```php
Rules::clearCache();
```

#### `Rules::customRule(string $class, ...$args): string|array`

Define a custom validation rule.

```php
Rules::customRule(ValidUsername::class)
Rules::customRule(ValidContent::class, 10000)
```

### Instance Methods

#### `only(array|string $fields): self`

Filter rules to only include specified fields.

```php
$rules = Rules::for('user.profile')
    ->only(['email', 'name'])
    ->toArray();

// Single field
$rules = Rules::for('user.profile')
    ->only('email')
    ->toArray();
```

#### `except(array $fields): self`

Exclude specified fields from rules.

```php
$rules = Rules::for('user.profile')
    ->except(['password'])
    ->toArray();
```

#### `injectField(string $field, array $rules): self`

Add a new field with rules at runtime.

```php
$rules = Rules::for('user.profile')
    ->injectField('remember_token', ['required', 'string', 'max:100'])
    ->toArray();
```

#### `injectRuleForField(string $field, mixed $rules): self`

Add additional rules to an existing field.

```php
$rules = Rules::for('user.profile')
    ->injectRuleForField('email', 'required|unique:users,email')
    ->toArray();
```

#### `injectRuleFor{Field}(mixed $rules): self`

Dynamic method to inject rules for a specific field.

```php
$rules = Rules::for('user.profile')
    ->injectRuleForEmail('required')
    ->injectRuleForName(['required', 'string'])
    ->toArray();
```

#### `getFieldRules(string $field): array`

Get rules for a single field.

```php
$emailRules = Rules::for('user.profile')->getFieldRules('email');
```

#### `toArray(): array`

Convert rules to array format for Laravel validation.

```php
$rules = Rules::for('user.profile')->toArray();
```


## Artisan Commands

### `make:rule`

Generate a new validation rules file with a helpful template.

```bash
php artisan make:rule user
php artisan make:rule ai_operations
php artisan make:rule common
```

**Options:**
- The name will be automatically converted to snake_case
- The `.php` extension is optional and will be removed if provided
- Creates the rules directory if it doesn't exist

**Generated file includes:**
- Comprehensive documentation
- Usage examples
- Best practices
- Template structure

### `rules:generate-ide-helper`

Generate an IDE helper file for autocompletion of dynamic methods.

```bash
php artisan rules:generate-ide-helper
```

This creates a file (default: `_ide_helper_rules.php`) that provides IDE autocompletion for all `injectRuleFor*` methods based on your defined fields.

**When to regenerate:**
- After adding new rule files
- After adding new fields to existing files
- After removing fields

## Configuration

The `config/laravel-rules.php` file provides several configuration options:

```php
return [
    // Directory where rule files are stored
    'rules_path' => env('LARAVEL_RULES_PATH', 'config/laravel-rules'),

    // Enable or disable caching
    'cache_enabled' => env('LARAVEL_RULES_CACHE_ENABLED', true),

    // Cache TTL in seconds (0 = forever)
    'cache_ttl' => env('LARAVEL_RULES_CACHE_TTL', 3600),

    // IDE helper file location
    'ide_helper_path' => env('LARAVEL_RULES_IDE_HELPER_PATH', '_ide_helper_rules.php'),
];
```

### Environment Variables

You can override configuration using environment variables:

```env
LARAVEL_RULES_PATH=app/ValidationRules
LARAVEL_RULES_CACHE_ENABLED=true
LARAVEL_RULES_CACHE_TTL=7200
LARAVEL_RULES_IDE_HELPER_PATH=_ide_helper_rules.php
```

## Caching

The package includes a smart caching system to improve performance:

- **Enabled by default** - Rules are cached after first load
- **Configurable TTL** - Set cache duration in seconds (default: 3600)
- **Forever cache** - Set TTL to 0 to cache indefinitely
- **Easy clearing** - Use `Rules::clearCache()` or `php artisan cache:clear`

**Cache behavior:**
```php
// First call - loads from files and caches
$rules = Rules::for('user.profile')->toArray();

// Subsequent calls - loads from cache
$rules = Rules::for('user.profile')->toArray();

// Clear cache programmatically
Rules::clearCache();

// Disable caching for development
config(['laravel-rules.cache_enabled' => false]);
```

## Advanced Usage

### Working with Custom Validation Rules

```php
use SimoneBianco\LaravelRules\Rules;
use App\Rules\ValidUsername;
use App\Rules\ValidContent;

// In your rule file (e.g., config/laravel-rules/user.php)
return [
    'profile' => [
        'username' => [
            'string',
            Rules::customRule(ValidUsername::class),
            'min' => 5,
            'max' => 255,
        ],
        'bio' => [
            Rules::customRule(ValidContent::class, 1000), // With parameter
        ],
    ],
];
```

### Combining Multiple Rule Groups

```php
// Merge rules from different groups
$userRules = Rules::for('user.profile')->toArray();
$settingsRules = Rules::for('user.settings')->toArray();

$allRules = array_merge($userRules, $settingsRules);
```

### Conditional Rules

```php
$rules = Rules::for('user.profile')
    ->injectRuleForEmail('required');

if ($isUpdate) {
    $rules->injectRuleForEmail('unique:users,email,' . $userId);
} else {
    $rules->injectRuleForEmail('unique:users,email');
}

return $rules->toArray();
```

### Using with Laravel's Rule Objects

```php
use Illuminate\Validation\Rule;

$rules = Rules::for('user.profile')
    ->injectRuleForEmail([
        'required',
        Rule::unique('users', 'email')->ignore($userId),
    ])
    ->toArray();
```

## Best Practices

### 1. Separate Structural and Contextual Rules

**✅ Good - Structural rules in files:**
```php
// config/laravel-rules/user.php
return [
    'profile' => [
        'email' => [
            'email' => true,
            'max' => 255,
        ],
    ],
];
```

**✅ Good - Contextual rules at runtime:**
```php
$rules = Rules::for('user.profile')
    ->injectRuleForEmail('required|unique:users,email')
    ->toArray();
```

**❌ Bad - Mixing contextual rules in files:**
```php
// Don't do this
return [
    'profile' => [
        'email' => [
            'required' => true,  // Contextual - should be added at runtime
            'email' => true,
            'max' => 255,
        ],
    ],
];
```

### 2. Organize by Domain

Group related rules in the same file:

```
config/laravel-rules/
├── user.php          # User authentication, profile, settings
├── post.php          # Posts, comments, likes
├── ecommerce.php     # Products, orders, payments
└── common.php        # Shared/orphan fields
```

### 3. Use Descriptive Group Names

```php
// ✅ Good
Rules::for('user.profile')
Rules::for('user.settings')
Rules::for('post.content')

// ❌ Bad
Rules::for('user.data')
Rules::for('post.stuff')
```

### 4. Regenerate IDE Helper Regularly

Add to your deployment or development workflow:

```bash
php artisan rules:generate-ide-helper
```

### 5. Clear Cache After Updates

When deploying or updating rules:

```bash
php artisan cache:clear
# or
php artisan optimize:clear
```

## Testing

The package includes a comprehensive test suite using Pest:

```bash
# Run all tests
./vendor/bin/pest

# Run specific test suite
./vendor/bin/pest tests/Unit
./vendor/bin/pest tests/Feature

# Run with coverage
./vendor/bin/pest --coverage
```

## Troubleshooting

### Rules not found

**Problem:** `InvalidArgumentException: Rules not found for group X`

**Solutions:**
1. Check the file exists in the configured directory
2. Verify the group name matches the file structure
3. Clear cache: `Rules::clearCache()`

### IDE autocompletion not working

**Problem:** Dynamic methods not autocompleting

**Solutions:**
1. Run `php artisan rules:generate-ide-helper`
2. Restart your IDE
3. Check the helper file was generated in the correct location

### Cache not updating

**Problem:** Changes to rule files not reflected

**Solutions:**
1. Clear cache: `php artisan cache:clear`
2. Or disable caching during development: `config(['laravel-rules.cache_enabled' => false])`

## Migration from v1.x

If you're upgrading from version 1.x, here are the key changes:

### Configuration File

**Old (v1.x):**
```php
// config/laravel-rules.php contained actual rules
return [
    'user' => [
        'email' => ['email' => true, 'max' => 255],
    ],
];
```

**New (v2.x):**
```php
// config/laravel-rules.php contains only configuration
return [
    'rules_path' => 'config/laravel-rules',
    'cache_enabled' => true,
    'cache_ttl' => 3600,
];
```

### Rule Files

Move your rules from `config/laravel-rules.php` to separate files in `config/laravel-rules/`:

```bash
# Create a new rule file
php artisan make:rule user

# Move your rules to the generated file
```

### Accessing Rules

**Old (v1.x):**
```php
Rules::for('user')->toArray()
```

**New (v2.x):**
```php
Rules::for('filename.group')->toArray()
// Example: Rules::for('user.profile')->toArray()
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Credits

- [Simone Bianco](https://github.com/simonebianco)
- Built with [Spatie's Laravel Package Tools](https://github.com/spatie/laravel-package-tools)

## Support

If you find this package helpful, please consider:
- ⭐ Starring the repository
- 🐛 Reporting bugs
- 💡 Suggesting new features
- 📖 Improving documentation

