# Docker Environment for ReverseEngineeringBundle

This directory contains the Docker configuration for testing the bundle with the Sakila database, providing a complete development and testing environment for the ReverseEngineeringBundle.

## 🐳 Docker Services

### MySQL 8.0 with Sakila Database
- **Image**: `mysql:8.0`
- **Port**: `3306`
- **Database**: `sakila`
- **User**: `sakila_user`
- **Password**: `sakila_password`
- **Features**: Pre-loaded with complete Sakila sample database

### PHP 8.2 CLI Environment
- **Image**: PHP 8.2 with required extensions
- **Extensions**: PDO, PDO_MySQL, MySQLi, Zip, GD, MBString, XML, BCMath
- **Composer**: Included and ready to use
- **Xdebug**: Available for debugging

### phpMyAdmin (Optional)
- **Port**: `8080`
- **URL**: http://localhost:8080
- **Purpose**: Database administration and inspection

## 🚀 Quick Start

### 1. Start the Environment

```bash
# From project root
docker-compose up -d

# Or use the utility script
./docker-test.sh start
```

### 2. Verify MySQL is Ready

```bash
# Wait for container to be healthy
docker-compose ps

# Check MySQL logs
docker-compose logs mysql

# Or use utility script
./docker-test.sh status
```

### 3. Generate and Retrieve Sakila Entities

```bash
# Automatic generation and copy (recommended)
./docker-test.sh generate-and-copy

# Entities will be available in ./generated-entities/
```

### 4. Run Tests with Sakila

```bash
# Sakila integration tests only
docker-compose exec php vendor/bin/phpunit tests/Integration/SakilaIntegrationTest.php

# Or use utility script
./docker-test.sh test-sakila

# All tests
./docker-test.sh test-all
```

## 📊 Sakila Database

The Sakila database is a MySQL sample database that simulates a DVD rental store. It provides an excellent testing ground for reverse engineering with complex relationships and diverse data types.

### Main Tables
- **`actor`**: Movie actors
- **`film`**: Movie catalog
- **`customer`**: Store customers
- **`rental`**: Movie rentals
- **`payment`**: Payments
- **`inventory`**: Movie inventory
- **`store`**: Stores
- **`staff`**: Staff members
- **`address`**: Addresses
- **`city`**: Cities
- **`country`**: Countries
- **`category`**: Movie categories
- **`language`**: Languages

### Complex Relationships
- **Many-to-One**: `customer` → `address`, `film` → `language`
- **One-to-Many**: `customer` → `rental`, `film` → `inventory`
- **Many-to-Many**: `film` ↔ `actor` (via `film_actor`), `film` ↔ `category` (via `film_category`)

### Diverse Data Types
- **Integers**: `TINYINT`, `SMALLINT`, `MEDIUMINT`, `INT`
- **Decimals**: `DECIMAL(4,2)`, `DECIMAL(5,2)`
- **Text**: `VARCHAR`, `CHAR`, `TEXT`
- **Dates**: `DATE`, `DATETIME`, `TIMESTAMP`, `YEAR`
- **Enumerations**: `ENUM('G','PG','PG-13','R','NC-17')`
- **Sets**: `SET('Trailers','Commentaries',...)`
- **Booleans**: `BOOLEAN`
- **Binary**: `BLOB`

### Database Statistics
- **Tables**: 16 tables
- **Relationships**: 15+ foreign key constraints
- **Records**: 47,000+ sample records
- **Complexity**: Enterprise-level schema complexity

## 🧪 Available Tests

### Sakila Integration Tests
```bash
# Complete entity generation test
docker-compose exec php vendor/bin/phpunit tests/Integration/SakilaIntegrationTest.php::testCompleteEntityGeneration

# Complex relationship test
docker-compose exec php vendor/bin/phpunit tests/Integration/SakilaIntegrationTest.php::testComplexRelations

# Data type mapping test
docker-compose exec php vendor/bin/phpunit tests/Integration/SakilaIntegrationTest.php::testDataTypeMapping

# Performance test
docker-compose exec php vendor/bin/phpunit tests/Integration/SakilaIntegrationTest.php::testPerformanceOnFullDatabase
```

### Manual Entity Generation

#### Container-only Generation
```bash
# Generate all Sakila entities in container
docker-compose exec php php scripts/generate-entities.php \
    --namespace="Sakila\\Entity" \
    --output-dir="generated/sakila"

# Or use utility script
./docker-test.sh generate
```

#### Generation with Automatic Copy to Host
```bash
# Generation and automatic copy (recommended)
./docker-test.sh generate-and-copy

# With custom destination directory
./docker-test.sh generate-and-copy ./my-entities

# With custom directory and namespace
./docker-test.sh generate-and-copy ./src/Entity "MyApp\\Entity"
```

