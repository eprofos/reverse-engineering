# MySQL ENUM to PHP 8.1 Backed Enum Support

## Overview

The ReverseEngineeringBundle now includes comprehensive support for MySQL ENUM columns, automatically generating PHP 8.1 backed enum classes instead of treating ENUM columns as simple strings. This feature provides type safety, better IDE support, and improved code maintainability when working with enumerated values.

## Table of Contents

- [How It Works](#how-it-works)
- [Benefits](#benefits)
- [Before vs After Comparison](#before-vs-after-comparison)
- [Configuration Options](#configuration-options)
- [Usage Examples](#usage-examples)
- [Generated Code Structure](#generated-code-structure)
- [Best Practices](#best-practices)
- [Troubleshooting](#troubleshooting)
- [Migration Guide](#migration-guide)

## How It Works

When the bundle encounters a MySQL ENUM column during reverse engineering, it:

1. **Detects ENUM columns** during database schema analysis
2. **Extracts ENUM values** from the column definition
3. **Generates a PHP 8.1 backed enum class** with appropriate case names
4. **Updates the entity property** to use the enum type instead of string
5. **Adds proper Doctrine mapping** with `enumType` attribute
6. **Includes necessary imports** in the generated entity

### Technical Implementation

The ENUM support is implemented through several key components:

- **[`EnumClassGenerator`](../src/Service/EnumClassGenerator.php)**: Generates PHP 8.1 backed enum classes
- **Enhanced [`EntityGenerator`](../src/Service/EntityGenerator.php)**: Detects ENUM columns and integrates enum classes
- **Updated [entity template](../src/Resources/templates/entity.php.twig)**: Supports `enumType` attribute rendering

## Benefits

### Type Safety
- **Compile-time validation**: PHP will catch invalid enum values at runtime
- **IDE autocompletion**: Full IntelliSense support for enum values
- **Refactoring safety**: Renaming enum cases updates all references

### Code Quality
- **Self-documenting code**: Enum cases clearly show available values
- **Reduced magic strings**: No more hardcoded string values scattered throughout code
- **Better maintainability**: Centralized enum definitions

### Doctrine Integration
- **Native enum support**: Leverages Doctrine's built-in enum type handling
- **Automatic validation**: Doctrine validates enum values on persist
- **Database consistency**: Ensures only valid enum values are stored

## Before vs After Comparison

### Before: String-based Approach

**Database Schema:**
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive', 'pending') DEFAULT 'pending'
);
```

**Generated Entity (Old):**
```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User
{
    #[ORM\Column(type: 'string', length: 255)]
    private string $status = 'pending';

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status; // No validation!
        return $this;
    }
}
```

**Usage Issues:**
```php
// Prone to typos and runtime errors
$user->setStatus('activ'); // Typo - no compile-time error
$user->setStatus('ACTIVE'); // Wrong case - no validation
$user->setStatus('deleted'); // Invalid value - no immediate feedback
```

### After: PHP 8.1 Backed Enum Approach

**Generated Enum Class:**
```php
<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Enum for users.status values
 * Generated automatically by ReverseEngineeringBundle
 */
enum UserStatusEnum: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
}
```

**Generated Entity (New):**
```php
<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Enum\UserStatusEnum;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User
{
    #[ORM\Column(type: 'string', enumType: UserStatusEnum::class)]
    private UserStatusEnum $status = UserStatusEnum::PENDING;

    public function getStatus(): UserStatusEnum
    {
        return $this->status;
    }

    public function setStatus(UserStatusEnum $status): static
    {
        $this->status = $status; // Type-safe!
        return $this;
    }
}
```

**Improved Usage:**
```php
// Type-safe with IDE autocompletion
$user->setStatus(UserStatusEnum::ACTIVE);

// Compile-time error prevention
$user->setStatus('activ'); // PHP Fatal Error: Cannot assign string to UserStatusEnum

// Easy value checking
if ($user->getStatus() === UserStatusEnum::ACTIVE) {
    // Handle active user
}

// Enum methods available
$allStatuses = UserStatusEnum::cases(); // Get all possible values
$statusValue = UserStatusEnum::ACTIVE->value; // Get string value: 'active'
```

## Configuration Options

### Enum Namespace Configuration

Configure the namespace for generated enum classes in your `config/packages/reverse_engineering.yaml`:

```yaml
reverse_engineering:
    generation:
        # Namespace for generated entities
        namespace: App\Entity
        
        # Namespace for generated enum classes (optional)
        enum_namespace: App\Enum  # Default: App\Enum
        
        # Output directory for enum classes (optional)
        enum_output_dir: src/Enum  # Default: src/Enum
```

### Advanced Configuration

```yaml
reverse_engineering:
    generation:
        # Custom enum namespace per module
        enum_namespace: MyApp\Domain\Enum
        
        # Custom enum output directory
        enum_output_dir: src/Domain/Enum
        
        # Force overwrite existing enum files
        force_enum_overwrite: true  # Default: false
```

### Environment-Specific Configuration

```yaml
# config/packages/dev/reverse_engineering.yaml
reverse_engineering:
    generation:
        enum_namespace: App\Dev\Enum
        enum_output_dir: src/Dev/Enum

# config/packages/prod/reverse_engineering.yaml
reverse_engineering:
    generation:
        enum_namespace: App\Enum
        enum_output_dir: src/Enum
```

## Usage Examples

### Basic ENUM Column

**Database:**
```sql
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft'
);
```

**Generated Files:**

`src/Enum/ProductStatusEnum.php`:
```php
<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Enum for products.status values
 * Generated automatically by ReverseEngineeringBundle
 */
enum ProductStatusEnum: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';
}
```

`src/Entity/Product.php`:
```php
<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Enum\ProductStatusEnum;

#[ORM\Entity]
#[ORM\Table(name: 'products')]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', enumType: ProductStatusEnum::class)]
    private ProductStatusEnum $status = ProductStatusEnum::DRAFT;

    // Getters and setters...
}
```

### Complex ENUM Values

**Database:**
```sql
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    status ENUM('pending-payment', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending-payment',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal'
);
```

**Generated Enums:**

`src/Enum/OrderStatusEnum.php`:
```php
<?php

declare(strict_types=1);

namespace App\Enum;

enum OrderStatusEnum: string
{
    case PENDING_PAYMENT = 'pending-payment';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
}
```

`src/Enum/OrderPriorityEnum.php`:
```php
<?php

declare(strict_types=1);

namespace App\Enum;

enum OrderPriorityEnum: string
{
    case LOW = 'low';
    case NORMAL = 'normal';
    case HIGH = 'high';
    case URGENT = 'urgent';
}
```

### Nullable ENUM Columns

**Database:**
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    subscription_type ENUM('free', 'premium', 'enterprise') NULL
);
```

**Generated Entity:**
```php
#[ORM\Column(type: 'string', nullable: true, enumType: UserSubscriptionTypeEnum::class)]
private ?UserSubscriptionTypeEnum $subscriptionType = null;

public function getSubscriptionType(): ?UserSubscriptionTypeEnum
{
    return $this->subscriptionType;
}

public function setSubscriptionType(?UserSubscriptionTypeEnum $subscriptionType): static
{
    $this->subscriptionType = $subscriptionType;
    return $this;
}
```

## Generated Code Structure

### Enum Class Structure

Generated enum classes follow a consistent structure:

```php
<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Enum for {table_name}.{column_name} values
 * Generated automatically by ReverseEngineeringBundle
 */
enum {TableName}{ColumnName}Enum: string
{
    case {CASE_NAME} = '{database_value}';
    // ... more cases
}
```

### Case Name Generation Rules

The bundle automatically converts database enum values to valid PHP enum case names:

| Database Value | Generated Case Name | Notes |
|----------------|-------------------|-------|
| `active` | `ACTIVE` | Simple uppercase conversion |
| `pending-approval` | `PENDING_APPROVAL` | Hyphens become underscores |
| `in_progress` | `IN_PROGRESS` | Underscores preserved |
| `2fa-enabled` | `_2FA_ENABLED` | Numbers prefixed with underscore |
| `special@value` | `SPECIAL_VALUE` | Special characters become underscores |
| `` (empty) | `EMPTY_VALUE` | Empty values get fallback name |

### Entity Integration

Generated entities include:

1. **Proper imports** for enum classes
2. **Type-hinted properties** using enum types
3. **Doctrine mapping** with `enumType` attribute
4. **Type-safe getters/setters** with enum parameters and return types
5. **Default values** using enum cases instead of strings

## Best Practices

### 1. Enum Naming Conventions

**Recommended naming patterns:**
```php
// Good: Descriptive and consistent
enum UserStatusEnum: string { ... }
enum OrderStatusEnum: string { ... }
enum ProductCategoryEnum: string { ... }

// Avoid: Generic or unclear names
enum StatusEnum: string { ... }  // Too generic
enum UserEnum: string { ... }    // Unclear purpose
```

### 2. Working with Enums in Services

```php
class UserService
{
    public function activateUser(User $user): void
    {
        $user->setStatus(UserStatusEnum::ACTIVE);
        // Doctrine will automatically validate the enum value
    }

    public function getActiveUsers(): array
    {
        return $this->userRepository->findBy([
            'status' => UserStatusEnum::ACTIVE
        ]);
    }

    public function getUsersByStatus(UserStatusEnum $status): array
    {
        return $this->userRepository->findBy(['status' => $status]);
    }
}
```

### 3. Form Integration

```php
use Symfony\Component\Form\Extension\Core\Type\EnumType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('status', EnumType::class, [
                'class' => UserStatusEnum::class,
                'choice_label' => fn(UserStatusEnum $status) => ucfirst($status->value),
            ]);
    }
}
```

### 4. API Serialization

```php
use Symfony\Component\Serializer\Annotation\Groups;

class User
{
    #[Groups(['user:read', 'user:write'])]
    #[ORM\Column(type: 'string', enumType: UserStatusEnum::class)]
    private UserStatusEnum $status;

    // The serializer will automatically convert enum to/from string values
}
```

### 5. Database Queries

```php
// Using QueryBuilder
$qb = $this->createQueryBuilder('u')
    ->where('u.status = :status')
    ->setParameter('status', UserStatusEnum::ACTIVE);

// Using DQL
$dql = "SELECT u FROM App\Entity\User u WHERE u.status = :status";
$query = $this->entityManager->createQuery($dql)
    ->setParameter('status', UserStatusEnum::ACTIVE);

// Using Criteria
$criteria = Criteria::create()
    ->where(Criteria::expr()->eq('status', UserStatusEnum::ACTIVE));
```

## Troubleshooting

### Common Issues and Solutions

#### 1. Enum Class Not Found

**Error:**
```
Class "App\Enum\UserStatusEnum" not found
```

**Solution:**
- Ensure the enum file was generated in the correct directory
- Check that the namespace matches your configuration
- Run `composer dump-autoload` to refresh the autoloader

#### 2. Invalid Enum Value

**Error:**
```
ValueError: "invalid_status" is not a valid backing value for enum "UserStatusEnum"
```

**Solution:**
- Check that the database contains only valid enum values
- Update database records with invalid values before regenerating entities
- Consider adding data migration scripts

#### 3. Doctrine Schema Validation Errors

**Error:**
```
The field 'status' uses a non-existent 'enumType'
```

**Solution:**
- Ensure you're using Doctrine ORM 2.15+ which supports enum types
- Verify the enum class exists and is properly imported
- Check that the `enumType` attribute syntax is correct

#### 4. Case Name Conflicts

**Error:**
```
Cannot redeclare enum case "ACTIVE"
```

**Solution:**
- This occurs when multiple enum values would generate the same case name
- Manually edit the generated enum to use unique case names
- Consider using more descriptive enum values in the database

#### 5. File Permission Issues

**Error:**
```
Failed to write enum file '/path/to/enum/UserStatusEnum.php'
```

**Solution:**
- Check directory permissions: `chmod 755 src/Enum`
- Ensure the web server has write access to the enum directory
- Verify the enum output directory exists and is writable

### Debug Mode

Enable verbose output to troubleshoot enum generation:

```bash
# Generate with verbose output
php bin/console reverse:generate --verbose

# Dry-run to preview enum generation
php bin/console reverse:generate --dry-run --verbose

# Debug specific tables
php bin/console reverse:generate --tables=users --verbose
```

### Manual Enum Regeneration

If you need to regenerate only enum classes:

```bash
# Force regeneration of all entities (includes enums)
php bin/console reverse:generate --force

# Regenerate specific tables
php bin/console reverse:generate --tables=users --force
```

## Migration Guide

### From String-based to Enum-based Entities

#### Step 1: Backup Existing Code

```bash
# Create backup of current entities
cp -r src/Entity src/Entity.backup.$(date +%Y%m%d_%H%M%S)
```

#### Step 2: Generate New Entities with Enums

```bash
# Generate entities with enum support
php bin/console reverse:generate --force
```

#### Step 3: Update Application Code

**Before:**
```php
// Old string-based approach
$user->setStatus('active');
if ($user->getStatus() === 'active') { ... }
```

**After:**
```php
// New enum-based approach
$user->setStatus(UserStatusEnum::ACTIVE);
if ($user->getStatus() === UserStatusEnum::ACTIVE) { ... }
```

#### Step 4: Update Forms and Validation

**Before:**
```php
->add('status', ChoiceType::class, [
    'choices' => [
        'Active' => 'active',
        'Inactive' => 'inactive',
        'Pending' => 'pending',
    ],
])
```

**After:**
```php
->add('status', EnumType::class, [
    'class' => UserStatusEnum::class,
])
```

#### Step 5: Update Tests

**Before:**
```php
$user = new User();
$user->setStatus('active');
$this->assertEquals('active', $user->getStatus());
```

**After:**
```php
$user = new User();
$user->setStatus(UserStatusEnum::ACTIVE);
$this->assertEquals(UserStatusEnum::ACTIVE, $user->getStatus());
```

#### Step 6: Validate Changes

```bash
# Validate Doctrine schema
php bin/console doctrine:schema:validate

# Run tests to ensure compatibility
vendor/bin/phpunit

# Check for any remaining string usage
grep -r "setStatus('.*')" src/
```

### Gradual Migration Strategy

For large applications, consider a gradual migration:

1. **Phase 1**: Generate new entities alongside existing ones
2. **Phase 2**: Update new features to use enum-based entities
3. **Phase 3**: Gradually refactor existing code module by module
4. **Phase 4**: Remove old string-based entity code

### Rollback Strategy

If issues arise, you can rollback:

```bash
# Restore backup
rm -rf src/Entity
mv src/Entity.backup.YYYYMMDD_HHMMSS src/Entity

# Or regenerate without enum support (if needed)
# Note: This would require temporarily modifying the bundle
```

---

## Additional Resources

- [PHP 8.1 Enums Documentation](https://www.php.net/manual/en/language.enumerations.php)
- [Doctrine Enum Type Documentation](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/basic-mapping.html#enumtype)
- [Symfony Form EnumType Documentation](https://symfony.com/doc/current/reference/forms/types/enum.html)
- [Bundle Architecture Documentation](./ARCHITECTURE.md)
- [Troubleshooting Guide](./TROUBLESHOOTING.md)

---

**This feature represents a significant improvement in type safety and code quality for applications using MySQL ENUM columns. The automatic generation of PHP 8.1 backed enums provides a modern, type-safe approach to handling enumerated values in your Symfony applications.**