# Docker Setup with Sakila Database - Complete Guide

## 📋 Overview

This comprehensive Docker environment provides a complete testing setup for the ReverseEngineeringBundle using the Sakila database - a realistic MySQL sample database that simulates a DVD rental store with complex relationships and diverse data types.

## 🏗️ Architecture

### Docker Services

- **MySQL 8.0**: Primary database server with Sakila database pre-loaded
- **PHP 8.2**: CLI environment with all required extensions for the bundle
- **phpMyAdmin**: Web-based database administration interface (optional)
- **Persistent Volumes**: Data persistence across container restarts

### Created Files and Structure

#### Docker Configuration
- [`docker-compose.yml`](docker-compose.yml) - Main service orchestration
- [`docker/php/Dockerfile`](docker/php/Dockerfile) - Custom PHP image with extensions
- [`docker/php/conf/php.ini`](docker/php/conf/php.ini) - Optimized PHP configuration
- [`docker/mysql/conf/my.cnf`](docker/mysql/conf/my.cnf) - MySQL performance tuning
- [`docker/mysql/init/01-sakila-schema.sql`](docker/mysql/init/01-sakila-schema.sql) - Sakila schema
- [`docker/mysql/init/02-download-sakila-data.sh`](docker/mysql/init/02-download-sakila-data.sh) - Data loading script

#### Integration Testing
- [`tests/Integration/SakilaIntegrationTest.php`](tests/Integration/SakilaIntegrationTest.php) - Comprehensive Sakila tests
- [`tests/TestHelper.php`](tests/TestHelper.php) - Docker utility methods
- [`tests/bootstrap.php`](tests/bootstrap.php) - Updated test configuration
- [`phpunit.docker.xml`](phpunit.docker.xml) - Docker-specific PHPUnit configuration

#### Utility Scripts and Documentation
- [`docker-test.sh`](docker-test.sh) - Comprehensive Docker utility script
- [`docker/README.md`](docker/README.md) - Detailed Docker documentation
- [`.env.docker`](.env.docker) - Environment variables configuration
- [`.gitignore`](.gitignore) - Updated with Docker exclusions

## 🚀 Quick Start Guide

### Step 1: Start the Environment

```bash
# Method 1: Using the utility script (recommended)
./docker-test.sh start

# Method 2: Direct Docker Compose
docker-compose up -d

# Method 3: With build (if images need updating)
docker-compose up -d --build
```

### Step 2: Verify Services

```bash
# Check service status
./docker-test.sh status

# Or manually check
docker-compose ps

# View MySQL logs to ensure it's ready
docker-compose logs mysql | tail -20
```

### Step 3: Run Sakila Integration Tests

```bash
# Run comprehensive Sakila tests
./docker-test.sh test-sakila

# Or run manually
docker-compose exec php vendor/bin/phpunit tests/Integration/SakilaIntegrationTest.php

# Run all tests in Docker environment
./docker-test.sh test-all
```

### Step 4: Generate Entities

```bash
# Generate and copy entities automatically (recommended)
./docker-test.sh generate-and-copy

# Generate with custom settings
./docker-test.sh generate-and-copy ./my-entities "MyApp\\Entity"

# Manual generation in container only
./docker-test.sh generate
```

## 🧪 Comprehensive Testing Capabilities

### Sakila Database Features

The Sakila database provides an ideal testing environment with:

#### Table Structure (16+ Tables)
- **`actor`**: Film actors with personal information
- **`film`**: Movie catalog with ratings, descriptions, and metadata
- **`customer`**: Customer database with addresses and contact information
- **`rental`**: Rental transactions with dates and return information
- **`payment`**: Payment records with amounts and timestamps
- **`inventory`**: Film inventory tracking across stores
- **`store`**: Store locations with staff assignments
- **`staff`**: Employee information and store assignments
- **`address`**: Address information linked to cities and countries
- **`city`**: City database linked to countries
- **`country`**: Country reference data
- **`category`**: Film category classifications
- **`language`**: Language options for films
- **`film_actor`**: Many-to-many relationship between films and actors
- **`film_category`**: Many-to-many relationship between films and categories
- **`film_text`**: Full-text search data for films

