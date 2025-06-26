# Generate-and-Copy Command - Complete Usage Guide

## 🎯 Overview

The `generate-and-copy` command provides a seamless, automated workflow for generating Doctrine entities from the Sakila database within the Docker container and automatically transferring them to your local development environment. This command eliminates the need for manual file manipulation and provides a production-ready workflow for database reverse engineering.

## 🚀 Command Syntax

### Basic Syntax
```bash
./docker-test.sh generate-and-copy [destination_directory] [namespace]
```

### Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `destination_directory` | Optional | `./generated-entities` | Local directory where entities will be copied |
| `namespace` | Optional | `Sakila\\Entity` | PHP namespace for generated entities |

### Parameter Details

#### Destination Directory
- **Purpose**: Specifies where generated entities will be stored on the host system
- **Default**: `./generated-entities` (relative to project root)
- **Examples**: 
  - `./src/Entity` - Direct integration with Symfony project
  - `./my-entities` - Custom directory name
  - `./generated/sakila` - Organized subdirectory structure
  - `/tmp/entities` - Temporary location for testing

#### Namespace
- **Purpose**: Defines the PHP namespace for all generated entity classes
- **Default**: `Sakila\\Entity` (escaped for bash compatibility)
- **Format**: Must be a valid PHP namespace with double backslashes for bash escaping
- **Examples**:
  - `"MyApp\\Entity"` - Application-specific namespace
  - `"App\\Entity\\Sakila"` - Symfony standard with subdirectory
  - `"Company\\Database\\Entity"` - Enterprise naming convention
  - `"Legacy\\Migration\\Entity"` - Migration-specific namespace

## 📋 Usage Examples

### Example 1: Basic Usage (Default Settings)
```bash
./docker-test.sh generate-and-copy
```
**Result**: 
- Entities generated in `./generated-entities/`
- Namespace: `Sakila\Entity`
- All 16+ Sakila tables processed
- Repositories generated alongside entities

### Example 2: Custom Destination Directory
```bash
./docker-test.sh generate-and-copy ./my-sakila-entities
```
**Result**:
- Entities generated in `./my-sakila-entities/`
- Namespace: `Sakila\Entity` (default)
- Directory created automatically if it doesn't exist
- Proper file permissions set automatically

### Example 3: Custom Directory and Namespace
```bash
./docker-test.sh generate-and-copy ./src/Entity "MyApp\\Entity"
```
**Result**:
- Entities generated in `./src/Entity/`
- Namespace: `MyApp\Entity`
- Ready for direct Symfony integration
- All use statements updated accordingly

### Example 4: Symfony Project Integration
```bash
./docker-test.sh generate-and-copy ./src/Entity/Sakila "App\\Entity\\Sakila"
```
**Result**:
- Entities in `./src/Entity/Sakila/`
- Namespace: `App\Entity\Sakila`
- Follows Symfony best practices
- Organized in dedicated subdirectory

### Example 5: Temporary Generation for Testing
```bash
./docker-test.sh generate-and-copy /tmp/test-entities "Test\\Entity"
```
**Result**:
- Entities in `/tmp/test-entities/`
- Namespace: `Test\Entity`
- Useful for testing and validation
- Easy cleanup after testing

### Example 6: Enterprise Naming Convention
```bash
./docker-test.sh generate-and-copy ./src/Domain/Entity "Company\\Sakila\\Domain\\Entity"
```
**Result**:
- Entities in `./src/Domain/Entity/`
- Namespace: `Company\Sakila\Domain\Entity`
- Enterprise-grade organization
- Domain-driven design structure

## 🔄 Automated Process Flow

### Phase 1: Environment Validation (5-10 seconds)

#### Docker Environment Check
```bash
[INFO] Checking Docker environment...
[INFO] ✅ Docker is installed and running
[INFO] ✅ Docker Compose is available
[INFO] ✅ Project docker-compose.yml found
```

#### Service Status Verification
```bash
[INFO] Verifying Docker services...
[INFO] ✅ MySQL container is running and healthy
[INFO] ✅ PHP container is running and ready
[INFO] ✅ Database connection successful
```

