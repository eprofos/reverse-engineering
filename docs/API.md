# Documentation API - ReverseEngineeringBundle

Cette documentation décrit l'API publique du ReverseEngineeringBundle, incluant tous les services, méthodes et interfaces disponibles pour les développeurs.

## 📋 Table des Matières

- [Services Principaux](#services-principaux)
- [Exceptions](#exceptions)
- [Configuration](#configuration)
- [Commandes CLI](#commandes-cli)
- [Exemples d'Utilisation](#exemples-dutilisation)

## 🔧 Services Principaux

### ReverseEngineeringService

**Service principal** pour l'orchestration du processus de génération d'entités.

#### Constructeur

```php
public function __construct(
    private readonly DatabaseAnalyzer $databaseAnalyzer,
    private readonly MetadataExtractor $metadataExtractor,
    private readonly EntityGenerator $entityGenerator,
    private readonly FileWriter $fileWriter,
    private readonly array $config = []
)
```

#### Méthodes Publiques

##### `generateEntities(array $options = []): array`

Génère les entités à partir de la base de données.

**Paramètres :**
- `$options` (array) : Options de génération
  - `tables` (array) : Tables spécifiques à traiter
  - `exclude` (array) : Tables à exclure
  - `namespace` (string) : Namespace des entités
  - `output_dir` (string) : Répertoire de sortie
  - `force` (bool) : Forcer l'écrasement des fichiers
  - `dry_run` (bool) : Mode simulation
  - `generate_repository` (bool) : Générer les repositories

**Retour :**
```php
[
    'entities' => array,      // Entités générées
    'files' => array,         // Fichiers créés
    'tables_processed' => int // Nombre de tables traitées
]
```

**Exceptions :**
- `ReverseEngineeringException` : Erreur générale du processus

**Exemple :**
```php
$service = $container->get(ReverseEngineeringService::class);

$result = $service->generateEntities([
    'tables' => ['users', 'products'],
    'namespace' => 'App\\Entity\\Shop',
    'output_dir' => 'src/Entity/Shop',
    'force' => true
]);

echo "Entités générées : " . count($result['entities']);
```

##### `validateDatabaseConnection(): bool`

Valide la connexion à la base de données.

**Retour :** `true` si la connexion est valide

**Exceptions :**
- `ReverseEngineeringException` : Erreur de connexion

##### `getAvailableTables(): array`

Récupère la liste des tables disponibles.

**Retour :** Array des noms de tables

##### `getTableInfo(string $tableName): array`

Récupère les informations détaillées d'une table.

**Paramètres :**
- `$tableName` (string) : Nom de la table

**Retour :** Métadonnées de la table

---

### DatabaseAnalyzer

**Service d'analyse** de la structure de base de données.

#### Méthodes Publiques

##### `analyzeTables(array $include = [], array $exclude = []): array`

Analyse les tables de la base de données avec filtrage.

**Paramètres :**
- `$include` (array) : Tables à inclure (toutes si vide)
- `$exclude` (array) : Tables à exclure

**Retour :** Array des noms de tables filtrées

**Exemple :**
```php
$analyzer = $container->get(DatabaseAnalyzer::class);

// Analyser toutes les tables sauf les tables système
$tables = $analyzer->analyzeTables([], ['information_schema', 'performance_schema']);

// Analyser seulement les tables spécifiées
$tables = $analyzer->analyzeTables(['users', 'products', 'orders']);
```

##### `getTableColumns(string $tableName): array`

Récupère les colonnes d'une table avec leurs propriétés.

**Paramètres :**
- `$tableName` (string) : Nom de la table

**Retour :**
```php
[
    [
        'name' => 'id',
        'type' => 'int',
        'nullable' => false,
        'default' => null,
        'auto_increment' => true,
        'primary' => true,
        'length' => null,
        'precision' => null,
        'scale' => null
    ],
    // ... autres colonnes
]
```

##### `getForeignKeys(string $tableName): array`

Récupère les clés étrangères d'une table.

**Paramètres :**
- `$tableName` (string) : Nom de la table

**Retour :**
```php
[
    [
        'column' => 'user_id',
        'referenced_table' => 'users',
        'referenced_column' => 'id',
        'on_delete' => 'CASCADE',
        'on_update' => 'RESTRICT'
    ],
    // ... autres FK
]
```

##### `getIndexes(string $tableName): array`

Récupère les index d'une table.

##### `testConnection(): bool`

Teste la connexion à la base de données.

##### `listTables(): array`

Liste toutes les tables de la base de données.

---

### MetadataExtractor

**Service d'extraction** et de transformation des métadonnées.

#### Méthodes Publiques

##### `extractTableMetadata(string $tableName, array $allTables = []): array`

Extrait et transforme les métadonnées d'une table.

**Paramètres :**
- `$tableName` (string) : Nom de la table
- `$allTables` (array) : Liste de toutes les tables (pour les relations)

**Retour :**
```php
[
    'table_name' => 'users',
    'class_name' => 'User',
    'columns' => [
        [
            'name' => 'id',
            'property_name' => 'id',
            'php_type' => 'int',
            'doctrine_type' => 'integer',
            'nullable' => false,
            'primary' => true,
            'generated' => true
        ],
        // ... autres colonnes
    ],
    'relations' => [
        [
            'type' => 'ManyToOne',
            'property' => 'category',
            'target_entity' => 'Category',
            'join_column' => 'category_id'
        ]
    ],
    'repository' => 'UserRepository'
]
```

##### `mapColumnType(string $dbType, string $driver): string`

Mappe un type de base de données vers un type PHP/Doctrine.

**Paramètres :**
- `$dbType` (string) : Type de la base de données
- `$driver` (string) : Driver utilisé (pdo_mysql, pdo_pgsql, pdo_sqlite)

**Retour :** Type PHP correspondant

**Exemple :**
```php
$extractor = $container->get(MetadataExtractor::class);

echo $extractor->mapColumnType('VARCHAR', 'pdo_mysql'); // 'string'
echo $extractor->mapColumnType('INTEGER', 'pdo_sqlite'); // 'int'
echo $extractor->mapColumnType('TIMESTAMP', 'pdo_pgsql'); // 'DateTimeInterface'
```

##### `detectRelations(array $foreignKeys, array $allTables): array`

Détecte et configure les relations entre entités.

##### `normalizeNames(string $name): string`

Normalise les noms (table → classe, colonne → propriété).

---

### EntityGenerator

**Service de génération** du code PHP des entités.

#### Méthodes Publiques

##### `generateEntity(string $tableName, array $metadata, array $options = []): array`

Génère le code d'une entité à partir des métadonnées.

**Paramètres :**
- `$tableName` (string) : Nom de la table
- `$metadata` (array) : Métadonnées de la table
- `$options` (array) : Options de génération
  - `namespace` (string) : Namespace de l'entité
  - `use_annotations` (bool) : Utiliser annotations au lieu d'attributs
  - `generate_repository` (bool) : Générer le repository

**Retour :**
```php
[
    'class_name' => 'User',
    'namespace' => 'App\\Entity',
    'filename' => 'User.php',
    'content' => '<?php...',
    'repository' => [
        'class_name' => 'UserRepository',
        'filename' => 'UserRepository.php',
        'content' => '<?php...'
    ]
]
```

##### `generateRepository(string $entityName, array $options = []): array`

Génère le code d'un repository Doctrine.

##### `renderTemplate(string $template, array $variables): string`

Rend un template Twig avec les variables fournies.

**Paramètres :**
- `$template` (string) : Nom du template
- `$variables` (array) : Variables pour le template

---

### FileWriter

**Service d'écriture** sécurisée des fichiers.

#### Méthodes Publiques

##### `writeEntityFile(array $entity, ?string $outputDir = null, bool $force = false): string`

Écrit un fichier d'entité sur le disque.

**Paramètres :**
- `$entity` (array) : Données de l'entité générée
- `$outputDir` (string|null) : Répertoire de sortie
- `$force` (bool) : Forcer l'écrasement si le fichier existe

**Retour :** Chemin du fichier créé

**Exceptions :**
- `FileWriteException` : Erreur d'écriture

##### `writeRepositoryFile(array $repository, ?string $outputDir = null, bool $force = false): string`

Écrit un fichier de repository sur le disque.

##### `validateOutputDirectory(string $directory): bool`

Valide qu'un répertoire est accessible en écriture.

##### `handleFileConflict(string $filePath, bool $force): bool`

Gère les conflits de fichiers existants.

---

## ⚠️ Exceptions

### Hiérarchie des Exceptions

```
ReverseEngineeringException (base)
├── DatabaseConnectionException
├── MetadataExtractionException
├── EntityGenerationException
└── FileWriteException
```

### ReverseEngineeringException

**Exception de base** pour toutes les erreurs du bundle.

```php
class ReverseEngineeringException extends \Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    )
}
```

### DatabaseConnectionException

**Exception spécialisée** pour les erreurs de connexion à la base de données.

**Cas d'usage :**
- Paramètres de connexion invalides
- Base de données inaccessible
- Driver non disponible

### MetadataExtractionException

**Exception spécialisée** pour les erreurs d'extraction de métadonnées.

**Cas d'usage :**
- Table inexistante
- Permissions insuffisantes
- Structure de table invalide

### EntityGenerationException

**Exception spécialisée** pour les erreurs de génération d'entités.

**Cas d'usage :**
- Template invalide
- Métadonnées corrompues
- Erreur de rendu Twig

### FileWriteException

**Exception spécialisée** pour les erreurs d'écriture de fichiers.

**Cas d'usage :**
- Permissions insuffisantes
- Espace disque insuffisant
- Chemin invalide

---

## ⚙️ Configuration

### Structure de Configuration

```yaml
# config/packages/reverse_engineering.yaml
reverse_engineering:
    database:
        driver: pdo_mysql
        host: localhost
        port: 3306
        dbname: myapp
        user: username
        password: password
        charset: utf8mb4
        options: []
    
    generation:
        namespace: App\Entity
        output_dir: src/Entity
        generate_repository: true
        use_annotations: false
        tables: []
        exclude_tables: []
        
    templates:
        entity: '@ReverseEngineering/entity.php.twig'
        repository: '@ReverseEngineering/repository.php.twig'
```

### Options de Configuration

#### Section `database`
- `driver` : Driver Doctrine DBAL (pdo_mysql, pdo_pgsql, pdo_sqlite)
- `host` : Hôte de la base de données
- `port` : Port de connexion
- `dbname` : Nom de la base de données
- `user` : Nom d'utilisateur
- `password` : Mot de passe
- `charset` : Encodage des caractères
- `options` : Options supplémentaires du driver

#### Section `generation`
- `namespace` : Namespace par défaut des entités
- `output_dir` : Répertoire de sortie par défaut
- `generate_repository` : Générer les repositories automatiquement
- `use_annotations` : Utiliser annotations au lieu d'attributs PHP 8+
- `tables` : Tables à traiter (toutes si vide)
- `exclude_tables` : Tables à exclure

#### Section `templates`
- `entity` : Template pour les entités
- `repository` : Template pour les repositories

---

## 🖥️ Commandes CLI

### reverse:generate

**Commande principale** pour la génération d'entités.

#### Syntaxe

```bash
php bin/console reverse:generate [options]
```

#### Options

| Option | Raccourci | Description | Valeur par défaut |
|--------|-----------|-------------|-------------------|
| `--tables` | `-t` | Tables à traiter | Toutes |
| `--exclude` | `-e` | Tables à exclure | Aucune |
| `--namespace` | `-n` | Namespace des entités | Configuration |
| `--output-dir` | `-o` | Répertoire de sortie | Configuration |
| `--force` | `-f` | Forcer l'écrasement | false |
| `--dry-run` | `-d` | Mode simulation | false |
| `--verbose` | `-v` | Mode verbeux | false |

#### Exemples

```bash
# Génération basique
php bin/console reverse:generate

# Tables spécifiques
php bin/console reverse:generate --tables=users --tables=products

# Avec namespace personnalisé
php bin/console reverse:generate --namespace="App\Entity\Shop" --output-dir="src/Entity/Shop"

# Mode simulation
php bin/console reverse:generate --dry-run --verbose

# Force l'écrasement
php bin/console reverse:generate --force
```

#### Codes de Retour

- `0` : Succès
- `1` : Erreur générale
- `2` : Erreur de configuration
- `3` : Erreur de connexion base de données
- `4` : Erreur de génération

---

## 💡 Exemples d'Utilisation

### Utilisation Programmatique

#### Génération Simple

```php
use App\Service\ReverseEngineeringService;

class MyController
{
    public function __construct(
        private ReverseEngineeringService $reverseService
    ) {}
    
    public function generateEntities(): Response
    {
        try {
            $result = $this->reverseService->generateEntities([
                'tables' => ['users', 'products'],
                'namespace' => 'App\\Entity\\Generated',
                'output_dir' => 'src/Entity/Generated'
            ]);
            
            return new JsonResponse([
                'success' => true,
                'entities_count' => count($result['entities']),
                'files_created' => count($result['files'])
            ]);
            
        } catch (ReverseEngineeringException $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
```

#### Validation de Connexion

```php
use App\Service\ReverseEngineeringService;

class DatabaseController
{
    public function testConnection(ReverseEngineeringService $service): Response
    {
        try {
            $isValid = $service->validateDatabaseConnection();
            
            return new JsonResponse([
                'connection' => $isValid ? 'OK' : 'FAILED'
            ]);
            
        } catch (ReverseEngineeringException $e) {
            return new JsonResponse([
                'connection' => 'ERROR',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
```

#### Analyse de Tables

```php
use App\Service\DatabaseAnalyzer;

class SchemaController
{
    public function analyzeTables(DatabaseAnalyzer $analyzer): Response
    {
        $tables = $analyzer->listTables();
        $analysis = [];
        
        foreach ($tables as $table) {
            $columns = $analyzer->getTableColumns($table);
            $foreignKeys = $analyzer->getForeignKeys($table);
            
            $analysis[$table] = [
                'columns_count' => count($columns),
                'has_foreign_keys' => !empty($foreignKeys),
                'columns' => array_column($columns, 'name')
            ];
        }
        
        return new JsonResponse($analysis);
    }
}
```

### Service Personnalisé

```php
use App\Service\ReverseEngineeringService;
use App\Service\EntityGenerator;

class CustomEntityService
{
    public function __construct(
        private ReverseEngineeringService $reverseService,
        private EntityGenerator $entityGenerator
    ) {}
    
    public function generateWithCustomTemplate(string $tableName): string
    {
        // 1. Obtenir les métadonnées
        $metadata = $this->reverseService->getTableInfo($tableName);
        
        // 2. Générer avec template personnalisé
        $entity = $this->entityGenerator->generateEntity(
            $tableName,
            $metadata,
            [
                'template' => 'custom_entity.php.twig',
                'namespace' => 'App\\Entity\\Custom'
            ]
        );
        
        return $entity['content'];
    }
}
```

### Gestion d'Erreurs Avancée

```php
use App\Exception\DatabaseConnectionException;
use App\Exception\MetadataExtractionException;
use App\Exception\EntityGenerationException;
use App\Exception\FileWriteException;

class RobustGenerationService
{
    public function generateWithErrorHandling(array $options): array
    {
        $results = [
            'success' => [],
            'errors' => [],
            'warnings' => []
        ];
        
        try {
            $result = $this->reverseService->generateEntities($options);
            $results['success'] = $result;
            
        } catch (DatabaseConnectionException $e) {
            $results['errors'][] = [
                'type' => 'database_connection',
                'message' => 'Impossible de se connecter à la base de données',
                'details' => $e->getMessage()
            ];
            
        } catch (MetadataExtractionException $e) {
            $results['errors'][] = [
                'type' => 'metadata_extraction',
                'message' => 'Erreur lors de l\'extraction des métadonnées',
                'details' => $e->getMessage()
            ];
            
        } catch (EntityGenerationException $e) {
            $results['errors'][] = [
                'type' => 'entity_generation',
                'message' => 'Erreur lors de la génération des entités',
                'details' => $e->getMessage()
            ];
            
        } catch (FileWriteException $e) {
            $results['errors'][] = [
                'type' => 'file_write',
                'message' => 'Erreur lors de l\'écriture des fichiers',
                'details' => $e->getMessage()
            ];
        }
        
        return $results;
    }
}
```

---

## 📚 Références

- [Symfony Service Container](https://symfony.com/doc/current/service_container.html)
- [Doctrine DBAL Types](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html)
- [Twig Templates](https://twig.symfony.com/doc/3.x/)
- [PHP 8 Attributes](https://www.php.net/manual/en/language.attributes.overview.php)

---

**Cette API est stable et rétrocompatible dans la version 0.x du bundle.**