#### Advantages of `generate-and-copy` Command
- ✅ Automatic generation in Docker container
- ✅ Automatic file copy to local host
- ✅ PHP syntax validation of copied files
- ✅ Automatic cleanup of temporary files
- ✅ Detailed statistics (time, size, file count)
- ✅ Automatic permission correction
- ✅ Complete operation summary

## 🔄 `generate-and-copy` Command - Complete Guide

### Description
The `generate-and-copy` command completely automates the process of generating entities from the Sakila database and retrieving them on the local host. This command combines generation in the Docker container with automatic copying of generated files.

### Syntax
```bash
./docker-test.sh generate-and-copy [destination_directory] [namespace]
```

### Parameters
- **`destination_directory`** (optional): Local directory where to copy generated entities
  - Default: `./generated-entities`
  - Example: `./src/Entity`, `./my-entities`

- **`namespace`** (optional): PHP namespace for generated entities
  - Default: `Sakila\\Entity`
  - Example: `MyApp\\Entity`, `App\\Entity\\Sakila`

### Usage Examples

#### Basic Usage
```bash
# Generation with default parameters
./docker-test.sh generate-and-copy

# Result: Entities in ./generated-entities/ with namespace Sakila\Entity
```

#### Custom Directory
```bash
# Specify destination directory
./docker-test.sh generate-and-copy ./my-entities

# Result: Entities in ./my-entities/ with namespace Sakila\Entity
```

#### Custom Directory and Namespace
```bash
# Specify directory and namespace
./docker-test.sh generate-and-copy ./src/Entity "MyApp\\Entity"

# Result: Entities in ./src/Entity/ with namespace MyApp\Entity
```

### Detailed Process

1. **Environment Verification**
   - Check Docker and Docker Compose installation
   - Verify MySQL environment is started

2. **Preparation**
   - Create local destination directory
   - Clean generation directory in container

3. **Entity Generation**
   - Execute generation script in PHP container
   - Measure execution time
   - Validate generation

4. **File Copy**
   - Use `docker cp` to copy files
   - Preserve directory structure
   - Automatic permission correction

5. **Validation and Cleanup**
   - PHP syntax validation (if PHP available on host)
   - Cleanup temporary files in container
   - Generate final report

### Generated File Structure

```
generated-entities/          # Destination directory
├── Actor.php               # Actor entity
├── ActorRepository.php     # Actor repository
├── Film.php                # Film entity
├── FilmRepository.php      # Film repository
├── Customer.php            # Customer entity
├── CustomerRepository.php  # Customer repository
└── ...                     # Other entities and repositories
```

### Displayed Information

The command displays a detailed report including:

- **Generation time**: Duration of entity generation
- **File count**: Generated entities and repositories
- **Total size**: Disk space used by files
- **Syntax validation**: PHP validation result
- **File list**: Detail of each generated file with size

### Example Output

```bash
$ ./docker-test.sh generate-and-copy ./my-entities "MyApp\\Entity"

[INFO] Automatic entity generation and copy...
[INFO] Local destination directory: ./my-entities
[INFO] Namespace: MyApp\Entity
[INFO] Local directory created: ./my-entities
[INFO] Cleaning generation directory in container...
[INFO] Generating entities in Docker container...
[SUCCESS] Entities generated successfully in 12s
[INFO] Retrieving list of generated files...
[INFO] Files to copy: 32
[INFO] Copying files from container to local host...
[SUCCESS] Files copied successfully to ./my-entities
[INFO] Correcting file permissions...
[INFO] Validating PHP syntax of copied files...
[INFO] Cleaning temporary files in container...

[SUCCESS] 🎉 Generation and copy completed successfully!

[INFO] 📊 Operation Summary:
[INFO]    - Generation time: 12s
[INFO]    - Files generated: 32
[INFO]    - Files copied: 32
[INFO]    - Total size: 156K
[INFO]    - Destination directory: ./my-entities
[INFO]    - Namespace used: MyApp\Entity
[SUCCESS]    - Syntax validation: ✅ All files are valid

[INFO] 📁 Generated files:
[INFO]    - Actor.php (2.1K)
[INFO]    - ActorRepository.php (1.2K)
[INFO]    - Film.php (4.8K)
[INFO]    - FilmRepository.php (1.2K)
[INFO]    - ...

[INFO] 💡 To use these entities in your Symfony project:
[INFO]    1. Copy files to src/Entity/ of your project
[INFO]    2. Adjust namespace according to your configuration
[INFO]    3. Run 'php bin/console doctrine:schema:validate'

[SUCCESS] Operation completed successfully!
```

### Integration in a Symfony Project

After generation, to use entities in your project:

1. **Copy files**
   ```bash
   cp ./generated-entities/*.php /path/to/your/symfony/project/src/Entity/
   ```