#### Parameter Validation
```bash
[INFO] Validating parameters...
[INFO] ✅ Destination directory: ./generated-entities
[INFO] ✅ Namespace: Sakila\Entity
[INFO] ✅ All parameters valid
```

### Phase 2: Preparation (2-5 seconds)

#### Local Environment Setup
```bash
[INFO] Preparing local environment...
[INFO] 📁 Creating destination directory: ./generated-entities
[INFO] ✅ Directory created with proper permissions
[INFO] 🧹 Cleaning any existing files in destination
```

#### Container Environment Cleanup
```bash
[INFO] Preparing container environment...
[INFO] 🧹 Cleaning previous generation files in container
[INFO] ✅ Container environment ready
[INFO] 📋 Generation parameters configured
```

### Phase 3: Entity Generation (10-30 seconds)

#### Generation Process Execution
```bash
[INFO] Starting entity generation in Docker container...
[INFO] ⚙️ Executing generation script with parameters:
[INFO]    - Namespace: Sakila\Entity
[INFO]    - Output directory: generated/sakila
[INFO]    - Generate repositories: true
[INFO]    - Use PHP 8 attributes: true
```

#### Progress Monitoring
```bash
[INFO] 📊 Generation progress:
[INFO]    - Analyzing database schema...
[INFO]    - Processing table: actor (1/16)
[INFO]    - Processing table: film (2/16)
[INFO]    - Processing table: customer (3/16)
[INFO]    - ... (continuing for all tables)
[INFO]    - Generating repositories...
[INFO]    - Validating generated code...
```

#### Generation Completion
```bash
[SUCCESS] ✅ Entity generation completed successfully!
[INFO] ⏱️ Generation time: 12.3 seconds
[INFO] 📄 Files generated: 32 (16 entities + 16 repositories)
[INFO] 💾 Total size: 156KB
```

### Phase 4: File Transfer (2-5 seconds)

#### File Discovery and Validation
```bash
[INFO] Discovering generated files in container...
[INFO] 📋 Found 32 PHP files ready for transfer
[INFO] ✅ All files validated for syntax and structure
```

#### Docker Copy Operation
```bash
[INFO] Copying files from container to host...
[INFO] 📋 Using docker cp for efficient transfer
[INFO] 🔄 Transferring 32 files...
[INFO] ✅ All files copied successfully
```

#### Permission and Ownership Correction
```bash
[INFO] Correcting file permissions and ownership...
[INFO] 🔐 Setting file permissions to 644
[INFO] 👤 Setting ownership to current user
[INFO] ✅ Permissions corrected
```

### Phase 5: Validation and Cleanup (2-5 seconds)

#### Syntax Validation
```bash
[INFO] Validating copied files...
[INFO] 🔍 Checking PHP syntax for all files
[INFO] ✅ All files pass syntax validation
[INFO] 🔍 Verifying namespace consistency
[INFO] ✅ Namespace validation successful
```

#### Container Cleanup
```bash
[INFO] Cleaning up container environment...
[INFO] 🧹 Removing temporary files from container
[INFO] ✅ Container cleanup completed
```

#### Final Report Generation
```bash
[SUCCESS] 🎉 Generate-and-copy operation completed successfully!
```

## 📁 Generated File Structure

### Complete File Listing