#### Complex Relationships
- **Many-to-One**: `customer` → `address`, `film` → `language`, `rental` → `customer`
- **One-to-Many**: `customer` → `rental`, `film` → `inventory`, `store` → `staff`
- **Many-to-Many**: `film` ↔ `actor` (via `film_actor`), `film` ↔ `category` (via `film_category`)
- **Self-Referencing**: Address hierarchies and staff management structures

#### Diverse Data Types
- **Integer Types**: `TINYINT`, `SMALLINT`, `MEDIUMINT`, `INT` with various constraints
- **Decimal Types**: `DECIMAL(4,2)`, `DECIMAL(5,2)` for monetary values
- **String Types**: `VARCHAR(255)`, `CHAR(3)`, `TEXT` with different lengths
- **Date/Time Types**: `DATE`, `DATETIME`, `TIMESTAMP`, `YEAR` with timezone handling
- **Enumeration Types**: `ENUM('G','PG','PG-13','R','NC-17')` for film ratings
- **Set Types**: `SET('Trailers','Commentaries','Deleted Scenes','Behind the Scenes')` for special features
- **Boolean Types**: `BOOLEAN` for active/inactive flags
- **Binary Types**: `BLOB` for binary data storage

### Available Test Suites

#### 1. Connection and Environment Tests
```bash
# Test Docker environment connectivity
docker-compose exec php vendor/bin/phpunit tests/Integration/SakilaIntegrationTest.php::testDockerEnvironmentConnection
```

#### 2. Complete Entity Generation Tests
```bash
# Test full entity generation process
docker-compose exec php vendor/bin/phpunit tests/Integration/SakilaIntegrationTest.php::testCompleteEntityGeneration
```

#### 3. Complex Relationship Tests
```bash
# Test relationship detection and generation
docker-compose exec php vendor/bin/phpunit tests/Integration/SakilaIntegrationTest.php::testComplexRelationships
```

#### 4. Data Type Mapping Tests
```bash
# Test all supported data types
docker-compose exec php vendor/bin/phpunit tests/Integration/SakilaIntegrationTest.php::testDataTypeMapping
```

#### 5. Performance and Scale Tests
```bash
# Test performance with realistic database size
docker-compose exec php vendor/bin/phpunit tests/Integration/SakilaIntegrationTest.php::testPerformanceOnFullDatabase
```

#### 6. Many-to-Many Relationship Tests
```bash
# Test junction table handling
docker-compose exec php vendor/bin/phpunit tests/Integration/SakilaIntegrationTest.php::testManyToManyRelations
```

#### 7. Table Exclusion Tests
```bash
# Test selective table processing
docker-compose exec php vendor/bin/phpunit tests/Integration/SakilaIntegrationTest.php::testTableExclusion
```

#### 8. Generated Code Validation Tests
```bash
# Test PHP syntax and Doctrine validation
docker-compose exec php vendor/bin/phpunit tests/Integration/SakilaIntegrationTest.php::testGeneratedCodeValidation
```

#### 9. Metadata Extraction Tests
```bash
# Test comprehensive metadata extraction
docker-compose exec php vendor/bin/phpunit tests/Integration/SakilaIntegrationTest.php::testMetadataExtraction
```

#### 10. Custom Namespace Tests
```bash
# Test custom namespace generation
docker-compose exec php vendor/bin/phpunit tests/Integration/SakilaIntegrationTest.php::testCustomNamespaceGeneration
```

## 📊 Expected Performance Metrics

### Generation Performance
- **Total Generation Time**: < 30 seconds for all 16 tables
- **Memory Usage**: < 128 MB peak memory consumption
- **Tables Processed**: 16+ main tables with relationships
- **Entities Generated**: 16+ entity classes with repositories
- **Files Created**: 32+ files (entities + repositories)
- **Relationship Detection**: 20+ foreign key relationships mapped

