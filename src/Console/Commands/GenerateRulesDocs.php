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
     * La firma del comando console.
     *
     * @var string
     */
    protected $signature = 'rules:generate-ide-helper';

    /**
     * La descrizione del comando console.
     *
     * @var string
     */
    protected $description = 'Genera un file helper per l\'autocompletamento dell\'IDE per il package laravel-rules.';

    /**
     * Il percorso del file helper da generare, relativo alla root del progetto.
     *
     * @var string
     */
    protected string $outputPath = '_ide_helper_rules.php';

    /**
     * Esegui il comando console.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->newLine();
        $this->line("🚀 \e[1;34mGenerazione del file IDE Helper per Laravel Rules...\e[0m");
        $this->line("========================================================");

        try {
            $this->line("   - Lettura di \e[0;33mconfig/validation.php\e[0m...");
            $validationConfig = config(Rules::CONFIG_FILE);
            if (!$validationConfig) {
                $this->error('   ❌ File di configurazione config/validation.php non trovato o vuoto. Operazione annullata.');
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
                $this->warn('   ⚠️  Nessun campo di validazione trovato. Verrà generato un file helper vuoto.');
            } else {
                $this->info('   - Trovati ' . count($uniqueFields) . ' campi di validazione univoci.');
            }

            $docLines = [];
            foreach ($uniqueFields as $field) {
                $methodName = 'injectRuleFor' . Str::studly($field);
                $description = "Injects a validation rule for the '{$field}' field.";
                // Aggiunge il namespace completo nel tipo di ritorno per chiarezza
                $docLines[] = " * @method static \\SimoneBianco\\LaravelRules\\Rules {$methodName}(array|string|\\Illuminate\\Validation\\Rule \$rules) {$description}";
            }
            $generatedDocs = implode("\n", $docLines);

            $this->line('   - Costruzione del contenuto del file helper...');
            $fileContent = $this->createHelperFileContent($generatedDocs);

            $fullPath = base_path($this->outputPath);
            File::put($fullPath, $fileContent);

            $this->line("   ----------------------------------------------------");
            $this->info("🎉 \e[1;32mFile IDE Helper generato con successo!\e[0m");
            $this->comment("   File creato in: \e[0;33m{$this->outputPath}\e[0m");
            $this->comment('   Potrebbe essere necessario riavviare l\'IDE per applicare le modifiche.');
            $this->newLine();

        } catch (Throwable $e) {
            $this->error("   ❌ Errore imprevisto: " . $e->getMessage());
            $this->line("      File: " . $e->getFile() . " Linea: " . $e->getLine());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Crea il template del file helper con la sintassi PHPDoc corretta.
     *
     * @param string $phpdocMethods
     * @return string
     */
    protected function createHelperFileContent(string $phpdocMethods): string
    {
        $namespace = 'SimoneBianco\\LaravelRules';

        // ** LA CORREZIONE È QUI **
        // Il blocco PHPDoc viene costruito e inserito PRIMA della classe
        return <<<PHP
<?php

// @formatter:off
// phpcs:ignoreFile

/**
 * Un file helper per SimoneBianco\LaravelRules, per fornire informazioni
 * di autocompletamento al tuo IDE.
 *
 * Questo file non dovrebbe essere incluso nel tuo codice, ma solo analizzato dal tuo IDE!
 *
 * @see \\{$namespace}\\Rules
 */

namespace {$namespace} {
    // Questa classe viene ridefinita qui per aggiungere il blocco PHPDoc.
    // Il blocco `if (false)` assicura che questo codice non venga mai eseguito a runtime.
    if (false) {
        /**
{$phpdocMethods}
         */
        class Rules
        {
            // Il corpo della classe DEVE essere vuoto.
        }
    }
}

PHP;
    }
}