```
destination_directory/
├── Actor.php                    # Actor entity (2.1KB)
├── ActorRepository.php          # Actor repository (1.2KB)
├── Address.php                  # Address entity (2.8KB)
├── AddressRepository.php        # Address repository (1.2KB)
├── Category.php                 # Category entity (1.9KB)
├── CategoryRepository.php       # Category repository (1.2KB)
├── City.php                     # City entity (2.2KB)
├── CityRepository.php           # City repository (1.2KB)
├── Country.php                  # Country entity (1.8KB)
├── CountryRepository.php        # Country repository (1.2KB)
├── Customer.php                 # Customer entity (3.5KB)
├── CustomerRepository.php       # Customer repository (1.2KB)
├── Film.php                     # Film entity (4.8KB) - Most complex
├── FilmRepository.php           # Film repository (1.2KB)
├── FilmActor.php                # Film-Actor junction (2.1KB)
├── FilmActorRepository.php      # FilmActor repository (1.2KB)
├── FilmCategory.php             # Film-Category junction (2.0KB)
├── FilmCategoryRepository.php   # FilmCategory repository (1.2KB)
├── FilmText.php                 # Film text search (2.3KB)
├── FilmTextRepository.php       # FilmText repository (1.2KB)
├── Inventory.php                # Inventory entity (2.4KB)
├── InventoryRepository.php      # Inventory repository (1.2KB)
├── Language.php                 # Language entity (1.7KB)
├── LanguageRepository.php       # Language repository (1.2KB)
├── Payment.php                  # Payment entity (2.9KB)
├── PaymentRepository.php        # Payment repository (1.2KB)
├── Rental.php                   # Rental entity (3.1KB)
├── RentalRepository.php         # Rental repository (1.2KB)
├── Staff.php                    # Staff entity (3.0KB)
├── StaffRepository.php          # Staff repository (1.2KB)
├── Store.php                    # Store entity (2.5KB)
└── StoreRepository.php          # Store repository (1.2KB)
```

### File Categories and Characteristics

#### Simple Entities (1.7KB - 2.2KB)
- **Country.php**: Basic reference data entity
- **Language.php**: Simple lookup table entity
- **Category.php**: Film category classification

#### Medium Complexity Entities (2.2KB - 3.0KB)
- **Actor.php**: Actor information with film relationships
- **Address.php**: Address with city/country hierarchy
- **City.php**: City with country relationship
- **FilmActor.php**: Many-to-many junction table
- **FilmCategory.php**: Many-to-many junction table

#### Complex Entities (3.0KB - 4.8KB)
- **Customer.php**: Customer with multiple relationships
- **Staff.php**: Staff with store and address relationships
- **Rental.php**: Rental with customer, inventory, and payment relationships
- **Film.php**: Most complex entity with multiple relationships and special features

#### Repository Files (1.2KB each)
- All repository files follow consistent structure
- Include basic CRUD operations
- Prepared for custom query methods
- Follow Doctrine repository patterns

## 📊 Detailed Operation Report

### Sample Complete Output

