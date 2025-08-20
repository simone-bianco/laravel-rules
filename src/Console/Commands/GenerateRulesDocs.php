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
     * The path for the helper file to be generated, relative to the project root.
     *
     * @var string
     */
    protected string $outputPath = '_ide_helper_rules.php';

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
            $this->line("   - Reading \e[0;33mconfig/laravel-rules.php\e[0m...");
            $validationConfig = config(Rules::CONFIG_FILE);
            if (!$validationConfig) {
                $this->error('   ❌ Configuration file config/laravel-rules.php not found or is empty. Operation cancelled.');
                return self::FAILURE;
            }

            $allFields = [];
            foreach ($validationConfig as $group => $fields) {
                if (is_array($fields)) {
                    $allFields = array_merge($allFields, array_keys($fields));
                }
            }
            $uniqueFields = array_unique($allFields);
            sort($uniqueFields);

            if (empty($uniqueFields)) {
                $this->warn('   ⚠️  No validation fields found. An empty helper file will be generated.');
            } else {
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

            $fullPath = base_path($this->outputPath);
            File::put($fullPath, $fileContent);

            $this->line("   ----------------------------------------------------");
            $this->info("🎉 \e[1;32mIDE Helper file generated successfully!\e[0m");
            $this->comment("   File created at: \e[0;33m{$this->outputPath}\e[0m");
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
