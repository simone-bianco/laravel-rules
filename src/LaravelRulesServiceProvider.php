<?php

namespace SimoneBianco\LaravelRules;

use SimoneBianco\LaravelRules\Console\Commands\GenerateRulesDocs;
use SimoneBianco\LaravelRules\Console\Commands\MakeRuleCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Commands\InstallCommand;

class LaravelRulesServiceProvider extends PackageServiceProvider
{
    /**
     * Configure the package.
     *
     * @param Package $package
     * @return void
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-rules')
            ->hasConfigFile('laravel-rules')
            ->hasCommands([
                MakeRuleCommand::class,
                GenerateRulesDocs::class,
            ])
            ->hasInstallCommand(function(InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->askToStarRepoOnGitHub('simonebianco/laravel-rules');
            });
    }

    /**
     * Register package services.
     *
     * @return void
     */
    public function packageRegistered(): void
    {
        $this->app->singleton('laravel-rules.factory', fn() => new RulesFactory());
        $this->app->alias('laravel-rules.factory', RulesFactory::class);
    }
}