```bash
$ ./docker-test.sh generate-and-copy ./my-entities "MyApp\\Entity"

[INFO] 🚀 Starting generate-and-copy operation...
[INFO] 📋 Configuration:
[INFO]    - Destination: ./my-entities
[INFO]    - Namespace: MyApp\Entity
[INFO]    - Docker environment: Active
[INFO]    - Sakila database: Ready

[INFO] 🔍 Environment validation...
[INFO] ✅ Docker and Docker Compose available
[INFO] ✅ MySQL container healthy
[INFO] ✅ PHP container ready
[INFO] ✅ Database connection successful

[INFO] 📁 Preparing local environment...
[INFO] ✅ Created directory: ./my-entities
[INFO] ✅ Set permissions: 755

[INFO] 🧹 Preparing container environment...
[INFO] ✅ Cleaned previous generation files
[INFO] ✅ Container ready for generation

[INFO] ⚙️ Starting entity generation...
[INFO] 📊 Processing Sakila database (16 tables)...
[INFO] ⏱️ Generation started at: 2025-06-26 11:45:00

[INFO] 📋 Table processing progress:
[INFO]    ✅ actor (1/16) - 2 relationships detected
[INFO]    ✅ address (2/16) - 2 relationships detected  
[INFO]    ✅ category (3/16) - 0 relationships detected
[INFO]    ✅ city (4/16) - 1 relationship detected
[INFO]    ✅ country (5/16) - 0 relationships detected
[INFO]    ✅ customer (6/16) - 3 relationships detected
[INFO]    ✅ film (7/16) - 4 relationships detected
[INFO]    ✅ film_actor (8/16) - 2 relationships detected
[INFO]    ✅ film_category (9/16) - 2 relationships detected
[INFO]    ✅ film_text (10/16) - 0 relationships detected
[INFO]    ✅ inventory (11/16) - 2 relationships detected
[INFO]    ✅ language (12/16) - 0 relationships detected
[INFO]    ✅ payment (13/16) - 3 relationships detected
[INFO]    ✅ rental (14/16) - 3 relationships detected
[INFO]    ✅ staff (15/16) - 2 relationships detected
[INFO]    ✅ store (16/16) - 2 relationships detected

[SUCCESS] ✅ Entity generation completed!
[INFO] ⏱️ Generation time: 12.3 seconds
[INFO] 📄 Entities generated: 16
[INFO] 📄 Repositories generated: 16
[INFO] 📄 Total files: 32

[INFO] 📋 Discovering generated files...
[INFO] ✅ Found 32 PHP files in container
[INFO] 💾 Total size: 156KB

[INFO] 🔄 Copying files to host system...
[INFO] 📋 Using docker cp for efficient transfer
[INFO] ✅ Copied 32 files successfully

[INFO] 🔐 Correcting file permissions...
[INFO] ✅ Set file permissions to 644
[INFO] ✅ Set directory permissions to 755
[INFO] ✅ Set ownership to current user

[INFO] 🔍 Validating copied files...
[INFO] ✅ PHP syntax validation passed for all files
[INFO] ✅ Namespace consistency verified
[INFO] ✅ File structure validation passed

[INFO] 🧹 Cleaning up container...
[INFO] ✅ Removed temporary files from container
[INFO] ✅ Container cleanup completed

[SUCCESS] 🎉 Generate-and-copy operation completed successfully!

[INFO] 📊 Final Summary:
[INFO] ═══════════════════════════════════════════════════════════
[INFO]    Operation: Generate and Copy Entities
[INFO]    Status: ✅ SUCCESS
[INFO]    Duration: 18.7 seconds total
[INFO]    ├─ Environment setup: 2.1s
[INFO]    ├─ Entity generation: 12.3s  
[INFO]    ├─ File transfer: 2.8s
[INFO]    └─ Validation & cleanup: 1.5s
[INFO] ═══════════════════════════════════════════════════════════
[INFO]    Files Generated: 32 (16 entities + 16 repositories)
[INFO]    Total Size: 156KB
[INFO]    Destination: ./my-entities
[INFO]    Namespace: MyApp\Entity
[INFO]    Relationships: 26 foreign key relationships detected
[INFO]    Data Types: 15 different MySQL types mapped
[INFO] ═══════════════════════════════════════════════════════════

[INFO] 📁 Generated Files Detail:
[INFO] ┌─────────────────────────────┬────────┬─────────────────────────────┐
[INFO] │ File                        │ Size   │ Description                 │
[INFO] ├─────────────────────────────┼────────┼─────────────────────────────┤
[INFO] │ Actor.php                   │ 2.1KB  │ Actor entity with relations │
[INFO] │ ActorRepository.php         │ 1.2KB  │ Actor repository            │
[INFO] │ Address.php                 │ 2.8KB  │ Address with city/country   │
[INFO] │ AddressRepository.php       │ 1.2KB  │ Address repository          │
[INFO] │ Category.php                │ 1.9KB  │ Film category entity        │
[INFO] │ CategoryRepository.php      │ 1.2KB  │ Category repository         │
[INFO] │ City.php                    │ 2.2KB  │ City with country relation  │
[INFO] │ CityRepository.php          │ 1.2KB  │ City repository             │
[INFO] │ Country.php                 │ 1.8KB  │ Country reference data      │
[INFO] │ CountryRepository.php       │ 1.2KB  │ Country repository          │
[INFO] │ Customer.php                │ 3.5KB  │ Customer with complex rels  │
[INFO] │ CustomerRepository.php      │ 1.2KB  │ Customer repository         │
[INFO] │ Film.php                    │ 4.8KB  │ Complex film entity         │
[INFO] │ FilmRepository.php          │ 1.2KB  │ Film repository             │
[INFO] │ FilmActor.php               │ 2.1KB  │ Film-Actor junction         │
[INFO] │ FilmActorRepository.php     │ 1.2KB  │ FilmActor repository        │
[INFO] │ FilmCategory.php            │ 2.0KB  │ Film-Category junction      │
[INFO] │ FilmCategoryRepository.php  │ 1.2KB  │ FilmCategory repository     │
[INFO] │ FilmText.php                │ 2.3KB  │ Film text search entity     │
[INFO] │ FilmTextRepository.php      │ 1.2KB  │ FilmText repository         │
[INFO] │ Inventory.php               │ 2.4KB  │ Inventory with relations    │
[INFO] │ InventoryRepository.php     │ 1.2KB  │ Inventory repository        │
[INFO] │ Language.php                │ 1.7KB  │ Language reference          │
[INFO] │ LanguageRepository.php      │ 1.2KB  │ Language repository         │
[INFO] │ Payment.php                 │ 2.9KB  │ Payment with relations      │
[INFO] │ PaymentRepository.php       │ 1.2KB  │ Payment repository          │
[INFO] │ Rental.php                  │ 3.1KB  │ Rental business entity      │
[INFO] │ RentalRepository.php        │ 1.2KB  │ Rental repository           │
[INFO] │ Staff.php                   │ 3.0KB  │ Staff with store relations  │
[INFO] │ StaffRepository.php         │ 1.2KB  │ Staff repository            │
[INFO] │ Store.php                   │ 2.5KB  │ Store with address/staff    │
[INFO] │ StoreRepository.php         │ 1.2KB  │ Store repository            │
[INFO] └─────────────────────────────┴────────┴─────────────────────────────┘

[INFO] 💡 Next Steps for Symfony Integration:
[INFO] ═══════════════════════════════════════════════════════════
[INFO] 1. Copy to Symfony Project:
[INFO]    cp ./my-entities/*.php /path/to/symfony/project/src/Entity/
[INFO] 
[INFO] 2. Update Namespaces (if needed):
[INFO]    find /path/to/project/src/Entity/ -name "*.php" \
[INFO]      -exec sed -i 's/MyApp\\Entity/App\\Entity/g' {} \;
[INFO] 
[INFO] 3. Validate with Doctrine:
[INFO]    cd /path/to/symfony/project
[INFO]    php bin/console doctrine:schema:validate
[INFO] 
[INFO] 4. Generate Migrations:
[INFO]    php bin/console doctrine:migrations:diff
[INFO]    php bin/console doctrine:migrations:migrate
[INFO] 
[INFO] 5. Clear Cache:
[INFO]    php bin/console cache:clear
[INFO] ═══════════════════════════════════════════════════════════

[SUCCESS] 🎉 Operation completed successfully!
[INFO] 📧 For support: https://github.com/eprofos/reverse-engineering-bundle/issues
[INFO] 📖 Documentation: https://github.com/eprofos/reverse-engineering-bundle#readme
```

