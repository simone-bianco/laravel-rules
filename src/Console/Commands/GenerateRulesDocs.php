<?php

namespace SimoneBianco\LaravelRules\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SimoneBianco\LaravelRules\Rules;
use Throwable;

class GenerateRulesDocs extends Command
{
    /**
     * The signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rules:generate-ide-helper';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Generate an IDE helper file for autocompletion for the laravel-rules package.';



    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->newLine();
        $this->line("🚀 \e[1;34mGenerating IDE Helper file for Laravel Rules...\e[0m");
        $this->line("========================================================");

        try {
            $this->line("   - Loading rules from directory...");

            // Get all rule groups from the new system
            $allGroups = Rules::getAllGroups();

            if (empty($allGroups)) {
                $this->warn('   ⚠️  No validation rules found. An empty helper file will be generated.');
                $this->comment('   💡 Tip: Run "php artisan make:rule <name>" to create your first rule file.');
                $uniqueFields = [];
            } else {
                $this->info('   - Found ' . count($allGroups) . ' rule groups.');

                // Collect all unique field names across all groups
                $allFields = [];
                foreach ($allGroups as $group) {
                    try {
                        $rules = Rules::for($group);
                        foreach ($rules as $field => $fieldRules) {
                            $allFields[] = $field;
                        }
                    } catch (\Exception $e) {
                        // Skip groups that can't be loaded
                        continue;
                    }
                }

                $uniqueFields = array_unique($allFields);
                sort($uniqueFields);

                $this->info('   - Found ' . count($uniqueFields) . ' unique validation fields.');
            }

            $docLines = [];
            foreach ($uniqueFields as $field) {
                $methodName = 'injectRuleFor' . Str::studly($field);
                $description = "Injects a validation rule for the '{$field}' field.";
                // Adds the fully qualified namespace in the return type for clarity
                $docLines[] = " * @method static \\SimoneBianco\\LaravelRules\\Rules {$methodName}(string[]|string|\\Illuminate\\Validation\\Rule \$rules) {$description}";
            }
            $generatedDocs = implode("\n", $docLines);

            $this->line('   - Building the helper file content...');
            $fileContent = $this->createHelperFileContent($generatedDocs);

            $outputPath = config('laravel-rules.ide_helper_path', '_ide_helper_rules.php');
            $fullPath = base_path($outputPath);
            File::put($fullPath, $fileContent);

            $this->line("   ----------------------------------------------------");
            $this->info("🎉 \e[1;32mIDE Helper file generated successfully!\e[0m");
            $this->comment("   File created at: \e[0;33m{$outputPath}\e[0m");
            $this->comment('   You may need to restart your IDE for the changes to take effect.');
            $this->newLine();

        } catch (Throwable $e) {
            $this->error("   ❌ Unexpected error: " . $e->getMessage());
            $this->line("      File: " . $e->getFile() . " Line: " . $e->getLine());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Creates the helper file template with the correct PHPDoc syntax.
     *
     * @param string $phpdocMethods
     * @return string
     */
    protected function createHelperFileContent(string $phpdocMethods): string
    {
        $namespace = 'SimoneBianco\\LaravelRules';

        // The PHPDoc block is built and inserted BEFORE the class
        return <<<PHP
<?php

// @formatter:off
// phpcs:ignoreFile

/**
 * A helper file for SimoneBianco\LaravelRules, to provide autocompletion
 * information to your IDE.
 *
 * This file should not be included in your code, but only analyzed by your IDE!
 *
 * @see \\{$namespace}\\Rules
 */

namespace {$namespace} {
    // This class is redefined here to add the PHPDoc block.
    // The `if (false)` block ensures that this code is never executed at runtime.
    if (false) {
        /**
{$phpdocMethods}
         */
        class Rules
        {
            // The class body MUST be empty.
        }
    }
}

PHP;
    }
}
