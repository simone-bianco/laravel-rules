# Laravel Rules (simone-bianco/laravel-rules)

Gestione dinamica, centralizzata e cache delle regole di validazione Laravel, con generazione di helper IDE senza modificare la classe originale.

## Obiettivi
- Centralizzare definizioni strutturali delle regole (min, max, pattern, ecc.).
- Escludere dal config tutte le regole contestuali (`required`, `nullable`, `unique`, `confirmed`, ecc.).
- Aggiungere dinamicamente regole contestuali a runtime via API fluente.
- Offrire autocompletamento per metodi dinamici `injectRuleFor*` tramite file helper generato.

## Installazione (path repository locale)
Aggiungi (se non presente) nel composer.json root:
```json
"repositories": [
  {"type": "path", "url": "packages/simone-bianco/laravel-rules", "options": {"symlink": true}}
]
```
Quindi installa:
```bash
composer require simone-bianco/laravel-rules:*
```

Il service provider è auto‑discovered. È creato anche un alias retro‑compatibile `\App\Features\Rules`.

## Pubblicazione Configurazione
Pubblica il file di configurazione di esempio (se non ne hai già uno):
```bash
php artisan vendor:publish --tag=laravel-rules-config
```
Questo crea `config/validation.php` con una struttura iniziale:
```php
return [
    'user' => [
        'name' => [ 'min' => 2, 'max' => 255 ],
        'email' => [ 'max' => 255, 'email' => true ],
    ],
    'orphans' => [ /* campi standalone */ ],
];
```
NOTA: Non inserire regole contestuali (required, unique, nullable, confirmed...).

## Generazione Helper IDE
Per ottenere autocompletamento dei metodi dinamici `injectRuleForXyz` senza toccare la classe:
```bash
php artisan docs:generate-rules
```
Genera `_ide_helper_laravel_rules.php` (modificabile con `--path=`) che gli IDE indicizzano.
Rigenera il file ogni volta che aggiungi/rimuovi campi nel config.

## API Principali
```php
use SimoneBianco\LaravelRules\Rules; // oppure \App\Features\Rules

// Regole complete per un gruppo
$rules = Rules::for('user')->toArray();

// Solo alcuni campi
$loginRules = Rules::for('user')->only(['email', 'password']);

// Escludere campi
$updateRules = Rules::for('user')->except(['password']);

// Campo orfano
$slugRules = Rules::forOrphansField('slug')->toArray();

// Iniettare regole aggiuntive (contestuali) per update
$rules = Rules::for('user')
    ->injectRuleForField('email', 'required|email|unique:users,email,'.$userId)
    ->injectRuleForName(['required', 'string']) // tramite metodo dinamico
    ->toArray();
```

### Metodi Dinamici
Ogni campo definito nel gruppo genera un metodo virtuale: `injectRuleFor{StudlyCaseCampo}`.
Esempio: campo `first_name` => `injectRuleForFirstName()`.

## Esempio in una FormRequest
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

## Formato Configurazione
Ogni campo è un array associativo `regola => valore`:
```php
'username' => [
  'min' => 5,
  'max' => 255,
  'regex' => '/^[a-z0-9.]+$/',
],
```
Array come valore vengono trasformati in liste CSV (`in:admin,user,guest`).

## Caching
Le regole elaborate sono cache‑izzate (TTL 1h). Svuota con:
```bash
php artisan cache:clear
```

## Comandi Artisan
| Comando | Descrizione |
|---------|-------------|
| `rules:publish-config` | Pubblica il file `config/validation.php`. |
| `docs:generate-rules` | Genera `_ide_helper_laravel_rules.php` con i metodi dinamici. |

## Perché Non Modificare La Classe
Il file helper evita merge noise e mantiene la classe minimale, aderendo al principio SRP.

## Buone Pratiche
- Mantieni il config privo di regole contestuali.
- Rigenera l'helper dopo aver aggiunto nuovi campi.
- Usa `only()` quando validi subset (login, update parziali).
- Evita duplicazioni: centralizza sempre lunghezze e pattern.

## Licenza
MIT

