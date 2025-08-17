# Laravel Rules (simone-bianco/laravel-rules)

Dynamic, centralized, and cached management of Laravel validation rules, with IDE helper generation without modifying the original class.

## Goals

- Centralize structural rule definitions (min, max, pattern, etc.).
- Exclude all contextual rules from the config (`required`, `nullable`, `unique`, `confirmed`, etc.).
- Dynamically add contextual rules at runtime via a fluent API.
- Provide autocompletion for dynamic `injectRuleFor*` methods via a generated helper file.

## Installation (local repository path)

Add (if not already present) to your root `composer.json`:

```json
"repositories": [
  {"type": "path", "url": "packages/simone-bianco/laravel-rules", "options": {"symlink": true}}
]
```

Then install:

```bash
composer require simone-bianco/laravel-rules:*
```

The service provider is auto-discovered. A backward-compatible alias `\App\Features\Rules` is also created.

## Publishing Configuration

Publish the sample configuration file (if you don't already have one):

```bash
php artisan vendor:publish --tag=laravel-rules-config
```

This creates `config/laravel-rules.php` with an initial structure:

```php
return [
    'user' => [
        'name' => [ 'min' => 2, 'max' => 255 ],
        'email' => [ 'max' => 255, 'email' => true ],
    ],
    'orphans' => [ /* standalone fields */ ],
];
```

NOTE: Do not insert contextual rules (required, unique, nullable, confirmed...).

## IDE Helper Generation

To get autocompletion for dynamic `injectRuleForXyz` methods without touching the class:

```bash
php artisan docs:generate-rules
```

This generates `_ide_helper_laravel_rules.php` (path is customizable with `--path=`) which IDEs will index.
Regenerate the file whenever you add/remove fields in the config.

## Main API

```php
use SimoneBianco\LaravelRules\Rules; // or \App\Features\Rules

// Full rules for a group
$rules = Rules::for('user')->toArray();

// Only some fields
$loginRules = Rules::for('user')->only(['email', 'password']);

// Exclude fields
$updateRules = Rules::for('user')->except(['password']);

// Orphan field
$slugRules = Rules::forOrphansField('slug')->toArray();

// Inject additional (contextual) rules for an update
$rules = Rules::for('user')
    ->injectRuleForField('email', 'required|email|unique:users,email,'.$userId)
    ->injectRuleForName(['required', 'string']) // via dynamic method
    ->toArray();
```

### Dynamic Methods

Each field defined in the group generates a virtual method: `injectRuleFor{StudlyCaseField}`.
Example: `first_name` field =\> `injectRuleForFirstName()`.

## Example in a FormRequest

```php
public function rules(): array
{
    return Rules::for('user')
        ->only(['name','email'])
        ->injectRuleForName('required|string|min:2')
        ->injectRuleForEmail('required|email|unique:users,email')
        ->toArray();
}
```

## Configuration Format

Each field is an associative array of `rule => value`:

```php
'username' => [
  'min' => 5,
  'max' => 255,
  'regex' => '/^[a-z0-9.]+$/',
],
```

Arrays as values are transformed into CSV lists (`in:admin,user,guest`).

## Caching

The processed rules are cached (1h TTL). Clear with:

```bash
php artisan cache:clear
```

## Artisan Commands

| Command | Description |
|---|---|
| `rules:publish-config` | Publishes the `config/laravel-rules.php` file. |
| `docs:generate-rules` | Generates `_ide_helper_laravel_rules.php` with dynamic methods. |

## Why Not Modify The Class

The helper file avoids merge noise and keeps the class minimal, adhering to the SRP (Single Responsibility Principle).

## Best Practices

- Keep the config free of contextual rules.
- Regenerate the helper after adding new fields.
- Use `only()` when validating subsets (login, partial updates).
- Avoid duplication: always centralize lengths and patterns.

## License

MIT