## ✨ Key Features and Benefits

### Automation Benefits
- ✅ **Complete Automation**: No manual file manipulation required
- ✅ **Error Prevention**: Automated validation prevents common mistakes
- ✅ **Consistent Results**: Reproducible generation process
- ✅ **Time Saving**: Reduces setup time from hours to minutes
- ✅ **Production Ready**: Generated code follows best practices

### File Management Features
- ✅ **Automatic Directory Creation**: Creates destination directories as needed
- ✅ **Permission Management**: Sets appropriate file and directory permissions
- ✅ **Conflict Resolution**: Handles existing files gracefully
- ✅ **Cleanup Automation**: Removes temporary files automatically
- ✅ **Structure Preservation**: Maintains proper file organization

### Quality Assurance Features
- ✅ **Syntax Validation**: Validates PHP syntax of all generated files
- ✅ **Namespace Consistency**: Ensures consistent namespace usage
- ✅ **Relationship Validation**: Verifies foreign key relationships
- ✅ **Type Safety**: Generates type-safe PHP 8+ code
- ✅ **Doctrine Compliance**: Ensures Doctrine ORM compatibility

### Developer Experience Features
- ✅ **Detailed Progress Reporting**: Real-time progress updates
- ✅ **Comprehensive Logging**: Detailed operation logs for debugging
- ✅ **Integration Instructions**: Step-by-step Symfony integration guide
- ✅ **Error Handling**: Clear error messages with resolution suggestions
- ✅ **Performance Metrics**: Timing and size information