### Database Metrics
- **Total Records**: 47,000+ records across all tables
- **Largest Table**: `rental` with 16,000+ records
- **Complex Queries**: Foreign key analysis across 16 tables
- **Index Analysis**: 25+ indexes including composite keys

### Code Quality Metrics
- **Generated Code**: 100% valid PHP syntax
- **Doctrine Validation**: All entities pass schema validation
- **PSR-12 Compliance**: Generated code follows coding standards
- **Type Safety**: Full PHP 8+ type declarations

## 🔧 Advanced Configuration

### Environment Variables

Customize the Docker environment by modifying [`.env.docker`](.env.docker):

```bash
# Database Configuration
MYSQL_ROOT_PASSWORD=root_password
MYSQL_DATABASE=sakila
MYSQL_USER=sakila_user
MYSQL_PASSWORD=sakila_password
MYSQL_EXTERNAL_PORT=3306

# PHP Configuration
PHP_MEMORY_LIMIT=512M
PHP_MAX_EXECUTION_TIME=300

# phpMyAdmin Configuration
PHPMYADMIN_EXTERNAL_PORT=8080

# Volume Configuration
MYSQL_DATA_VOLUME=mysql_data
```

### MySQL Configuration

Advanced MySQL settings in [`docker/mysql/conf/my.cnf`](docker/mysql/conf/my.cnf):

```ini
[mysqld]
# Character Set and Collation
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci

# InnoDB Configuration
innodb_buffer_pool_size = 256M
innodb_log_file_size = 64M
innodb_flush_log_at_trx_commit = 2

# Query Cache
query_cache_type = 1
query_cache_size = 32M

# Connection Settings
max_connections = 200
wait_timeout = 600

# Logging
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
```

### PHP Configuration

Optimized PHP settings in [`docker/php/conf/php.ini`](docker/php/conf/php.ini):

```ini
; Memory and Execution
memory_limit = 512M
max_execution_time = 300
max_input_time = 300

; MySQL Extensions
extension=pdo_mysql
extension=mysqli

; OPcache Configuration
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 4000
opcache.revalidate_freq = 60

; Error Reporting
error_reporting = E_ALL
display_errors = On
log_errors = On
```

### PHPUnit Docker Configuration

Specialized test configuration in [`phpunit.docker.xml`](phpunit.docker.xml):

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true">
    
    <testsuites>
        <testsuite name="Docker Integration">
            <directory>tests/Integration</directory>
        </testsuite>
        <testsuite name="Sakila">
            <file>tests/Integration/SakilaIntegrationTest.php</file>
        </testsuite>
    </testsuites>
    
    <php>
        <env name="DATABASE_URL" value="mysql://sakila_user:sakila_password@mysql:3306/sakila"/>
        <env name="APP_ENV" value="test"/>
        <env name="DOCKER_ENVIRONMENT" value="true"/>
    </php>
    
    <coverage>
        <include>
            <directory suffix=".php">src</directory>
        </include>
        <report>
            <html outputDirectory="coverage/docker-html"/>
            <clover outputFile="coverage/docker-clover.xml"/>
        </report>
    </coverage>
