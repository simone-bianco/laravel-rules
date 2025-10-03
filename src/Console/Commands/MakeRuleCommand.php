<?php

namespace SimoneBianco\LaravelRules\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class MakeRuleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:rule {name : The name of the rule file (e.g., user, common, ai_operations)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new validation rules file in the configured rules directory';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->newLine();
        $this->line("🚀 \e[1;34mCreating a new Validation Rules file...\e[0m");
        $this->line('======================================');

        try {
            // 1. Get the rule name from the user input.
            $name = (string) $this->argument('name');
            
            // Sanitize the name (convert to snake_case, remove .php extension if provided)
            $name = Str::snake($name);
            $name = str_replace('.php', '', $name);

            // 2. Get the rules directory path from config
            $rulesPath = config('laravel-rules.rules_path', 'config/laravel-rules');
            $fullPath = base_path($rulesPath);

            // 3. Create the directory if it doesn't exist
            if (!File::isDirectory($fullPath)) {
                $this->line('   - Creating rules directory...');
                File::makeDirectory($fullPath, 0755, true);
            }

            // 4. Check if file already exists
            $filePath = $fullPath . DIRECTORY_SEPARATOR . $name . '.php';
            
            if (File::exists($filePath)) {
                $this->error("   ❌ Error: Rule file '{$name}.php' already exists!");
                return self::FAILURE;
            }

            // 5. Generate the file content
            $this->line('   - Generating file content...');
            $fileContent = $this->generateFileContent($name);

            // 6. Write the file
            File::put($filePath, $fileContent);

            // 7. Display the success message
            $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $filePath);
            $this->line('   ----------------------------------------');
            $this->info("🎉 \e[1;32mValidation rules file created successfully!\e[0m");
            $this->comment("   File created at: \e[0;33m{$relativePath}\e[0m");
            $this->newLine();
            
            // 8. Show usage examples
            $this->showUsageExamples($name);

        } catch (Throwable $e) {
            $this->error('   ❌ Error: ' . $e->getMessage());
            $this->line("      File: " . $e->getFile() . " Line: " . $e->getLine());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Generate the content for the new rules file.
     *
     * @param string $name
     * @return string
     */
    protected function generateFileContent(string $name): string
    {
        $studlyName = Str::studly($name);
        
        return <<<PHP
<?php

/**
 * Validation rules for {$studlyName}.
 * 
 * This file contains validation rules that can be accessed using the Rules facade.
 * 
 * USAGE:
 * ------
 * Rules can be accessed using dot notation:
 * - Rules::for('{$name}.group_name') - Access a specific group from this file
 * - Rules::for('{$name}.group_name')->only('field') - Access specific field rules
 * 
 * EXAMPLES:
 * ---------
 * // Get all rules for a group
 * \$rules = Rules::for('{$name}.user')->toArray();
 * 
 * // Get rules for specific fields only
 * \$rules = Rules::for('{$name}.user')->only(['email', 'password'])->toArray();
 * 
 * // Inject contextual rules (required, unique, etc.)
 * \$rules = Rules::for('{$name}.user')
 *     ->injectRuleForEmail('required|unique:users,email')
 *     ->toArray();
 * 
 * STRUCTURE:
 * ----------
 * Return an associative array where:
 * - Keys are group names (e.g., 'user', 'post', 'comment')
 * - Values are arrays of field validation rules
 * 
 * RULE FORMAT:
 * ------------
 * Each field can have rules defined as:
 * 
 * 1. Key-value pairs for rules with parameters:
 *    'min' => 5
 *    'max' => 255
 *    'regex' => '/^[a-z0-9]+\$/'
 * 
 * 2. Boolean true for rules without parameters:
 *    'required' => true
 *    'email' => true
 * 
 * 3. Numeric keys for string rules:
 *    ['required', 'string', 'email']
 * 
 * 4. Arrays for rules with multiple parameters:
 *    'in' => ['admin', 'user', 'guest']
 *    'mimes' => 'jpeg,png,jpg'
 * 
 * CUSTOM VALIDATION RULES:
 * ------------------------
 * Use Rules::customRule() for custom validation rule classes:
 * 
 * Rules::customRule(YourCustomRule::class)
 * Rules::customRule(YourCustomRule::class, \$param1, \$param2)
 * 
 * Example:
 * 'username' => [
 *     'string',
 *     Rules::customRule(ValidUsername::class),
 *     'min' => 5,
 *     'max' => 255,
 * ]
 * 
 * BEST PRACTICES:
 * ---------------
 * - Keep structural rules here (min, max, regex, format, etc.)
 * - Avoid contextual rules (required, nullable, unique, confirmed)
 * - Add contextual rules at runtime using injectRuleForField()
 * - Group related fields together
 * - Use descriptive group names
 */

use SimoneBianco\LaravelRules\Rules;

return [
    // Example group - replace with your own groups
    'example' => [
        'field_name' => [
            'min' => 2,
            'max' => 255,
        ],
        'email' => [
            'email' => true,
            'max' => 255,
        ],
    ],
    
    // Add more groups as needed
    // 'another_group' => [
    //     'field' => [
    //         'rule' => 'value',
    //     ],
    // ],
];

PHP;
    }

    /**
     * Show usage examples for the newly created file.
     *
     * @param string $name
     * @return void
     */
    protected function showUsageExamples(string $name): void
    {
        $this->line('   <fg=cyan>Usage Examples:</>');
        $this->line('   ----------------------------------------');
        $this->comment("   // Get all rules for a group");
        $this->line("   \$rules = Rules::for('{$name}.example')->toArray();");
        $this->newLine();
        $this->comment("   // Get specific fields only");
        $this->line("   \$rules = Rules::for('{$name}.example')->only(['field_name'])->toArray();");
        $this->newLine();
        $this->comment("   // Inject contextual rules");
        $this->line("   \$rules = Rules::for('{$name}.example')");
        $this->line("       ->injectRuleForFieldName('required')");
        $this->line("       ->toArray();");
        $this->newLine();
        $this->info('   💡 Tip: Run "php artisan rules:generate-ide-helper" to get autocompletion!');
        $this->newLine();
    }
}