## 🚨 Prerequisites and Requirements

### System Requirements
- **Docker**: Version 20.0+ with Docker Compose
- **Operating System**: Linux, macOS, or Windows with WSL2
- **Memory**: Minimum 2GB available RAM
- **Disk Space**: Minimum 1GB free space
- **Network**: Internet connection for initial Docker image downloads

### Docker Environment Requirements
- **MySQL Container**: Must be running and healthy
- **PHP Container**: Must be running with proper extensions
- **Sakila Database**: Must be loaded and accessible
- **Network Connectivity**: Containers must be able to communicate

### File System Requirements
- **Write Permissions**: Destination directory must be writable
- **Path Validity**: Destination path must be valid and accessible
- **Disk Space**: Sufficient space for generated files (typically < 1MB)

## 🔍 Troubleshooting Guide

### Common Issues and Solutions

#### Issue: Docker Environment Not Ready
**Symptoms:**
```bash
ERROR: Docker environment is not running
ERROR: MySQL container not found
```

**Solutions:**
```bash
# Start Docker environment
./docker-test.sh start

# Verify services are running
./docker-test.sh status

# Check Docker Compose status
docker-compose ps
```

#### Issue: No PHP Files Generated
**Symptoms:**
```bash
ERROR: No PHP files found in container
WARNING: Generation may have failed
```

**Solutions:**
```bash
# Check generation logs
docker-compose logs php | tail -50

# Verify database connection
docker-compose exec php php -r "
try {
    \$pdo = new PDO('mysql:host=mysql;dbname=sakila', 'sakila_user', 'sakila_password');
    echo 'Connection OK\n';
} catch (Exception \$e) {
    echo 'Error: ' . \$e->getMessage() . '\n';
}
"

# Manual generation test
docker-compose exec php php scripts/generate-entities.php \
    --namespace="Test\\Entity" \
    --output-dir="generated/test"
```

#### Issue: Permission Denied Errors
**Symptoms:**
```bash
ERROR: Permission denied when creating directory
ERROR: Cannot write to destination directory
```

**Solutions:**
```bash
# Check current permissions
ls -la ./

# Create directory manually with proper permissions
mkdir -p ./generated-entities
chmod 755 ./generated-entities

# Fix ownership if needed
sudo chown -R $USER:$USER ./generated-entities

# Use alternative destination
./docker-test.sh generate-and-copy /tmp/entities "Test\\Entity"
```

#### Issue: Invalid Namespace Errors
**Symptoms:**
```bash
ERROR: Invalid namespace format
ERROR: Namespace contains invalid characters
```

**Solutions:**
```bash
# Use proper namespace format with double backslashes
./docker-test.sh generate-and-copy ./entities "MyApp\\Entity"

# Avoid special characters
./docker-test.sh generate-and-copy ./entities "SimpleEntity"

# Use quotes for complex namespaces
./docker-test.sh generate-and-copy ./entities "Company\\Project\\Entity"
```

#### Issue: File Copy Failures
**Symptoms:**
```bash
ERROR: Failed to copy files from container
WARNING: Some files may be missing
```

**Solutions:**
```bash
# Check container file system
docker-compose exec php ls -la generated/

# Manual file copy
docker cp $(docker-compose ps -q php):/app/generated/. ./manual-copy/

# Verify container permissions
docker-compose exec php whoami
docker-compose exec php id

# Restart containers if needed
docker-compose restart
```

### Advanced Troubleshooting

