<?php

namespace SimoneBianco\LaravelRules;

use Illuminate\Support\ServiceProvider;
use SimoneBianco\LaravelRules\Console\Commands\GenerateRulesDocs;

class LaravelRulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('laravel-rules.factory', fn() => new RulesFactory());
        $this->app->alias('laravel-rules.factory', RulesFactory::class);

        $this->mergeConfigFrom(__DIR__ . '/../config/laravel-rules.php', 'laravel-rules');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {

            $this->publishes([
                __DIR__ . '/../config/laravel-rules.php' => config_path('laravel-rules.php'),
            ], 'laravel-rules-config');

            $this->commands([
                GenerateRulesDocs::class,
            ]);
        }
    }
}
