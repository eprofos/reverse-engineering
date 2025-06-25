# Cas d'Usage Avancés - ReverseEngineeringBundle

Ce guide présente des scénarios d'utilisation avancés et des techniques de personnalisation pour tirer le maximum du ReverseEngineeringBundle.

## 🎯 Table des Matières

- [Intégration dans des Projets Existants](#intégration-dans-des-projets-existants)
- [Personnalisation des Templates](#personnalisation-des-templates)
- [Automatisation et CI/CD](#automatisation-et-cicd)
- [Optimisation pour Grandes Bases de Données](#optimisation-pour-grandes-bases-de-données)
- [Intégration avec d'Autres Outils](#intégration-avec-dautres-outils)
- [Cas d'Usage Métier Spécifiques](#cas-dusage-métier-spécifiques)

---

## 🏗️ Intégration dans des Projets Existants

### Migration Progressive d'Applications Legacy

Stratégie pour migrer une application legacy vers Symfony sans interruption de service.

#### Étape 1 : Analyse et Planification

```bash
# 1. Analyser la structure existante
php bin/console reverse:generate --dry-run --verbose > analysis.txt

# 2. Identifier les tables critiques
php bin/console reverse:generate --dry-run --tables=users --tables=orders
```

#### Étape 2 : Migration par Modules

```bash
#!/bin/bash
# scripts/migrate-legacy.sh

# Module Utilisateurs
echo "Migration du module utilisateurs..."
php bin/console reverse:generate \
    --tables=users --tables=user_profiles --tables=user_permissions \
    --namespace="App\\Entity\\User" \
    --output-dir="src/Entity/User" \
    --force

# Module Produits
echo "Migration du module produits..."
php bin/console reverse:generate \
    --tables=products --tables=categories --tables=product_images \
    --namespace="App\\Entity\\Product" \
    --output-dir="src/Entity/Product" \
    --force

# Module Commandes
echo "Migration du module commandes..."
php bin/console reverse:generate \
    --tables=orders --tables=order_items --tables=payments \
    --namespace="App\\Entity\\Order" \
    --output-dir="src/Entity/Order" \
    --force
```

#### Étape 3 : Coexistence avec l'Ancien Système

```php
// src/Service/LegacyBridgeService.php
class LegacyBridgeService
{
    public function __construct(
        private EntityManagerInterface $em,
        private LegacyDatabaseConnection $legacyDb
    ) {}
    
    public function syncUserFromLegacy(int $legacyUserId): User
    {
        // Récupérer depuis l'ancien système
        $legacyData = $this->legacyDb->fetchUser($legacyUserId);
        
        // Créer/mettre à jour l'entité Doctrine
        $user = $this->em->getRepository(User::class)
            ->findOneBy(['legacyId' => $legacyUserId]) ?? new User();
            
        $user->setLegacyId($legacyUserId);
        $user->setEmail($legacyData['email']);
        $user->setFirstName($legacyData['first_name']);
        
        $this->em->persist($user);
        $this->em->flush();
        
        return $user;
    }
}
```

### Intégration avec des Entités Existantes

```php
// src/Entity/BaseEntity.php - Classe de base commune
abstract class BaseEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected int $id;
    
    #[ORM\Column(type: 'datetime')]
    protected DateTimeInterface $createdAt;
    
    #[ORM\Column(type: 'datetime')]
    protected DateTimeInterface $updatedAt;
    
    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }
    
    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new DateTime();
    }
}
```

```php
// Étendre les entités générées
class User extends BaseEntity
{
    // Propriétés générées automatiquement...
    
    // Méthodes métier personnalisées
    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }
    
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
```

---

## 🎨 Personnalisation des Templates

### Templates Twig Personnalisés

#### Créer un Template d'Entité Personnalisé

```twig
{# templates/custom_entity.php.twig #}
<?php

declare(strict_types=1);

namespace {{ namespace }};

use Doctrine\ORM\Mapping as ORM;
{% if use_carbon %}
use Carbon\Carbon;
{% endif %}
{% for use_statement in use_statements %}
use {{ use_statement }};
{% endfor %}

/**
 * Entité {{ class_name }} générée automatiquement
 * 
 * @author {{ author|default('ReverseEngineeringBundle') }}
 * @version {{ version|default('1.0') }}
 * @generated {{ "now"|date('Y-m-d H:i:s') }}
 * 
 * Table source: {{ table_name }}
 * {% if table_comment %}
 * Description: {{ table_comment }}
 * {% endif %}
 */
#[ORM\Entity(repositoryClass: {{ repository_class }}::class)]
#[ORM\Table(name: '{{ table_name }}')]
{% if has_lifecycle_callbacks %}
#[ORM\HasLifecycleCallbacks]
{% endif %}
class {{ class_name }} extends {{ base_class|default('BaseEntity') }}
{
{% for column in columns %}
    {% if column.comment %}
    /**
     * {{ column.comment }}
     */
    {% endif %}
    {% for attribute in column.attributes %}
    {{ attribute }}
    {% endfor %}
    private {{ column.php_type }}{{ column.nullable ? '?' : '' }} ${{ column.property_name }}{% if column.default_value %} = {{ column.default_value }}{% endif %};

{% endfor %}
{% for relation in relations %}
    /**
     * {{ relation.comment|default('Relation ' ~ relation.type) }}
     */
    {% for attribute in relation.attributes %}
    {{ attribute }}
    {% endfor %}
    private {{ relation.php_type }} ${{ relation.property_name }};

{% endfor %}
    // Getters et Setters
{% for column in columns %}
    public function get{{ column.property_name|title }}(): {{ column.php_type }}{{ column.nullable ? '?' : '' }}
    {
        return $this->{{ column.property_name }};
    }

    public function set{{ column.property_name|title }}({{ column.php_type }}{{ column.nullable ? '?' : '' }} ${{ column.property_name }}): self
    {
        $this->{{ column.property_name }} = ${{ column.property_name }};
        return $this;
    }

{% endfor %}
{% if has_lifecycle_callbacks %}
    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        // Logique avant insertion
    }
    
    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        // Logique avant mise à jour
    }
{% endif %}
}
```

#### Configuration du Template Personnalisé

```yaml
# config/packages/reverse_engineering.yaml
reverse_engineering:
    templates:
        entity: 'custom_entity.php.twig'
        repository: 'custom_repository.php.twig'
    
    generation:
        template_variables:
            author: 'Mon Équipe'
            version: '2.0'
            base_class: 'App\Entity\BaseEntity'
            use_carbon: true
            has_lifecycle_callbacks: true
```

### Service de Génération Personnalisé

```php
// src/Service/CustomEntityGenerator.php
class CustomEntityGenerator
{
    public function __construct(
        private EntityGenerator $entityGenerator,
        private Environment $twig
    ) {}
    
    public function generateWithBusinessLogic(string $tableName, array $metadata): array
    {
        // Ajouter des variables personnalisées
        $customVariables = [
            'business_rules' => $this->getBusinessRules($tableName),
            'validation_rules' => $this->getValidationRules($metadata),
            'audit_fields' => $this->getAuditFields($metadata)
        ];
        
        // Générer avec template personnalisé
        return $this->entityGenerator->generateEntity(
            $tableName,
            array_merge($metadata, $customVariables),
            [
                'template' => 'business_entity.php.twig',
                'custom_variables' => $customVariables
            ]
        );
    }
    
    private function getBusinessRules(string $tableName): array
    {
        $rules = [
            'users' => ['soft_delete', 'audit_trail', 'email_validation'],
            'orders' => ['status_workflow', 'amount_validation', 'audit_trail'],
            'products' => ['stock_management', 'price_validation']
        ];
        
        return $rules[$tableName] ?? [];
    }
}
```

---

## 🤖 Automatisation et CI/CD

### GitHub Actions Workflow

```yaml
# .github/workflows/reverse-engineering.yml
name: Database Reverse Engineering

on:
  schedule:
    - cron: '0 2 * * 1'  # Tous les lundis à 2h
  workflow_dispatch:
    inputs:
      tables:
        description: 'Tables à traiter (séparées par des virgules)'
        required: false
        default: ''
      force:
        description: 'Forcer l\'écrasement des fichiers'
        type: boolean
        default: false

jobs:
  reverse-engineering:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: testdb
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
        ports:
          - 3306:3306

    steps:
      - name: Checkout code
        uses: actions/checkout@v4
        
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: pdo, mysql, sqlite
          
      - name: Install dependencies
        run: composer install --no-interaction --prefer-dist
        
      - name: Setup test database
        run: |
          mysql -h 127.0.0.1 -u root -proot -e "
            CREATE TABLE users (
              id INT AUTO_INCREMENT PRIMARY KEY,
              email VARCHAR(255) NOT NULL,
              name VARCHAR(100),
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE posts (
              id INT AUTO_INCREMENT PRIMARY KEY,
              title VARCHAR(255) NOT NULL,
              user_id INT,
              FOREIGN KEY (user_id) REFERENCES users(id)
            );
          " testdb
          
      - name: Generate entities
        run: |
          if [ -n "${{ github.event.inputs.tables }}" ]; then
            IFS=',' read -ra TABLES <<< "${{ github.event.inputs.tables }}"
            for table in "${TABLES[@]}"; do
              php bin/console reverse:generate --tables="$table" \
                ${{ github.event.inputs.force == 'true' && '--force' || '' }}
            done
          else
            php bin/console reverse:generate \
              ${{ github.event.inputs.force == 'true' && '--force' || '' }}
          fi
        env:
          DATABASE_URL: mysql://root:root@127.0.0.1:3306/testdb
          
      - name: Validate generated entities
        run: |
          # Vérifier la syntaxe PHP
          find src/Entity -name "*.php" -exec php -l {} \;
          
          # Valider avec Doctrine
          php bin/console doctrine:schema:validate --skip-sync
          
      - name: Create Pull Request
        if: success()
        uses: peter-evans/create-pull-request@v5
        with:
          token: ${{ secrets.GITHUB_TOKEN }}
          commit-message: 'feat: update entities from database schema'
          title: 'Auto-generated entities update'
          body: |
            Mise à jour automatique des entités générées depuis le schéma de base de données.
            
            **Tables traitées :** ${{ github.event.inputs.tables || 'Toutes' }}
            **Force :** ${{ github.event.inputs.force }}
            **Date :** ${{ steps.date.outputs.date }}
            
            Vérifiez les changements avant de merger.
          branch: auto-update-entities
```

### Script de Déploiement Automatisé

```bash
#!/bin/bash
# scripts/deploy-with-entities.sh

set -e

echo "🚀 Déploiement avec mise à jour des entités"

# 1. Sauvegarder les entités actuelles
echo "📦 Sauvegarde des entités existantes..."
if [ -d "src/Entity" ]; then
    cp -r src/Entity src/Entity.backup.$(date +%Y%m%d_%H%M%S)
fi

# 2. Mettre à jour le code
echo "📥 Mise à jour du code..."
git pull origin main
composer install --no-dev --optimize-autoloader

# 3. Régénérer les entités depuis la production
echo "⚙️ Génération des entités depuis la base de production..."
php bin/console reverse:generate \
    --exclude=migrations \
    --exclude=doctrine_migration_versions \
    --force \
    --env=prod

# 4. Valider les entités
echo "✅ Validation des entités générées..."
php bin/console doctrine:schema:validate --env=prod

# 5. Mettre à jour le schéma si nécessaire
echo "🔄 Mise à jour du schéma de base de données..."
php bin/console doctrine:migrations:diff --env=prod
php bin/console doctrine:migrations:migrate --no-interaction --env=prod

# 6. Nettoyer le cache
echo "🧹 Nettoyage du cache..."
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

echo "✅ Déploiement terminé avec succès!"
```

### Monitoring et Alertes

```php
// src/Service/EntityMonitoringService.php
class EntityMonitoringService
{
    public function __construct(
        private ReverseEngineeringService $reverseService,
        private NotificationService $notifier,
        private LoggerInterface $logger
    ) {}
    
    public function checkSchemaChanges(): array
    {
        $changes = [];
        
        try {
            // Analyser les tables actuelles
            $currentTables = $this->reverseService->getAvailableTables();
            
            // Comparer avec la dernière analyse
            $lastAnalysis = $this->getLastAnalysis();
            
            if ($lastAnalysis) {
                $newTables = array_diff($currentTables, $lastAnalysis['tables']);
                $removedTables = array_diff($lastAnalysis['tables'], $currentTables);
                
                if (!empty($newTables)) {
                    $changes['new_tables'] = $newTables;
                    $this->notifier->send(
                        'Nouvelles tables détectées: ' . implode(', ', $newTables)
                    );
                }
                
                if (!empty($removedTables)) {
                    $changes['removed_tables'] = $removedTables;
                    $this->notifier->send(
                        'Tables supprimées: ' . implode(', ', $removedTables)
                    );
                }
            }
            
            // Sauvegarder l'analyse actuelle
            $this->saveAnalysis(['tables' => $currentTables, 'date' => new DateTime()]);
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la vérification du schéma', [
                'exception' => $e->getMessage()
            ]);
        }
        
        return $changes;
    }
}
```

---

## 🚀 Optimisation pour Grandes Bases de Données

### Traitement par Lots

```php
// src/Service/BatchEntityGenerator.php
class BatchEntityGenerator
{
    private const BATCH_SIZE = 10;
    
    public function generateInBatches(array $tables, array $options = []): array
    {
        $results = [];
        $batches = array_chunk($tables, self::BATCH_SIZE);
        
        foreach ($batches as $batchIndex => $batch) {
            $this->logger->info("Traitement du lot {$batchIndex}", [
                'tables' => $batch,
                'batch_size' => count($batch)
            ]);
            
            try {
                $batchResult = $this->reverseService->generateEntities(
                    array_merge($options, ['tables' => $batch])
                );
                
                $results = array_merge_recursive($results, $batchResult);
                
                // Libérer la mémoire entre les lots
                gc_collect_cycles();
                
            } catch (\Exception $e) {
                $this->logger->error("Erreur dans le lot {$batchIndex}", [
                    'tables' => $batch,
                    'error' => $e->getMessage()
                ]);
                
                // Continuer avec le lot suivant
                continue;
            }
        }
        
        return $results;
    }
}
```

### Cache des Métadonnées

```php
// src/Service/CachedMetadataExtractor.php
class CachedMetadataExtractor extends MetadataExtractor
{
    public function __construct(
        private MetadataExtractor $decorated,
        private CacheInterface $cache
    ) {}
    
    public function extractTableMetadata(string $tableName, array $allTables = []): array
    {
        $cacheKey = 'metadata_' . md5($tableName . serialize($allTables));
        
        return $this->cache->get($cacheKey, function () use ($tableName, $allTables) {
            return $this->decorated->extractTableMetadata($tableName, $allTables);
        });
    }
    
    public function invalidateCache(string $tableName = null): void
    {
        if ($tableName) {
            $this->cache->delete('metadata_' . md5($tableName));
        } else {
            $this->cache->clear();
        }
    }
}
```

### Optimisation des Requêtes

```php
// src/Service/OptimizedDatabaseAnalyzer.php
class OptimizedDatabaseAnalyzer extends DatabaseAnalyzer
{
    public function analyzeTables(array $include = [], array $exclude = []): array
    {
        // Utiliser une seule requête pour récupérer toutes les informations
        $sql = "
            SELECT 
                t.table_name,
                t.table_comment,
                COUNT(c.column_name) as column_count,
                COUNT(fk.constraint_name) as fk_count
            FROM information_schema.tables t
            LEFT JOIN information_schema.columns c ON t.table_name = c.table_name
            LEFT JOIN information_schema.key_column_usage fk ON t.table_name = fk.table_name
                AND fk.referenced_table_name IS NOT NULL
            WHERE t.table_schema = DATABASE()
            GROUP BY t.table_name, t.table_comment
        ";
        
        $result = $this->connection->executeQuery($sql);
        $tables = [];
        
        while ($row = $result->fetchAssociative()) {
            $tableName = $row['table_name'];
            
            // Appliquer les filtres
            if (!empty($include) && !in_array($tableName, $include)) {
                continue;
            }
            
            if (in_array($tableName, $exclude)) {
                continue;
            }
            
            $tables[] = $tableName;
        }
        
        return $tables;
    }
}
```

---

## 🔗 Intégration avec d'Autres Outils

### API Platform

```php
// Template pour entités API Platform
// templates/api_entity.php.twig
<?php

namespace {{ namespace }};

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(normalizationContext: ['groups' => ['{{ class_name|lower }}:read']]),
        new Get(normalizationContext: ['groups' => ['{{ class_name|lower }}:read']]),
        new Post(denormalizationContext: ['groups' => ['{{ class_name|lower }}:write']]),
        new Put(denormalizationContext: ['groups' => ['{{ class_name|lower }}:write']]),
        new Delete()
    ]
)]
#[ORM\Entity]
#[ORM\Table(name: '{{ table_name }}')]
class {{ class_name }}
{
{% for column in columns %}
    {% if column.primary %}
    #[Groups(['{{ class_name|lower }}:read'])]
    {% else %}
    #[Groups(['{{ class_name|lower }}:read', '{{ class_name|lower }}:write'])]
    {% if column.required %}
    #[Assert\NotBlank]
    {% endif %}
    {% if column.type == 'email' %}
    #[Assert\Email]
    {% endif %}
    {% endif %}
    {{ column.doctrine_mapping }}
    private {{ column.php_type }} ${{ column.property_name }};

{% endfor %}
}
```

### Symfony Maker Bundle

```php
// src/Command/MakeEntityFromTableCommand.php
class MakeEntityFromTableCommand extends Command
{
    protected static $defaultName = 'make:entity-from-table';
    
    public function __construct(
        private ReverseEngineeringService $reverseService,
        private MakerInterface $entityMaker
    ) {
        parent::__construct();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tableName = $input->getArgument('table');
        
        // Générer l'entité avec reverse engineering
        $result = $this->reverseService->generateEntities([
            'tables' => [$tableName],
            'dry_run' => true
        ]);
        
        // Utiliser Maker pour créer l'entité interactive
        $entity = $result['entities'][0];
        $this->entityMaker->createFromMetadata($entity);
        
        return Command::SUCCESS;
    }
}
```

### Doctrine Migrations

```php
// src/Service/MigrationGenerator.php
class MigrationGenerator
{
    public function generateMigrationFromChanges(array $changes): string
    {
        $migration = "<?php\n\n";
        $migration .= "declare(strict_types=1);\n\n";
        $migration .= "namespace DoctrineMigrations;\n\n";
        $migration .= "use Doctrine\DBAL\Schema\Schema;\n";
        $migration .= "use Doctrine\Migrations\AbstractMigration;\n\n";
        $migration .= "final class Version" . date('YmdHis') . " extends AbstractMigration\n";
        $migration .= "{\n";
        $migration .= "    public function up(Schema \$schema): void\n";
        $migration .= "    {\n";
        
        foreach ($changes['new_tables'] ?? [] as $table) {
            $migration .= "        // Table ajoutée: {$table}\n";
        }
        
        foreach ($changes['modified_tables'] ?? [] as $table) {
            $migration .= "        // Table modifiée: {$table}\n";
        }
        
        $migration .= "    }\n\n";
        $migration .= "    public function down(Schema \$schema): void\n";
        $migration .= "    {\n";
        $migration .= "        // Rollback logic\n";
        $migration .= "    }\n";
        $migration .= "}\n";
        
        return $migration;
    }
}
```

---

## 💼 Cas d'Usage Métier Spécifiques

### E-commerce Multi-tenant

```php
// Configuration pour architecture multi-tenant
class MultiTenantEntityGenerator
{
    public function generateTenantEntities(string $tenantId): array
    {
        $options = [
            'namespace' => "App\\Entity\\Tenant\\{$tenantId}",
            'output_dir' => "src/Entity/Tenant/{$tenantId}",
            'template_variables' => [
                'tenant_id' => $tenantId,
                'add_tenant_filter' => true
            ]
        ];
        
        return $this->reverseService->generateEntities($options);
    }
}
```

### Audit et Historique

```twig
{# templates/auditable_entity.php.twig #}
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Gedmo\Blameable\Traits\BlameableEntity;

#[ORM\Entity]
#[Gedmo\Loggable]
class {{ class_name }}
{
    use TimestampableEntity;
    use BlameableEntity;
    
{% for column in columns %}
    {% if column.auditable %}
    #[Gedmo\Versioned]
    {% endif %}
    {{ column.doctrine_mapping }}
    private {{ column.php_type }} ${{ column.property_name }};
{% endfor %}
}
```

### Microservices

```php
// Génération d'entités pour microservices
class MicroserviceEntityGenerator
{
    public function generateForService(string $serviceName, array $tables): array
    {
        $options = [
            'namespace' => "App\\{$serviceName}\\Entity",
            'output_dir' => "src/{$serviceName}/Entity",
            'tables' => $tables,
            'template_variables' => [
                'service_name' => $serviceName,
                'add_service_prefix' => true,
                'generate_dto' => true
            ]
        ];
        
        return $this->reverseService->generateEntities($options);
    }
}
```

### Intégration avec Event Sourcing

```php
// Template pour entités avec Event Sourcing
class EventSourcedEntityGenerator
{
    public function generateEventSourcedEntity(string $tableName, array $metadata): array
    {
        $customMetadata = array_merge($metadata, [
            'events' => $this->generateDomainEvents($metadata),
            'aggregateRoot' => true,
            'eventStore' => true
        ]);
        
        return $this->entityGenerator->generateEntity(
            $tableName,
            $customMetadata,
            ['template' => 'event_sourced_entity.php.twig']
        );
    }
}
```

---

## 🔧 Outils de Développement Avancés

### Plugin PHPStorm (Concept)

```xml
<!-- plugin.xml pour PHPStorm -->
<idea-plugin>
    <id>com.eprofos.reverse-engineering</id>
    <name>ReverseEngineering Bundle Helper</name>
    
    <actions>
        <action id="GenerateEntityFromTable" 
                class="com.eprofos.actions.GenerateEntityAction"
                text="Generate Entity from Table">
            <add-to-group group-id="DatabaseViewPopupMenu" anchor="first"/>
        </action>
    </actions>
</idea-plugin>
```

### Extension VSCode (Concept)

```json
{
    "name": "reverse-engineering-bundle",
    "displayName": "ReverseEngineering Bundle",
    "description": "Helper for Symfony ReverseEngineering Bundle",
    "version": "0.1.0",
    "engines": {
        "vscode": "^1.60.0"
    },
    "categories": ["Other"],
    "contributes": {
        "commands": [
            {
                "command": "reverseEngineering.generateEntity",
                "title": "Generate Entity from Database"
            }
        ]
    }
}
```

---

## 📊 Métriques et Monitoring Avancés

### Dashboard de Monitoring

```php
// src/Controller/ReverseEngineeringDashboardController.php
class ReverseEngineeringDashboardController extends AbstractController
{
    #[Route('/admin/reverse-engineering', name: 'reverse_engineering_dashboard')]
    public function dashboard(
        ReverseEngineeringService $service,
        EntityMonitoringService $monitoring
    ): Response {
        $stats = [
            'total_tables' => count($service->getAvailableTables()),
            'generated_entities' => $this->countGeneratedEntities(),
            'last_generation' => $this->getLastGenerationDate(),
            'schema_changes' => $monitoring->checkSchemaChanges()
        ];
        
        return $this->render('admin/reverse_engineering_dashboard.html.twig', [
            'stats' => $stats
        ]);
    }
}
```

---

**Ces cas d'usage avancés montrent la flexibilité et la puissance du ReverseEngineeringBundle pour des scénarios complexes et des architectures modernes.**