#### Debug Mode Execution
```bash
# Enable verbose output
DOCKER_DEBUG=1 ./docker-test.sh generate-and-copy ./debug "Debug\\Entity"

# Check all container logs
docker-compose logs --tail=100

# Monitor resource usage
docker stats --no-stream
```

#### Manual Process Verification
```bash
# Step 1: Verify environment
./docker-test.sh status

# Step 2: Test generation manually
docker-compose exec php php scripts/generate-entities.php \
    --namespace="Manual\\Test" \
    --output-dir="generated/manual"

# Step 3: Check generated files
docker-compose exec php find generated/ -name "*.php" -ls

# Step 4: Manual copy
docker cp $(docker-compose ps -q php):/app/generated/manual/. ./manual-test/

# Step 5: Validate copied files
find ./manual-test/ -name "*.php" -exec php -l {} \;
```

#### Performance Optimization
```bash
# Increase container resources
docker-compose down
echo "PHP_MEMORY_LIMIT=1G" >> .env.docker
docker-compose up -d

# Monitor generation performance
time ./docker-test.sh generate-and-copy ./perf-test "Perf\\Entity"

# Check MySQL performance
docker-compose exec mysql mysql -u root -p -e "SHOW PROCESSLIST;"
```

## 📚 Integration Examples

### Symfony Project Integration

#### Complete Integration Workflow
```bash
# Step 1: Generate entities
./docker-test.sh generate-and-copy ./temp-entities "App\\Entity\\Sakila"

# Step 2: Copy to Symfony project
cp ./temp-entities/*.php /path/to/symfony/project/src/Entity/Sakila/

# Step 3: Update composer autoload
cd /path/to/symfony/project
composer dump-autoload

# Step 4: Validate entities
php bin/console doctrine:mapping:info
php bin/console doctrine:schema:validate

# Step 5: Generate migrations
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate --dry-run

# Step 6: Clear cache
php bin/console cache:clear
```

#### Custom Repository Integration
```bash
# Generate with custom namespace for repositories
./docker-test.sh generate-and-copy ./entities "App\\Entity"

# Create custom repository base class
cat > /path/to/project/src/Repository/BaseRepository.php << 'EOF'
<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

abstract class BaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, string $entityClass)
    {
        parent::__construct($registry, $entityClass);
    }
    
    // Add common repository methods here
}
EOF

# Update generated repositories to extend BaseRepository
find /path/to/project/src/Entity/ -name "*Repository.php" \
    -exec sed -i 's/ServiceEntityRepository/BaseRepository/g' {} \;
```

### API Platform Integration
```bash
# Generate entities for API Platform
./docker-test.sh generate-and-copy ./api-entities "App\\Entity\\Api"

# Add API Platform annotations manually
# (This would be automated in future versions)
```

### Testing Integration
```bash
# Generate test entities
./docker-test.sh generate-and-copy ./test/fixtures/entities "Test\\Fixtures\\Entity"

# Use for test fixtures and data providers
```

## 🔮 Future Enhancements

### Planned Features (Version 0.2.0)
- **Custom Template Support**: Use custom Twig templates for generation
- **Selective Table Generation**: Generate only specific tables via command options
- **Namespace Mapping**: Map different tables to different namespaces
- **API Platform Integration**: Automatic API Platform annotation generation
- **Validation Constraints**: Automatic Symfony validation constraint generation

### Advanced Features (Version 0.3.0)
- **Migration Generation**: Automatic Doctrine migration file creation
- **Fixture Generation**: Test fixture generation from existing data
- **Form Generation**: Automatic Symfony form class generation
- **GraphQL Integration**: GraphQL type generation for entities

### Enterprise Features (Version 1.0.0)
- **Multi-Database Support**: Generate from multiple databases simultaneously
- **Custom Workflows**: Configurable generation workflows
- **CI/CD Integration**: GitHub Actions and GitLab CI integration
- **Enterprise Templates**: Industry-specific entity templates

---

**The generate-and-copy command represents the pinnacle of automated database reverse engineering, providing a seamless bridge between legacy databases and modern Symfony applications.**