2. **Adjust namespace** (if necessary)
   ```php
   // Replace in all files
   namespace Sakila\Entity;
   // With
   namespace App\Entity;
   ```

3. **Validate with Doctrine**
   ```bash
   cd /path/to/your/symfony/project
   php bin/console doctrine:schema:validate
   ```

4. **Generate migrations** (if necessary)
   ```bash
   php bin/console doctrine:migrations:diff
   ```

## 🔧 Configuration

### Environment Variables
The following variables can be modified in [`docker-compose.yml`](../docker-compose.yml):

```yaml
environment:
  MYSQL_ROOT_PASSWORD: root_password
  MYSQL_DATABASE: sakila
  MYSQL_USER: sakila_user
  MYSQL_PASSWORD: sakila_password
```

### MySQL Configuration
MySQL configuration is in [`mysql/conf/my.cnf`](mysql/conf/my.cnf):
- UTF8MB4 charset
- Optimized InnoDB
- Query cache enabled
- Slow query logging

### PHP Configuration
PHP configuration is in [`php/conf/php.ini`](php/conf/php.ini):
- Memory: 512M
- Execution time: 300s
- MySQL extensions enabled
- Optimized OPcache

## 🐛 Troubleshooting

### MySQL Won't Start
```bash
# Check logs
docker-compose logs mysql

# Restart service
docker-compose restart mysql

# Rebuild image
docker-compose build --no-cache mysql
```

### Connection Refused
```bash
# Check port 3306 is free
netstat -tlnp | grep 3306

# Wait for MySQL to be ready
docker-compose exec mysql mysqladmin ping -h localhost -u root -p
```

### Empty Database
```bash
# Check initialization
docker-compose logs mysql | grep -i sakila

# Reset data
docker-compose down -v
docker-compose up -d
```

### Tests Failing
```bash
# Check connection from PHP
docker-compose exec php php -r "
try {
    \$pdo = new PDO('mysql:host=mysql;dbname=sakila', 'sakila_user', 'sakila_password');
    echo 'Connection OK\n';
    \$stmt = \$pdo->query('SELECT COUNT(*) FROM actor');
    echo 'Actors: ' . \$stmt->fetchColumn() . '\n';
} catch (Exception \$e) {
    echo 'Error: ' . \$e->getMessage() . '\n';
}
"
```

## 📈 Performance

### Expected Metrics
- **Generation time**: < 30 seconds for all tables
- **Memory used**: < 128 MB
- **Tables processed**: 16 main tables
- **Entities generated**: 16 entities with relationships

### Optimizations
- Indexes on foreign keys
- MySQL query cache enabled
- PHP OPcache configured
- Persistent connections

## 🔒 Security

### Network Access
- MySQL accessible only from localhost:3306
- phpMyAdmin accessible from localhost:8080
- No external exposure by default

### Authentication
- Dedicated MySQL user (non-root)
- Configurable passwords
- Isolated database

## 📝 Maintenance

### Backup
```bash
# Export Sakila database
docker-compose exec mysql mysqldump -u sakila_user -p sakila > sakila_backup.sql
```

### Cleanup
```bash
# Stop and remove containers
docker-compose down

# Remove volumes (data lost)
docker-compose down -v

# Remove images
docker-compose down --rmi all
```

### Update
```bash
# Update images
docker-compose pull

# Rebuild services
docker-compose build --no-cache

# Restart
docker-compose up -d
```

## 🎯 Use Cases

### Development
- **Entity Generation**: Test reverse engineering with complex schema
- **Relationship Testing**: Validate complex relationship detection
- **Performance Testing**: Benchmark with realistic data volume

### Testing
- **Integration Tests**: Complete workflow validation
- **Regression Tests**: Ensure consistency across versions
- **Performance Tests**: Validate performance benchmarks

### Learning
- **Schema Analysis**: Study complex database design
- **Doctrine Learning**: Understand ORM mapping
- **Best Practices**: Learn entity generation patterns

## 📚 Additional Resources

### Sakila Database
- [Official Sakila Documentation](https://dev.mysql.com/doc/sakila/en/)
- [Sakila Schema Diagram](https://dev.mysql.com/doc/sakila/en/sakila-structure.html)
- [Sample Queries](https://dev.mysql.com/doc/sakila/en/sakila-usage.html)

### Docker
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [MySQL Docker Image](https://hub.docker.com/_/mysql)
- [PHP Docker Image](https://hub.docker.com/_/php)

### Development Tools
- [phpMyAdmin Documentation](https://docs.phpmyadmin.net/)
- [Xdebug Configuration](https://xdebug.org/docs/install)
- [Composer in Docker](https://hub.docker.com/_/composer)

---

**This Docker environment provides a complete, isolated, and reproducible testing environment for the ReverseEngineeringBundle with the industry-standard Sakila database.**