</phpunit>
```

## 🔄 Generate-and-Copy Command - Detailed Guide

### Command Overview

The `generate-and-copy` command provides a seamless workflow for generating entities in the Docker container and automatically copying them to the host system.

### Syntax and Parameters

```bash
./docker-test.sh generate-and-copy [destination_directory] [namespace]
```

#### Parameters
- **`destination_directory`** (optional): Local directory for generated entities
  - Default: `./generated-entities`
  - Examples: `./src/Entity`, `./my-entities`, `./generated/sakila`

- **`namespace`** (optional): PHP namespace for generated entities
  - Default: `Sakila\\Entity`
  - Examples: `MyApp\\Entity`, `App\\Entity\\Sakila`, `Company\\Database\\Entity`

### Usage Examples

#### Basic Usage
```bash
# Generate with default settings
./docker-test.sh generate-and-copy
# Result: Entities in ./generated-entities/ with Sakila\Entity namespace
```

#### Custom Directory
```bash
# Specify custom destination directory
./docker-test.sh generate-and-copy ./my-sakila-entities
# Result: Entities in ./my-sakila-entities/ with Sakila\Entity namespace
```

#### Custom Directory and Namespace
```bash
# Full customization
./docker-test.sh generate-and-copy ./src/Entity "MyApp\\Entity"
# Result: Entities in ./src/Entity/ with MyApp\Entity namespace
```

#### Production-Ready Example
```bash
# Generate for production use
./docker-test.sh generate-and-copy ./src/Entity/Sakila "App\\Entity\\Sakila"
# Result: Production-ready entities with proper namespace
```

### Automated Process Flow

#### Phase 1: Environment Verification
1. **Docker Availability Check**: Verifies Docker and Docker Compose installation
2. **Service Status Check**: Ensures MySQL container is running and healthy
3. **Database Connectivity**: Tests connection to Sakila database
4. **Permission Validation**: Checks write permissions for destination directory

#### Phase 2: Preparation
1. **Local Directory Creation**: Creates destination directory with proper permissions
2. **Container Cleanup**: Removes any existing generated files in container
3. **Namespace Validation**: Validates PHP namespace syntax
4. **Parameter Logging**: Records all parameters for audit trail

#### Phase 3: Entity Generation
1. **Script Execution**: Runs generation script in PHP container
2. **Progress Monitoring**: Tracks generation progress with timing
3. **Error Detection**: Monitors for generation errors and failures
4. **Validation**: Verifies successful entity creation in container

#### Phase 4: File Transfer
1. **File Discovery**: Lists all generated PHP files in container
2. **Docker Copy**: Uses `docker cp` to transfer files to host
3. **Structure Preservation**: Maintains directory structure during copy
4. **Permission Correction**: Sets appropriate file permissions on host

#### Phase 5: Validation and Cleanup
1. **Syntax Validation**: Validates PHP syntax of copied files (if PHP available on host)
2. **File Count Verification**: Confirms all files were copied successfully
3. **Container Cleanup**: Removes temporary files from container
4. **Report Generation**: Creates detailed operation report

### Generated File Structure

```
destination_directory/
├── Actor.php                    # Actor entity with film relationships
├── ActorRepository.php          # Actor repository with custom queries
├── Address.php                  # Address entity with city/country relations
├── AddressRepository.php        # Address repository
├── Category.php                 # Film category entity
├── CategoryRepository.php       # Category repository
├── City.php                     # City entity with country relation
├── CityRepository.php           # City repository
├── Country.php                  # Country entity
├── CountryRepository.php        # Country repository
├── Customer.php                 # Customer entity with complex relations
├── CustomerRepository.php       # Customer repository
├── Film.php                     # Film entity (most complex with multiple relations)
├── FilmRepository.php           # Film repository
├── FilmActor.php                # Film-Actor junction table entity
├── FilmActorRepository.php      # FilmActor repository
├── FilmCategory.php             # Film-Category junction table entity
├── FilmCategoryRepository.php   # FilmCategory repository
├── FilmText.php                 # Film text search entity
├── FilmTextRepository.php       # FilmText repository
├── Inventory.php                # Inventory entity with film/store relations
├── InventoryRepository.php      # Inventory repository
├── Language.php                 # Language entity
├── LanguageRepository.php       # Language repository
├── Payment.php                  # Payment entity with customer/rental relations
├── PaymentRepository.php        # Payment repository
├── Rental.php                   # Rental entity with complex business logic
├── RentalRepository.php         # Rental repository
├── Staff.php                    # Staff entity with store relations
├── StaffRepository.php          # Staff repository
├── Store.php                    # Store entity with address/staff relations
└── StoreRepository.php          # Store repository
```

### Detailed Operation Report

The command provides comprehensive reporting:

```bash
[INFO] 📊 Operation Summary:
[INFO]    - Generation Time: 12.3 seconds
[INFO]    - Files Generated: 32
[INFO]    - Files Copied: 32
[INFO]    - Total Size: 156KB
[INFO]    - Destination: ./generated-entities
[INFO]    - Namespace: Sakila\Entity
[SUCCESS] - Syntax Validation: ✅ All files valid

