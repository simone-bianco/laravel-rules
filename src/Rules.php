<?php

namespace SimoneBianco\LaravelRules;

use BadMethodCallException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use IteratorAggregate;
use Countable;
use JsonSerializable;
use ArrayIterator;
use Stringable;


class Rules implements IteratorAggregate, Countable, JsonSerializable, Stringable
{
    public const string CONFIG_FILE = 'laravel-rules';
    public const string ORPHANS_GROUP = 'orphans';
    public const string CACHE_KEY = 'laravel_rules_';
    public const string CUSTOM_RULE_PREFIX = 'laravel_rules_custom::';

    protected string $group = '';
    protected array $rules = [];

    protected static ?array $allRulesCache = null;

    /**
     * Load all rules from the configured directory.
     *
     * @return array
     */
    protected static function _loadAllRules(): array
    {
        if (self::$allRulesCache !== null) {
            return self::$allRulesCache;
        }

        $rulesPath = config('laravel-rules.rules_path', 'config/laravel-rules');
        $fullPath = base_path($rulesPath);

        if (!File::isDirectory($fullPath)) {
            self::$allRulesCache = [];
            return [];
        }

        $allRules = [];
        $files = File::files($fullPath);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $filename = $file->getFilenameWithoutExtension();
            $fileRules = require $file->getPathname();

            if (!is_array($fileRules)) {
                continue;
            }

            // Prefix all rules with the filename
            foreach ($fileRules as $group => $rules) {
                $prefixedGroup = $filename . '.' . $group;
                $allRules[$prefixedGroup] = $rules;
            }
        }

