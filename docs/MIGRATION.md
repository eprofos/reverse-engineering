# Migration Guide - ReverseEngineeringBundle

This comprehensive guide helps you migrate legacy applications to modern Symfony architecture using the ReverseEngineeringBundle. Whether you're modernizing a monolithic application or transitioning from another framework, this guide provides step-by-step instructions and best practices.

## 📋 Table of Contents

- [Migration Strategies](#migration-strategies)
- [Pre-Migration Assessment](#pre-migration-assessment)
- [Step-by-Step Migration Process](#step-by-step-migration-process)
- [Framework-Specific Migrations](#framework-specific-migrations)
- [Database Migration Scenarios](#database-migration-scenarios)
- [Post-Migration Optimization](#post-migration-optimization)
- [Troubleshooting Common Issues](#troubleshooting-common-issues)

## 🎯 Migration Strategies

### 1. Big Bang Migration

**Best for**: Small to medium applications (< 50 tables)

**Approach**: Complete migration in a single phase

**Timeline**: 1-4 weeks

**Steps**:
1. Analyze entire database schema
2. Generate all entities at once
3. Migrate all business logic
4. Switch to new system

**Pros**:
- ✅ Clean break from legacy system
- ✅ No dual maintenance
- ✅ Immediate benefits of new architecture

**Cons**:
- ❌ Higher risk
- ❌ Longer downtime
- ❌ All-or-nothing approach

### 2. Strangler Fig Migration

**Best for**: Large applications (50+ tables)

**Approach**: Gradual replacement by module

**Timeline**: 3-12 months

**Steps**:
1. Identify business modules
2. Migrate one module at a time
3. Maintain both systems during transition
4. Gradually redirect traffic

**Pros**:
- ✅ Lower risk
- ✅ Continuous delivery
- ✅ Easier rollback

**Cons**:
- ❌ Dual maintenance overhead
- ❌ Data synchronization complexity
- ❌ Longer overall timeline

### 3. Database-First Migration

**Best for**: Applications with complex database schemas

**Approach**: Migrate database structure first, then application logic

**Timeline**: 2-8 weeks

**Steps**:
1. Analyze and optimize database schema
2. Generate entities with relationships
3. Create data access layer
4. Migrate business logic incrementally

## 🔍 Pre-Migration Assessment

### Database Analysis

#### Step 1: Schema Complexity Assessment

```bash
# Analyze database structure
php bin/console reverse:generate --dry-run --verbose > schema_analysis.txt

# Count tables and relationships
mysql -u username -p database_name -e "
SELECT 
    COUNT(*) as table_count,
    (SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE 
     WHERE REFERENCED_TABLE_NAME IS NOT NULL 
     AND TABLE_SCHEMA = DATABASE()) as foreign_key_count
FROM information_schema.tables 
WHERE table_schema = DATABASE();"
```

#### Step 2: Data Volume Assessment

```sql
-- Analyze table sizes
SELECT 
    table_name,
    table_rows,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.tables 
WHERE table_schema = DATABASE()
ORDER BY (data_length + index_length) DESC;
```

#### Step 3: Relationship Complexity

```sql
-- Analyze foreign key relationships
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME,
    DELETE_RULE,
    UPDATE_RULE
FROM information_schema.KEY_COLUMN_USAGE 
WHERE REFERENCED_TABLE_NAME IS NOT NULL 
AND TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME;
```

### Application Analysis

#### Legacy Code Assessment

```bash
# Create assessment script
cat > scripts/assess_legacy.sh << 'EOF'
#!/bin/bash

echo "=== Legacy Application Assessment ==="

# Count PHP files
echo "PHP Files: $(find . -name "*.php" | wc -l)"

# Count database queries
echo "SQL Queries: $(grep -r "SELECT\|INSERT\|UPDATE\|DELETE" --include="*.php" . | wc -l)"

# Find direct database access
echo "Direct DB Access: $(grep -r "mysql_\|mysqli_\|PDO" --include="*.php" . | wc -l)"

# Find framework usage
echo "Framework Detection:"
grep -r "CodeIgniter\|Laravel\|CakePHP\|Zend" --include="*.php" . | head -5

# Identify business logic patterns
echo "Business Logic Files:"
find . -name "*Model*.php" -o -name "*Service*.php" -o -name "*Manager*.php" | head -10
EOF

chmod +x scripts/assess_legacy.sh
./scripts/assess_legacy.sh
```

## 🚀 Step-by-Step Migration Process

### Phase 1: Environment Setup (1-2 days)

#### Step 1: Create New Symfony Project

```bash
# Create new Symfony project
composer create-project symfony/skeleton my-migrated-app
cd my-migrated-app

# Install required packages
composer require doctrine/orm doctrine/doctrine-bundle
composer require eprofos/reverse-engineering-bundle

# Install development tools
composer require --dev symfony/maker-bundle phpunit/phpunit
```

#### Step 2: Configure Database Connection

```yaml
# config/packages/doctrine.yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'
        driver: 'pdo_mysql'
        server_version: '8.0'
        charset: utf8mb4
        default_table_options:
            charset: utf8mb4
            collate: utf8mb4_unicode_ci
    orm:
        auto_generate_proxy_classes: true
        naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware
        auto_mapping: true
        mappings:
            App:
                is_bundle: false
                dir: '%kernel.project_dir%/src/Entity'
                prefix: 'App\Entity'
                alias: App
```

#### Step 3: Configure ReverseEngineeringBundle

```yaml
# config/packages/reverse_engineering.yaml
reverse_engineering:
    database:
        driver: '%env(DB_DRIVER)%'
        host: '%env(DB_HOST)%'
        port: '%env(int:DB_PORT)%'
        dbname: '%env(DB_NAME)%'
        user: '%env(DB_USER)%'
        password: '%env(DB_PASSWORD)%'
        charset: utf8mb4
    
    generation:
        namespace: App\Entity
        output_dir: src/Entity
        generate_repository: true
        use_annotations: false
        exclude_tables:
            - doctrine_migration_versions
            - messenger_messages
            - cache_items
            - sessions
```

### Phase 2: Entity Generation (2-5 days)

#### Step 1: Analyze and Plan Entity Structure

```bash
# Preview entity generation
php bin/console reverse:generate --dry-run --verbose

# Create entity organization plan
cat > docs/entity_plan.md << 'EOF'
# Entity Organization Plan

## Core Entities
- User, UserProfile, UserRole
- Product, Category, Brand

## Business Logic Entities  
- Order, OrderItem, Payment
- Invoice, InvoiceItem

## Reference Data
- Country, State, City
- Currency, Language

## System Entities
- AuditLog, Configuration
- Notification, EmailTemplate
EOF
```

#### Step 2: Generate Entities by Module

```bash
# User Management Module
php bin/console reverse:generate \
    --tables=users --tables=user_profiles --tables=user_roles \
    --namespace="App\Entity\User" \
    --output-dir="src/Entity/User" \
    --force

# Product Catalog Module
php bin/console reverse:generate \
    --tables=products --tables=categories --tables=brands \
    --namespace="App\Entity\Product" \
    --output-dir="src/Entity/Product" \
    --force

# Order Management Module
php bin/console reverse:generate \
    --tables=orders --tables=order_items --tables=payments \
    --namespace="App\Entity\Order" \
    --output-dir="src/Entity/Order" \
    --force

# Reference Data Module
php bin/console reverse:generate \
    --tables=countries --tables=states --tables=cities \
    --namespace="App\Entity\Reference" \
    --output-dir="src/Entity/Reference" \
    --force
```

#### Step 3: Validate Generated Entities

```bash
# Validate entity mapping
php bin/console doctrine:mapping:info

# Validate schema
php bin/console doctrine:schema:validate

# Check for syntax errors
find src/Entity -name "*.php" -exec php -l {} \;

# Generate database schema (for comparison)
php bin/console doctrine:schema:create --dump-sql > schema_generated.sql
```

### Phase 3: Business Logic Migration (1-4 weeks)

#### Step 1: Create Service Layer

```php
// src/Service/User/UserService.php
namespace App\Service\User;

use App\Entity\User\User;
use App\Repository\User\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository
    ) {}
    
    public function createUser(array $userData): User
    {
        $user = new User();
        $user->setEmail($userData['email']);
        $user->setFirstName($userData['first_name']);
        $user->setLastName($userData['last_name']);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $user;
    }
    
    public function findActiveUsers(): array
    {
        return $this->userRepository->findBy(['isActive' => true]);
    }
    
    // Migrate legacy business logic here
    public function calculateUserScore(User $user): float
    {
        // Migrated from legacy calculateScore() function
        $baseScore = 100;
        $activityBonus = count($user->getOrders()) * 5;
        $loyaltyBonus = $user->getCreatedAt() < new \DateTime('-1 year') ? 20 : 0;
        
        return $baseScore + $activityBonus + $loyaltyBonus;
    }
}
```

#### Step 2: Create Data Transfer Objects (DTOs)

```php
// src/DTO/User/CreateUserRequest.php
namespace App\DTO\User;

use Symfony\Component\Validator\Constraints as Assert;

class CreateUserRequest
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;
    
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    public string $firstName;
    
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    public string $lastName;
    
    public ?string $phone = null;
    
    public ?\DateTimeInterface $birthDate = null;
}
```

#### Step 3: Create Controllers

```php
// src/Controller/User/UserController.php
namespace App\Controller\User;

use App\DTO\User\CreateUserRequest;
use App\Service\User\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/users')]
class UserController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {}
    
    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $createRequest = $this->serializer->deserialize(
            $request->getContent(),
            CreateUserRequest::class,
            'json'
        );
        
        $errors = $this->validator->validate($createRequest);
        if (count($errors) > 0) {
            return new JsonResponse(['errors' => (string) $errors], 400);
        }
        
        $user = $this->userService->createUser([
            'email' => $createRequest->email,
            'first_name' => $createRequest->firstName,
            'last_name' => $createRequest->lastName,
            'phone' => $createRequest->phone,
            'birth_date' => $createRequest->birthDate
        ]);
        
        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s')
        ], 201);
    }
    
    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $users = $this->userService->findActiveUsers();
        
        return new JsonResponse(
            array_map(fn($user) => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'full_name' => $user->getFirstName() . ' ' . $user->getLastName(),
                'score' => $this->userService->calculateUserScore($user)
            ], $users)
        );
    }
}
```

### Phase 4: Data Migration (1-2 weeks)

#### Step 1: Create Migration Scripts

```php
// src/Command/MigrateDataCommand.php
namespace App\Command;

use App\Service\Migration\DataMigrationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-data',
    description: 'Migrate data from legacy system'
)]
class MigrateDataCommand extends Command
{
    public function __construct(
        private DataMigrationService $migrationService
    ) {
        parent::__construct();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Data Migration Process');
        
        try {
            // Migrate reference data first
            $io->section('Migrating Reference Data');
            $this->migrationService->migrateReferenceData();
            $io->success('Reference data migrated successfully');
            
            // Migrate users
            $io->section('Migrating Users');
            $userCount = $this->migrationService->migrateUsers();
            $io->success("Migrated {$userCount} users");
            
            // Migrate products
            $io->section('Migrating Products');
            $productCount = $this->migrationService->migrateProducts();
            $io->success("Migrated {$productCount} products");
            
            // Migrate orders
            $io->section('Migrating Orders');
            $orderCount = $this->migrationService->migrateOrders();
            $io->success("Migrated {$orderCount} orders");
            
            $io->success('Data migration completed successfully!');
            
        } catch (\Exception $e) {
            $io->error('Migration failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
}
```

#### Step 2: Implement Migration Service

```php
// src/Service/Migration/DataMigrationService.php
namespace App\Service\Migration;

use App\Entity\User\User;
use App\Entity\Product\Product;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class DataMigrationService
{
    public function __construct(
        private Connection $legacyConnection,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}
    
    public function migrateUsers(): int
    {
        $stmt = $this->legacyConnection->executeQuery('SELECT * FROM legacy_users');
        $count = 0;
        
        while ($row = $stmt->fetchAssociative()) {
            try {
                $user = new User();
                $user->setEmail($row['email']);
                $user->setFirstName($row['first_name']);
                $user->setLastName($row['last_name']);
                $user->setCreatedAt(new \DateTime($row['created_at']));
                
                // Handle legacy-specific fields
                if (isset($row['legacy_id'])) {
                    $user->setLegacyId($row['legacy_id']);
                }
                
                $this->entityManager->persist($user);
                $count++;
                
                // Batch processing
                if ($count % 100 === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                    $this->logger->info("Migrated {$count} users");
                }
                
            } catch (\Exception $e) {
                $this->logger->error("Failed to migrate user {$row['id']}: " . $e->getMessage());
            }
        }
        
        $this->entityManager->flush();
        $this->entityManager->clear();
        
        return $count;
    }
    
    public function migrateProducts(): int
    {
        // Similar implementation for products
        // ...
    }
    
    public function migrateOrders(): int
    {
        // Similar implementation for orders
        // ...
    }
}
```

### Phase 5: Testing and Validation (1-2 weeks)

#### Step 1: Create Integration Tests

```php
// tests/Integration/MigrationTest.php
namespace App\Tests\Integration;

use App\Service\Migration\DataMigrationService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MigrationTest extends KernelTestCase
{
    private DataMigrationService $migrationService;
    
    protected function setUp(): void
    {
        self::bootKernel();
        $this->migrationService = static::getContainer()->get(DataMigrationService::class);
    }
    
    public function testUserMigration(): void
    {
        $userCount = $this->migrationService->migrateUsers();
        
        $this->assertGreaterThan(0, $userCount);
        
        // Verify data integrity
        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $users = $entityManager->getRepository(User::class)->findAll();
        
        $this->assertCount($userCount, $users);
        
        foreach ($users as $user) {
            $this->assertNotEmpty($user->getEmail());
            $this->assertInstanceOf(\DateTimeInterface::class, $user->getCreatedAt());
        }
    }
}
```

#### Step 2: Data Validation Scripts

```bash
# Create validation script
cat > scripts/validate_migration.sh << 'EOF'
#!/bin/bash

echo "=== Migration Validation ==="

# Count records in legacy vs new system
echo "Legacy Users: $(mysql -u user -p legacy_db -e "SELECT COUNT(*) FROM legacy_users" -s)"
echo "New Users: $(php bin/console doctrine:query:sql "SELECT COUNT(*) FROM users" | tail -1)"

echo "Legacy Products: $(mysql -u user -p legacy_db -e "SELECT COUNT(*) FROM legacy_products" -s)"
echo "New Products: $(php bin/console doctrine:query:sql "SELECT COUNT(*) FROM products" | tail -1)"

# Validate data integrity
php bin/console app:validate-migration-integrity
EOF

chmod +x scripts/validate_migration.sh
```

## 🔄 Framework-Specific Migrations

### From CodeIgniter

#### Legacy Code Pattern
```php
// Legacy CodeIgniter Model
class User_model extends CI_Model {
    public function get_user($id) {
        $query = $this->db->get_where('users', array('id' => $id));
        return $query->row();
    }
    
    public function create_user($data) {
        $this->db->insert('users', $data);
        return $this->db->insert_id();
    }
}
```

#### Migrated Symfony Service
```php
// Migrated Symfony Service
namespace App\Service\User;

use App\Entity\User\User;
use App\Repository\User\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository
    ) {}
    
    public function getUser(int $id): ?User
    {
        return $this->userRepository->find($id);
    }
    
    public function createUser(array $data): User
    {
        $user = new User();
        $user->setEmail($data['email']);
        $user->setFirstName($data['first_name']);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $user;
    }
}
```

### From Laravel

#### Legacy Eloquent Model
```php
// Legacy Laravel Model
class User extends Model {
    protected $fillable = ['email', 'first_name', 'last_name'];
    
    public function orders() {
        return $this->hasMany(Order::class);
    }
    
    public function getFullNameAttribute() {
        return $this->first_name . ' ' . $this->last_name;
    }
}
```

#### Migrated Doctrine Entity
```php
// Migrated Doctrine Entity
namespace App\Entity\User;

use App\Entity\Order\Order;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;
    
    #[ORM\Column(length: 255)]
    private string $email;
    
    #[ORM\Column(length: 100)]
    private string $firstName;
    
    #[ORM\Column(length: 100)]
    private string $lastName;
    
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Order::class)]
    private Collection $orders;
    
    public function __construct()
    {
        $this->orders = new ArrayCollection();
    }
    
    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }
    
    // ... getters and setters
}
```

### From CakePHP

#### Legacy CakePHP Controller
```php
// Legacy CakePHP Controller
class UsersController extends AppController {
    public function index() {
        $users = $this->User->find('all', array(
            'conditions' => array('User.active' => 1),
            'order' => 'User.created DESC'
        ));
        $this->set('users', $users);
    }
}
```

#### Migrated Symfony Controller
```php
// Migrated Symfony Controller
namespace App\Controller\User;

use App\Repository\User\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route('/users', methods: ['GET'])]
    public function index(UserRepository $userRepository): JsonResponse
    {
        $users = $userRepository->findBy(
            ['isActive' => true],
            ['createdAt' => 'DESC']
        );
        
        return $this->json(array_map(fn($user) => [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'full_name' => $user->getFullName(),
            'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s')
        ], $users));
    }
}
```

## 🗄️ Database Migration Scenarios

### Scenario 1: Schema Modernization

#### Before: Legacy Schema
```sql
-- Legacy table with poor design
CREATE TABLE user_data (
    user_id INT PRIMARY KEY,
    user_info TEXT,  -- JSON stored as text
    settings TEXT,   -- Serialized PHP data
    created DATETIME,
    modified DATETIME
);
```

#### After: Normalized Schema
```sql
-- Modernized schema
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE user_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value JSON,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_user_setting (user_id, setting_key)
);
```

#### Migration Script
```php
// src/Command/ModernizeSchemaCommand.php
public function migrateUserData(): void
{
    $stmt = $this->legacyConnection->executeQuery('SELECT * FROM user_data');
    
    while ($row = $stmt->fetchAssociative()) {
        // Parse legacy JSON data
        $userInfo = json_decode($row['user_info'], true);
        $settings = unserialize($row['settings']);
        
        // Create modern user entity
        $user = new User();
        $user->setEmail($userInfo['email']);
        $user->setFirstName($userInfo['first_name']);
        $user->setLastName($userInfo['last_name']);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        // Migrate settings
        foreach ($settings as $key => $value) {
            $setting = new UserSetting();
            $setting->setUser($user);
            $setting->setSettingKey($key);
            $setting->setSettingValue($value);
            
            $this->entityManager->persist($setting);
        }
        
        $this->entityManager->flush();
    }
}
```

### Scenario 2: Relationship Normalization

#### Before: Denormalized Data
```sql
CREATE TABLE orders (
    id INT PRIMARY KEY,
    customer_name VARCHAR(255),
    customer_email VARCHAR(255),
    customer_address TEXT,
    product_names TEXT,  -- Comma-separated
    product_prices TEXT, -- Comma-separated
    total_amount DECIMAL(10,2)
);
```

#### After: Normalized Relationships
```sql
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    address TEXT
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);
```

## 📈 Post-Migration Optimization

### Performance Optimization

#### Database Indexing
```sql
-- Add performance indexes
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_created_at ON users(created_at);
CREATE INDEX idx_orders_customer_id ON orders(customer_id);
CREATE INDEX idx_orders_created_at ON orders(created_at);
CREATE INDEX idx_order_items_order_id ON order_items(order_id);
CREATE INDEX idx_order_items_product_id ON order_items(product_id);
```

#### Query Optimization
```php
// Optimize N+1 queries with eager loading
public function findUsersWithOrders(): array
{
    return $this->createQueryBuilder('u')
        ->leftJoin('u.orders', 'o')
        ->addSelect('o')
        ->getQuery()
        ->getResult();
}

// Use pagination for large datasets
public function findUsersPaginated(int $page, int $limit): array
{
    return $this->createQueryBuilder('u')
        ->setFirstResult(($page - 1) * $limit)
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
}
```

### Code Quality Improvements

#### Add Validation
```php
// src/Entity/User/User.php
use Symfony\Component\Validator\Constraints as Assert;

class User
{
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email;
    
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    private string $firstName;
    
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    private string $lastName;
}
```

#### Add Events and Listeners
```php
// src/EventListener/UserEventListener.php
namespace App\EventListener;

use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

#[AsEntityListener(event: Events::prePersist, entity: User::class)]
#[AsEntityListener(event: Events::preUpdate, entity: User::class)]
class UserEventListener
{
    public function prePersist(User $user, LifecycleEventArgs $event): void
    {
        $user->setCreatedAt(new \DateTime());
        $user->setUpdatedAt(new \DateTime());
    }
    
    public function preUpdate(User $user, LifecycleEventArgs $event): void
    {
        $user->setUpdatedAt(new \DateTime());
    }
}
```

## 🚨 Troubleshooting Common Issues

### Issue 1: Character Encoding Problems

**Problem**: Special characters not displaying correctly

**Solution**:
```yaml
# config/packages/doctrine.yaml
doctrine:
    dbal:
        charset: utf8mb4
        default_table_options:
            charset: utf8mb4
            collate: utf8mb4_unicode_ci
```

```sql
-- Convert existing tables
ALTER TABLE users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Issue 2: Large Dataset Migration Timeouts

**Problem**: Migration scripts timing out on large datasets

**Solution**:
```php
// Implement batch processing
public function migrateLargeDataset(): void
{
    $batchSize = 1000;
    $offset = 0;
    
    do {
        $stmt = $this->legacyConnection->executeQuery(
            'SELECT * FROM large_table LIMIT ? OFFSET ?',
            [$batchSize, $offset]
        );
        
        $count = 0;
        while ($