[INFO] 📁 Generated Files:
[INFO]    - Actor.php (2.1KB) - Actor entity with film relationships
[INFO]    - ActorRepository.php (1.2KB) - Repository with custom methods
[INFO]    - Film.php (4.8KB) - Complex entity with multiple relations
[INFO]    - FilmRepository.php (1.2KB) - Repository with query methods
[INFO]    - Customer.php (3.2KB) - Customer with address and rental relations
[INFO]    - ... (complete file listing)

[INFO] 💡 Integration Instructions:
[INFO]    1. Copy files to your Symfony project: cp ./generated-entities/*.php /path/to/project/src/Entity/
[INFO]    2. Update namespaces if needed: sed -i 's/Sakila\\Entity/App\\Entity/g' *.php
[INFO]    3. Validate with Doctrine: php bin/console doctrine:schema:validate
[INFO]    4. Generate migrations if needed: php bin/console doctrine:migrations:diff
```

### Integration with Symfony Projects

#### Step 1: Copy Generated Files
```bash
# Copy to Symfony project
cp ./generated-entities/*.php /path/to/symfony/project/src/Entity/
```

#### Step 2: Update Namespaces (if needed)
```bash
# Update namespace in all files
find /path/to/symfony/project/src/Entity/ -name "*.php" -exec sed -i 's/namespace Sakila\\Entity;/namespace App\\Entity;/g' {} \;

# Update use statements
find /path/to/symfony/project/src/Entity/ -name "*.php" -exec sed -i 's/Sakila\\Entity\\/App\\Entity\\/g' {} \;
```

#### Step 3: Validate with Doctrine
```bash
cd /path/to/symfony/project

# Validate entity mapping
php bin/console doctrine:schema:validate

# Check entity information
php bin/console doctrine:mapping:info
```

#### Step 4: Generate Migrations
```bash
# Generate migration for new entities
php bin/console doctrine:migrations:diff

# Review and execute migration
php bin/console doctrine:migrations:migrate
```

## 🐛 Troubleshooting Guide

### Common Issues and Solutions

#### MySQL Container Won't Start

**Symptoms:**
```bash
ERROR: MySQL container exits immediately
ERROR: Port 3306 already in use
```

**Solutions:**
```bash
# Check if port 3306 is in use
netstat -tlnp | grep 3306
sudo lsof -i :3306

# Stop conflicting MySQL service
sudo systemctl stop mysql
sudo service mysql stop

# Change external port in .env.docker
MYSQL_EXTERNAL_PORT=3307

# Rebuild containers
docker-compose down -v
docker-compose up -d --build
```

#### Connection Refused Errors

**Symptoms:**
```bash
SQLSTATE[HY000] [2002] Connection refused
```

**Solutions:**
```bash
# Wait for MySQL to be fully ready (can take 60-90 seconds)
./docker-test.sh status

# Check MySQL logs
docker-compose logs mysql | tail -20

# Test connection manually
docker-compose exec mysql mysql -u sakila_user -p sakila
```

#### Sakila Database Not Loaded

**Symptoms:**
```bash
ERROR: Table 'sakila.actor' doesn't exist
```

**Solutions:**
```bash
# Check database initialization logs
docker-compose logs mysql | grep -i sakila

# Manually reload Sakila data
docker-compose exec mysql mysql -u root -p < docker/mysql/init/01-sakila-schema.sql

# Complete environment reset
docker-compose down -v
docker-compose up -d
```

#### Tests Failing

**Symptoms:**
```bash
Tests fail with database connection errors
```

**Solutions:**
```bash
# Verify Docker environment
./docker-test.sh status

# Test database connection from PHP container
docker-compose exec php php -r "
try {
    \$pdo = new PDO('mysql:host=mysql;dbname=sakila', 'sakila_user', 'sakila_password');
    echo 'Connection successful\n';
    \$stmt = \$pdo->query('SELECT COUNT(*) FROM actor');
    echo 'Actors: ' . \$stmt->fetchColumn() . '\n';
} catch (Exception \$e) {
    echo 'Error: ' . \$e->getMessage() . '\n';
}
"

# Run tests with verbose output
docker-compose exec php vendor/bin/phpunit tests/Integration/SakilaIntegrationTest.php -v
```

#### Generate-and-Copy Command Issues

**Symptoms:**
```bash
ERROR: No PHP files found to copy
ERROR: Permission denied when copying files
```

**Solutions:**
```bash
# Check generation in container
docker-compose exec php ls -la generated/

# Verify container permissions
docker-compose exec php whoami
docker-compose exec php id

# Manual file copy
docker cp $(docker-compose ps -q php):/app/generated/. ./manual-copy/

# Fix local permissions
sudo chown -R $USER:$USER ./generated-entities/
chmod -R 644 ./generated-entities/*.php
```

#### Performance Issues

**Symptoms:**
```bash
Generation takes longer than 60 seconds
High memory usage during generation
```

**Solutions:**
```bash
# Increase PHP memory limit
echo "PHP_MEMORY_LIMIT=1G" >> .env.docker
docker-compose restart php

# Monitor resource usage
docker stats

# Use table filtering for large databases
./docker-test.sh generate-and-copy ./entities "App\\Entity" --tables=actor,film,customer
```

### Diagnostic Commands

#### Environment Status Check
```bash
# Comprehensive status check
./docker-test.sh status

# Individual service status
docker-compose ps
docker-compose logs mysql --tail=20
docker-compose logs php --tail=20
```

#### Database Connectivity Test
```bash
# Test from host
mysql -h 127.0.0.1 -P 3306 -u sakila_user -p sakila

# Test from container
docker-compose exec mysql mysql -u sakila_user -p sakila -e "SHOW TABLES;"
```

#### Performance Monitoring
```bash
# Monitor resource usage
docker stats --no-stream

# Check MySQL performance
docker-compose exec mysql mysql -u root -p -e "SHOW PROCESSLIST;"
docker-compose exec mysql mysql -u root -p -e "SHOW ENGINE INNODB STATUS\G"
```

#### File System Checks
```bash
# Check generated files in container
docker-compose exec php find generated/ -name "*.php" -ls

# Check host file permissions
ls -la generated-entities/

# Verify file content
head -20 generated-entities/Actor.php
```

## 📈 Performance Optimization

### MySQL Optimization

#### Buffer Pool Tuning
```ini
# Adjust based on available memory
innodb_buffer_pool_size = 512M  # For 2GB+ systems
innodb_buffer_pool_size = 256M  # For 1GB systems
innodb_buffer_pool_size = 128M  # For 512MB systems
```

#### Query Cache Configuration
```ini
# Enable query cache for repeated queries
query_cache_type = 1
query_cache_size = 64M
query_cache_limit = 2M
```

#### Connection Optimization
```ini
# Adjust based on concurrent usage
max_connections = 100          # For development
max_connections = 200          # For testing
thread_cache_size = 16
```

### PHP Optimization

#### Memory Management
```ini
# Adjust based on database size
memory_limit = 256M    # For small databases (<50 tables)
memory_limit = 512M    # For medium databases (50-100 tables)
memory_limit = 1G      # For large databases (100+ tables)
```

#### OPcache Configuration
```ini
# Optimize for development
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 4000
opcache.validate_timestamps = 1
opcache.revalidate_freq = 2
```

### Docker Resource Limits

#### Container Resource Allocation
```yaml
# docker-compose.yml
services:
  mysql:
    deploy:
      resources:
        limits:
          memory: 512M
          cpus: '1.0'
        reservations:
          memory: 256M
          cpus: '0.5'
  
  php:
    deploy:
      resources:
        limits:
          memory: 256M
          cpus: '0.5'
```

## 🔒 Security Considerations

### Database Security

#### User Privileges
```sql
-- Create dedicated user with minimal privileges
CREATE USER 'sakila_readonly'@'%' IDENTIFIED BY 'readonly_password';
GRANT SELECT ON sakila.* TO 'sakila_readonly'@'%';
FLUSH PRIVILEGES;
```

#### Network Security
```yaml
# docker-compose.yml - Restrict network access
services:
  mysql:
    ports:
      - "127.0.0.1:3306:3306"  # Bind to localhost only
    networks:
      - internal
      
networks:
  internal:
    driver: bridge
    internal: true  # No external access
```

### Container Security

#### Non-Root User
```dockerfile
# Dockerfile - Run as non-root user
RUN groupadd -r appuser && useradd -r -g appuser appuser
USER appuser
```

#### Read-Only File System
```yaml
# docker-compose.yml - Read-only containers
services:
  php:
    read_only: true
    tmpfs:
      - /tmp
      - /var/tmp
```

## 📝 Maintenance and Updates

### Regular Maintenance Tasks

#### Database Maintenance
```bash
# Optimize tables
docker-compose exec mysql mysql -u root -p -e "OPTIMIZE TABLE sakila.actor, sakila.film, sakila.customer;"

# Check table integrity
docker-compose exec mysql mysql -u root -p -e "CHECK TABLE sakila.actor, sakila.film;"

# Update statistics
docker-compose exec mysql mysql -u root -p -e "ANALYZE TABLE sakila.actor, sakila.film;"
```

#### Container Updates
```bash
# Update base images
docker-compose pull

# Rebuild with latest changes
docker-compose build --no-cache

# Restart with updates
docker-compose down && docker-compose up -d
```

#### Log Management
```bash
# Rotate Docker logs
docker system prune -f

# Clear MySQL logs
docker-compose exec mysql mysql -u root -p -e "RESET MASTER;"

# Archive old logs
docker-compose logs mysql > mysql-logs-$(date +%Y%m%d).log
```

### Backup and Recovery

#### Database Backup
```bash
# Full database backup
docker-compose exec mysql mysqldump -u sakila_user -p sakila > sakila-backup-$(date +%Y%m%d).sql

# Schema-only backup
docker-compose exec mysql mysqldump -u sakila_user -p --no-data sakila > sakila-schema-$(date +%Y%m%d).sql

# Compressed backup
docker-compose exec mysql mysqldump -u sakila_user -p sakila | gzip > sakila-backup-$(date +%Y%m%d).sql.gz
```

#### Volume Backup
```bash
# Backup MySQL data volume
docker run --rm -v mysql_data:/data -v $(pwd):/backup alpine tar czf /backup/mysql-data-$(date +%Y%m%d).tar.gz -C /data .

# Restore from backup
docker run --rm -v mysql_data:/data -v $(pwd):/backup alpine tar xzf /backup/mysql-data-backup.tar.gz -C /data
```

### Environment Cleanup

#### Complete Cleanup
```bash
# Stop and remove all containers
docker-compose down

# Remove volumes (data will be lost)
docker-compose down -v

# Remove images
docker-compose down --rmi all

# Clean Docker system
docker system prune -a -f
```

#### Selective Cleanup
```bash
# Remove only containers
docker-compose rm -f

# Restart fresh containers
docker-compose up -d

# Clean generated files
rm -rf generated-entities/
docker-compose exec php rm -rf generated/
```

---

**This Docker environment provides a complete, production-ready testing setup for the ReverseEngineeringBundle with comprehensive documentation and troubleshooting guides.**