        self::$allRulesCache = $allRules;
        return $allRules;
    }

    protected static function _getFromCache(string $group): ?array
    {
        if (!config('laravel-rules.cache_enabled', true)) {
            return null;
        }

        return Cache::get(self::CACHE_KEY . $group);
    }

    protected static function _putInCache(string $group, array $rules): void
    {
        if (!config('laravel-rules.cache_enabled', true)) {
            return;
        }

        $ttl = config('laravel-rules.cache_ttl', 3600);

        if ($ttl === 0) {
            Cache::forever(self::CACHE_KEY . $group, $rules);
        } else {
            Cache::put(self::CACHE_KEY . $group, $rules, $ttl);
        }
    }

    protected static function _getFieldsRules(string $group): array
    {
        $allRules = self::_loadAllRules();

        if (!isset($allRules[$group])) {
            throw new InvalidArgumentException(sprintf('Rules not found for group %s', $group));
        }

        return $allRules[$group];
    }

    /**
     * Converts the rules from the config file into a format compatible with Laravel's Validator.
     * example:
     * 'username' => [
     *    'string',
     *    Rules::customRule(ValidUsername::class),
     *   'min' => 5,
     *   'max' => 255,
     * ],
     * becomes
     * 'username' => ['string', new ValidUsername(), 'min:5', 'max:255']
     *
     * @param string $group
     * @param array|null $allowedFields
     * @return array
     */
    protected static function _getLaravelRules(string $group, ?array $allowedFields = null): array
    {
        $laravelRules = self::_getFromCache($group);
        if ($laravelRules !== null) {
            if (empty($allowedFields)) {
                return $laravelRules;
            }
            return array_intersect_key($laravelRules, array_flip($allowedFields));
        }

        $fieldsRules = static::_getFieldsRules($group);
        if (!empty($allowedFields)) {
            $fieldsRules = array_intersect_key($fieldsRules, array_flip($allowedFields));
        }

        $laravelRules = [];
        foreach ($fieldsRules as $field => $rules) {
            $fieldRules = [];
            foreach ($rules as $rule => $value) {
                // if key is integer, we have something like ['required', CUSTOM_RULE_PREFIX::ValidUsernameClass]
                if (is_int($rule)) {
                    $isCustomRuleArray = is_array($value) && isset($value[0]) && is_string($value[0]) && Str::startsWith($value[0], self::CUSTOM_RULE_PREFIX);
                    $isCustomRuleString = is_string($value) && Str::startsWith($value, self::CUSTOM_RULE_PREFIX);

                    if ($isCustomRuleArray) {
                        // Case: Custom rule with arguments, e.g., ['laravel_rules_custom::...', [500]]
                        $validationRuleClass = Str::after($value[0], self::CUSTOM_RULE_PREFIX);
                        $arguments = $value[1] ?? [];
                        $fieldRules[] = new $validationRuleClass(...$arguments);
                    } elseif ($isCustomRuleString) {
                        // Case: Custom rule without arguments, e.g., 'laravel_rules_custom::...'
                        $validationRuleClass = Str::after($value, self::CUSTOM_RULE_PREFIX);
                        $fieldRules[] = new $validationRuleClass();

                    } else {
                        // Case: Standard string rule, e.g., 'required'
                        $fieldRules[] = $value;
                    }
                    continue;
                }

                // if value is true, we have a rule without parameters (e.g., 'required' or CUSTOM_RULE_PREFIX::ValidUsernameClass)
                if ($value === true) {
                    $fieldRules[] = $rule;
                    continue;
                }

                // if value is an array, we have a rule with parameters (e.g., ['min:2', 'max:255'])
                if (is_array($value)) {
                    $fieldRules[] = $rule . ':' . implode(',', $value);
                    continue;
                }

                // if value is a string, we have a rule with parameters (e.g., 'min:2', 'max:255')
                $fieldRules[] = "$rule:$value";
            }

            $laravelRules[$field] = $fieldRules;
        }

        self::_putInCache($group, $laravelRules);
        return $laravelRules;
    }

    /**
     * Creates a new Rules instance.
     *
     * @param string $group The name of the validation group.
     * @param array $rules The pre-processed validation rules.
     */
    public function __construct(string $group, array $rules)
    {
        $this->group = $group;
        $this->rules = $rules;
    }

    /**
     * Creates a new Rules instance for a specified validation group from the config file.
     * This is the primary entry point for building a ruleset.
     *
     * <code>
     * $userRules = Rules::for('user')->toArray();
     * </code>
     *
     * @param string $group The name of the group defined in the configuration file.
     * @return static A new Rules instance for fluent chaining.
     */
    public static function for(string $group, array $only = []): static
    {
        return (new static($group, self::_getLaravelRules($group, $only)));
    }

    /**
     * Creates a new Rules instance for a single field from the 'orphans' group.
     * This is useful for standalone fields that don't belong to a larger entity.
     *
     * <code>
     * $slugRules = Rules::forOrphansField('slug')->toArray();
     * </code>
     *
     * @param string $field The name of the field in the 'orphans' group.
     * @return static A new Rules instance for fluent chaining.
     */
    public static function forOrphansField(string $field): static
    {
        // Try to find orphans in any file
        $allRules = self::_loadAllRules();

        foreach ($allRules as $group => $fields) {
            if (Str::endsWith($group, '.orphans')) {
                if (isset($fields[$field])) {
                    return (new static($group, self::_getLaravelRules($group, [$field])));
                }
            }
        }

        throw new InvalidArgumentException(sprintf('Orphan field %s not found', $field));
    }

    /**
     * Filters the ruleset to only include the specified fields.
     *
     * <code>
     * // Keep only name and email
     * $rules = Rules::for('user')->only(['name', 'email'])->toArray();
     *
     * // Keep only the email field
     * $emailRules = Rules::for('user')->only('email')->toArray();
     * </code>
     *
     * @param array|string $fields An array of fields to keep, or a single field name as a string.
     * @return $this The current instance for fluent chaining.
     */
    public function only(array|string $fields): self
    {
        if (is_string($fields)) {
            $fields = [$fields];
        }

        $this->rules = array_intersect_key($this->rules, array_flip($fields));
        return $this;
    }

    /**
     * Injects a new field and its validation rules into the current instance.
     * This is useful for adding fields that are not defined in the configuration file.
     *
     * <code>
     * $rules = Rules::for('user')
     * ->only('name')
     * ->injectField('terms_accepted', ['required', 'accepted'])
     * ->toArray();
     * </code>
     *
     * @param string $field The name of the new field.
     * @param array $rules An array of validation rules for the new field.
     * @return $this The current instance for fluent chaining.
     * @throws InvalidArgumentException if the field already exists in the ruleset.
     */
    public function injectField(string $field, array $rules): self
    {
        if (array_key_exists($field, $this->rules)) {
            throw new InvalidArgumentException(sprintf('Field %s already exists in group %s', $field, $this->group));
        }

        $this->rules[$field] = $rules;

        return $this;
    }

    /**
     * Retrieves the validation rules for a single specified field.
     *
     * <code>
     * $nameRules = Rules::for('user')->getFieldRules('name');
     * // $nameRules will be ['min:2', 'max:255']
     * </code>
     *
     * @param string $field The name of the field whose rules are to be retrieved.
     * @return array An array of rules for the specified field.
     */
    public function getFieldRules(string $field): array
    {
        return $this->rules[$field] ?? [];
    }

    /**
     * Retrieves the validation rules for a single specified field.
     *
     * <code>
     * $nameRules = Rules::for('user')->getFieldRulesOrFail('name');
     * // $nameRules will be ['min:2', 'max:255']
     * </code>
     *
     * @param string $field The name of the field whose rules are to be retrieved.
     * @return array An array of rules for the specified field.
     * @throws InvalidArgumentException if the field is not found in the ruleset.
     */
    public function getFieldRulesOrFail(string $field): array
    {
        if (!array_key_exists($field, $this->rules)) {
            throw new InvalidArgumentException(sprintf('Field %s not found in group %s', $field, $this->group));
        }

        return $this->rules[$field];
    }

    /**
     * Excludes the specified fields from the current ruleset.
     *
     * <code>
     * // Get all user rules except for the password
     * $rules = Rules::for('user')->except(['password'])->toArray();
     * </code>
     *
     * @param array $fields An array of field names to exclude.
     * @return $this The current instance for fluent chaining.
     */
    public function except(array $fields): self
    {
        $this->rules = array_diff_key($this->rules, array_flip($fields));
        return $this;
    }

    /**
     * Injects additional validation rules for an existing field in the ruleset.
     * This is the primary method for adding contextual rules like `required` or `unique`.
     *
     * <code>
     * $userId = 1;
     * $rules = Rules::for('user')
     * ->injectRuleForField('email', 'required|email')
     * ->injectRuleForField('first_name', ['required', 'email', 'min:2'])
     * ->injectRuleForField('username', Rule::unique('users')->ignore($userId))
     * ->toArray();
     * </code>
     *
     * @param string $field The field to which the rules should be added.
     * @param array|string|Rule $rules The rule or rules to add.
     * @return static The current instance for fluent chaining.
     * @throws InvalidArgumentException if the specified field does not exist.
     */
    public function injectRuleForField(string $field, mixed $rules): static
    {
        if (!array_key_exists($field, $this->rules)) {
            throw new InvalidArgumentException(sprintf('Rule %s not found for group %s', $field, $this->group));
        }

        if (is_string($rules)) {
            $this->rules[$field] = array_merge($this->rules[$field], explode('|', $rules));
        } elseif (is_array($rules)) {
            $this->rules[$field] = array_merge($this->rules[$field], $rules);
        } else {
            $this->rules[$field][] = $rules;
        }

        return $this;
    }

    /**
     * @param string $validationRuleClass
     * @param mixed ...$args
     * @return string|array
     */
    public static function customRule(string $validationRuleClass, ...$args): string|array
    {
        if (!class_exists($validationRuleClass) || !is_subclass_of($validationRuleClass, ValidationRule::class)) {
            throw new InvalidArgumentException(sprintf(
                'Class %s must implement %s',
                $validationRuleClass,
                ValidationRule::class
            ));
        }

        $ruleIdentifier = sprintf(
            '%s%s',
            self::CUSTOM_RULE_PREFIX,
            $validationRuleClass
        );

        if (empty($args)) {
            return $ruleIdentifier;
        }

        return [$ruleIdentifier, $args];
    }

    /**
     * Handles dynamic method calls to inject rules for a specific field.
     * For example, `injectRuleForUserName(...)` is a shortcut for `injectRuleForField('user_name', ...)`.
     *
     * <code>
     * // Dynamically inject a 'required' rule for the 'name' field
     * $rules = Rules::for('user')->injectRuleForName(['required'])->toArray();
     * </code>
     *
     * @param string $name The dynamic method name (e.g., 'injectRuleForFieldName').
     * @param array $arguments The arguments passed to the method, containing the rules.
     * @return $this The current instance for fluent chaining.
     * @throws BadMethodCallException if the method name is not a valid dynamic injector.
     */
    public function __call(string $name, array $arguments)
    {
        if (Str::startsWith($name, 'injectRuleFor')) {
            $field = Str::snake(Str::after($name, 'injectRuleFor'));
            if (!array_key_exists($field, $this->rules)) {
                throw new InvalidArgumentException(sprintf('Rule %s not found for group %s', $field, $this->group));
            }
            $this->injectRuleForField($field, $arguments[0]);
            return $this;
        }
        throw new BadMethodCallException(
            sprintf('Method %s::%s does not exist.', static::class, $name)
        );
    }

    /**
     * Returns the final validation rules as a native PHP array.
     * This is typically the final method called in the chain.
     *
     * <code>
     * $validationRules = Rules::for('user')->toArray();
     * </code>
     *
     * @return array The compiled validation rules.
     */
    public function toArray(): array
    {
        return $this->rules;
    }

    /**
     * Get an iterator for the rules, allowing the object to be used in loops.
     *
     * <code>
     * foreach (Rules::for('user') as $field => $rules) {
     * // ...
     * }
     * </code>
     *
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->rules);
    }

    /**
     * Count the number of fields in the current ruleset.
     *
     * <code>
     * $fieldCount = count(Rules::for('user'));
     * </code>
     *
     * @return int The number of fields.
     */
    public function count(): int
    {
        return count($this->rules);
    }

    /**
     * Clear all cached rules.
     *
     * @return void
     */
    public static function clearCache(): void
    {
        $allRules = self::_loadAllRules();

        foreach (array_keys($allRules) as $group) {
            Cache::forget(self::CACHE_KEY . $group);
        }

        self::$allRulesCache = null;
    }

    /**
     * Get all available rule groups.
     *
     * @return array
     */
    public static function getAllGroups(): array
    {
        return array_keys(self::_loadAllRules());
    }

    /**
     * Specify data which should be serialized to JSON, enabling `json_encode()`.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->rules;
    }

    /**
     * Convert the rules object to its JSON string representation.
     *
     * <code>
     * echo Rules::for('user');
     * </code>
     *
     * @return string The rules as a JSON string.
     */
    public function __toString()
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }
}
