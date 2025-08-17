<?php

namespace SimoneBianco\LaravelRules;

use BadMethodCallException;
use Illuminate\Support\Facades\Cache;
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
    protected const string CACHE_KEY = 'rules_';

    protected string $group = '';
    protected array $rules = [];

    protected static function _getFromCache(string $group): ?array
    {
        return Cache::get(self::CACHE_KEY . $group);
    }

    protected static function _putInCache(string $group, array $rules): void
    {
        Cache::put(self::CACHE_KEY . $group, $rules, 3600);
    }

    protected static function _getFieldsRules(string $group): array
    {
        $fieldsRules = config(sprintf('%s.%s', self::CONFIG_FILE, $group));

        if (empty($fieldsRules)) {
            throw new InvalidArgumentException(sprintf('Rules not found for group %s', $group));
        }
        return $fieldsRules;
    }

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
                if (is_array($value)) {
                    $value = implode(',', $value);
                }
                $fieldRules[] = "$rule:$value";
            }
            $laravelRules[$field] = $fieldRules;
        }

        self::_putInCache($group, $laravelRules);
        return $laravelRules;
    }

    public function __construct(string $group, array $rules)
    {
        $this->group = $group;
        $this->rules = $rules;
    }

    public static function for(string $group): static
    {
        return (new static($group, self::_getLaravelRules($group)));
    }

    public static function forOrphansField(string $field): static
    {
        return (new static(self::ORPHANS_GROUP, self::_getLaravelRules(self::ORPHANS_GROUP, [$field])));
    }

    public function only(array $fields): self
    {
        $this->rules = array_intersect_key($this->rules, array_flip($fields));
        return $this;
    }

    public function except(array $fields): self
    {
        $this->rules = array_diff_key($this->rules, array_flip($fields));
        return $this;
    }

    public function injectRuleForField(string $field, array|string|Rule $rules): static
    {
        if (!array_key_exists($field, $this->rules)) {
            throw new InvalidArgumentException(sprintf('Rule %s not found for group %s', $field, $this->group));
        }

        $newRules = [];
        if ($rules instanceof Rule) {
            $newRules[] = $rules;
        } elseif (is_string($rules)) {
            $newRules = explode('|', $rules);
        } else {
            $newRules = $rules;
        }

        $this->rules[$field] = array_merge($this->rules[$field], $newRules);
        return $this;
    }

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

    public function toArray(): array
    {
        return $this->rules;
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->rules);
    }

    public function count(): int
    {
        return count($this->rules);
    }

    public function jsonSerialize(): array
    {
        return $this->rules;
    }

    public function __toString()
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }
}
