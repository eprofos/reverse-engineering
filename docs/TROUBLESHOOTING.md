# Troubleshooting Guide - ReverseEngineeringBundle

This guide helps you resolve common issues encountered when using the ReverseEngineeringBundle for Symfony 7+ and PHP 8+ database reverse engineering.

## 🚨 Database Connection Issues

### Error: "Connection refused" or "Access denied"

**Symptoms:**
```
DatabaseConnectionException: SQLSTATE[HY000] [2002] Connection refused
DatabaseConnectionException: SQLSTATE[28000] [1045] Access denied for user
```

**Possible Causes:**
- Incorrect connection parameters
- Database not started
- Insufficient user permissions
- Firewall blocking connection

**Solutions:**

1. **Verify connection parameters:**
```yaml
# config/packages/reverse_engineering.yaml
reverse_engineering:
    database:
        driver: pdo_mysql
        host: localhost    # Check host
        port: 3306        # Check port
        dbname: myapp     # Check database name
        user: username    # Check username
        password: password # Check password
```

2. **Test connection manually:**
```bash
# MySQL
mysql -h localhost -P 3306 -u username -p myapp

# PostgreSQL
psql -h localhost -p 5432 -U username -d myapp

# Check service is running
sudo systemctl status mysql
sudo systemctl status postgresql
```

3. **Verify user permissions:**
```sql
-- MySQL
SHOW GRANTS FOR 'username'@'localhost';
GRANT SELECT ON myapp.* TO 'username'@'localhost';

-- PostgreSQL
\du username
GRANT USAGE ON SCHEMA public TO username;
GRANT SELECT ON ALL TABLES IN SCHEMA public TO username;
```

### Error: "Driver not found"

**Symptoms:**
```
DatabaseConnectionException: Driver pdo_mysql not found
```

**Solutions:**

1. **Install missing PHP extension:**
```bash
# Ubuntu/Debian
sudo apt-get install php-mysql php-pgsql php-sqlite3

# CentOS/RHEL
sudo yum install php-mysql php-pgsql php-sqlite

# Check installed extensions
php -m | grep -E "(mysql|pgsql|sqlite)"
```

2. **Verify PHP configuration:**
```bash
php -i | grep -E "(mysql|pgsql|sqlite)"
```

### Error: "Unknown database" or "Database does not exist"

**Solutions:**

1. **Create the database:**
```sql
-- MySQL
CREATE DATABASE myapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- PostgreSQL
CREATE DATABASE myapp WITH ENCODING 'UTF8';
```

2. **Verify database existence:**
```sql
-- MySQL
SHOW DATABASES;

-- PostgreSQL
\l
```

---

## 🔍 Metadata Extraction Issues

### Error: "Table not found" or "Permission denied"

**Symptoms:**
```
MetadataExtractionException: Table 'users' doesn't exist
MetadataExtractionException: Access denied for table 'users'
```

**Solutions:**

1. **Verify table existence:**
```bash
php bin/console reverse:generate --dry-run --verbose
```

2. **List available tables:**
```sql
-- MySQL
SHOW TABLES;

-- PostgreSQL
\dt

-- SQLite
.tables
```

3. **Check permissions:**
```sql
-- MySQL
SHOW GRANTS FOR CURRENT_USER();

-- PostgreSQL
SELECT * FROM information_schema.table_privileges WHERE grantee = CURRENT_USER;
```

### Error: "Unsupported column type"

**Symptoms:**
```
MetadataExtractionException: Unsupported column type 'GEOMETRY'
```

**Solutions:**

1. **Exclude tables with unsupported types:**
```bash
php bin/console reverse:generate --exclude=spatial_table
```

2. **Custom mapping (future development):**
```php
// Custom configuration for special types
$customMapping = [
    'GEOMETRY' => 'string',
    'POINT' => 'string',
    'POLYGON' => 'string'
];
```

### Error: "Foreign key constraint error"

**Solutions:**

1. **Verify foreign key integrity:**
```sql
-- MySQL
SELECT * FROM information_schema.KEY_COLUMN_USAGE 
WHERE REFERENCED_TABLE_NAME IS NOT NULL;

-- PostgreSQL
SELECT * FROM information_schema.referential_constraints;
```

2. **Generate tables in dependency order:**
```bash
# Generate parent tables first
php bin/console reverse:generate --tables=categories
php bin/console reverse:generate --tables=products
```

---

## ⚙️ Entity Generation Issues

### Error: "Template not found" or "Twig error"

**Symptoms:**
```
EntityGenerationException: Template "entity.php.twig" not found
EntityGenerationException: Syntax error in template
```

**Solutions:**

1. **Verify template existence:**
```bash
ls -la src/Resources/templates/
```

2. **Reinstall the bundle:**
```bash
composer reinstall eprofos/reverse-engineering-bundle
```

3. **Check Twig configuration:**
```yaml
# config/packages/twig.yaml
twig:
    paths:
        '%kernel.project_dir%/src/Resources/templates': 'ReverseEngineering'
```

### Error: "Invalid namespace" or "Class name conflict"

**Symptoms:**
```
EntityGenerationException: Invalid namespace 'App\Entity\123Invalid'
EntityGenerationException: Class 'User' already exists
```

**Solutions:**

1. **Use a valid namespace:**
```bash
php bin/console reverse:generate --namespace="App\\Entity\\Generated"
```

2. **Force overwrite or use different directory:**
```bash
php bin/console reverse:generate --force
# or
php bin/console reverse:generate --output-dir="src/Entity/New"
```

### Error: "Memory limit exceeded"

**Symptoms:**
```
Fatal error: Allowed memory size exhausted
```

**Solutions:**

1. **Increase memory limit:**
```bash
php -d memory_limit=512M bin/console reverse:generate
```

2. **Process tables in small batches:**
```bash
# Process 10 tables at a time
php bin/console reverse:generate --tables=table1 --tables=table2 --tables=table3
```

3. **Optimize PHP configuration:**
```ini
; php.ini
memory_limit = 512M
max_execution_time = 300
```

---

## 📁 File Writing Issues

### Error: "Permission denied" or "Directory not writable"

**Symptoms:**
```
FileWriteException: Permission denied: /path/to/src/Entity/User.php
FileWriteException: Directory '/path/to/src/Entity' is not writable
```

**Solutions:**

1. **Check permissions:**
```bash
ls -la src/
chmod 755 src/Entity/
chown -R www-data:www-data src/Entity/
```

2. **Create directory manually:**
```bash
mkdir -p src/Entity/Generated
chmod 755 src/Entity/Generated
```

3. **Use temporary directory:**
```bash
php bin/console reverse:generate --output-dir="/tmp/entities"
```

### Error: "File already exists"

**Solutions:**

1. **Use force option:**
```bash
php bin/console reverse:generate --force
```

2. **Backup existing files:**
```bash
cp -r src/Entity src/Entity.backup.$(date +%Y%m%d)
php bin/console reverse:generate --force
```

### Error: "Invalid filename" or "Path too long"

**Solutions:**

1. **Use shorter table names:**
```bash
# Rename table if possible
ALTER TABLE very_long_table_name_that_causes_issues RENAME TO short_name;
```

2. **Use shorter output directory:**
```bash
php bin/console reverse:generate --output-dir="src/E"
```

---

## 🐛 Performance Issues

### Very slow generation

**Symptoms:**
- Generation taking several minutes
- Excessive memory usage
- Connection timeouts

**Solutions:**

1. **Optimize database connection:**
```yaml
reverse_engineering:
    database:
        options:
            # MySQL
            1002: "SET SESSION sql_mode=''"  # PDO::MYSQL_ATTR_INIT_COMMAND
            # PostgreSQL
            'application_name': 'ReverseEngineering'
```

2. **Process in batches:**
```bash
# Batch processing script
#!/bin/bash
TABLES=(users products orders categories)
for table in "${TABLES[@]}"; do
    echo "Processing $table..."
    php bin/console reverse:generate --tables=$table
done
```

3. **Use indexes on system tables:**
```sql
-- MySQL - Optimize information_schema (if possible)
-- PostgreSQL - Analyze statistics
ANALYZE;
```

### Excessive memory usage

**Solutions:**

1. **Limit number of processed tables:**
```bash
php bin/console reverse:generate --tables=table1 --tables=table2
```

2. **Optimize PHP configuration:**
```ini
; php.ini
memory_limit = 256M
opcache.enable = 1
opcache.memory_consumption = 128
```

---

## 🔧 Configuration Issues

### Configuration not taken into account

**Solutions:**

1. **Clear cache:**
```bash
php bin/console cache:clear
php bin/console cache:clear --env=prod
```

2. **Check YAML syntax:**
```bash
php bin/console lint:yaml config/packages/reverse_engineering.yaml
```

3. **Check bundle loading order:**
```php
// config/bundles.php
return [
    // ... other bundles
    App\Bundle\ReverseEngineeringBundle::class => ['all' => true],
];
```

### Environment variables not resolved

**Solutions:**

1. **Check .env file:**
```bash
# .env
DATABASE_URL=mysql://user:pass@localhost:3306/dbname
```

2. **Use variable resolution:**
```yaml
reverse_engineering:
    database:
        driver: '%env(DB_DRIVER)%'
        host: '%env(DB_HOST)%'
        dbname: '%env(DB_NAME)%'
```

---

## 🧪 Testing Issues

### Tests failing

**Solutions:**

1. **Check test environment:**
```bash
# Check test configuration
cat phpunit.xml
```

2. **Clear test cache:**
```bash
rm -rf .phpunit.cache
vendor/bin/phpunit --cache-clear
```

3. **Check test dependencies:**
```bash
composer install --dev
```

### Test database not accessible

**Solutions:**

1. **Use SQLite in-memory:**
```xml
<!-- phpunit.xml -->
<php>
    <env name="DATABASE_URL" value="sqlite:///:memory:" />
</php>
```

2. **Create dedicated test database:**
```sql
CREATE DATABASE myapp_test;
GRANT ALL ON myapp_test.* TO 'test_user'@'localhost';
```

---

## 📊 Diagnostic Tools

### Automatic diagnostic script

```bash
#!/bin/bash
# scripts/diagnose.sh

echo "=== REVERSEENGINEERINGBUNDLE DIAGNOSTICS ==="

echo "1. PHP verification..."
php --version
php -m | grep -E "(pdo|mysql|pgsql|sqlite)"

echo "2. Composer verification..."
composer --version
composer show eprofos/reverse-engineering-bundle

echo "3. Configuration verification..."
if [ -f "config/packages/reverse_engineering.yaml" ]; then
    echo "✓ Configuration file present"
else
    echo "✗ Configuration file missing"
fi

echo "4. Connection test..."
php bin/console reverse:generate --dry-run --tables=non_existent 2>&1 | head -5

echo "5. Permission verification..."
ls -la src/Entity/ 2>/dev/null || echo "src/Entity directory not found"

echo "6. Cache verification..."
ls -la var/cache/ | head -3

echo "=== END DIAGNOSTICS ==="
```

### Useful debug commands

```bash
# Detailed configuration information
php bin/console debug:config reverse_engineering

# Available services
php bin/console debug:container reverse

# Route verification (if applicable)
php bin/console debug:router | grep reverse

# Real-time logs
tail -f var/log/dev.log | grep -i reverse

# Database connection test
php bin/console dbal:run-sql "SELECT 1"
```

---

## 📞 Getting Help

### Information to provide when reporting a bug

1. **Bundle version:**
```bash
composer show eprofos/reverse-engineering-bundle
```

2. **PHP configuration:**
```bash
php --version
php -m | grep -E "(pdo|mysql|pgsql|sqlite)"
```

3. **Symfony configuration:**
```bash
php bin/console --version
```

4. **Database configuration:**
```yaml
# Hide passwords!
reverse_engineering:
    database:
        driver: pdo_mysql
        host: localhost
        port: 3306
        # ...
```

5. **Complete error message:**
```bash
php bin/console reverse:generate --verbose 2>&1
```

6. **Application logs:**
```bash
tail -50 var/log/dev.log
```

### Support channels

- **GitHub Issues:** [Report a bug](https://github.com/eprofos/reverse-engineering-bundle/issues/new?template=bug_report.md)
- **Discussions:** [Ask a question](https://github.com/eprofos/reverse-engineering-bundle/discussions)
- **Documentation:** [Complete guide](https://github.com/eprofos/reverse-engineering-bundle#readme)

---

## 🔄 Emergency Recovery Procedure

### In case of corrupted generated entities

1. **Restore from backup:**
```bash
# If you made a backup
cp -r src/Entity.backup/* src/Entity/
```

2. **Clean regeneration:**
```bash
# Clean directory
rm -rf src/Entity/Generated/

# Regenerate with validation
php bin/console reverse:generate --dry-run
php bin/console reverse:generate --output-dir="src/Entity/Generated"
```

3. **Validate generated entities:**
```bash
# Check PHP syntax
find src/Entity -name "*.php" -exec php -l {} \;

# Validate with Doctrine
php bin/console doctrine:schema:validate
```

### In case of critical performance issues

1. **Degraded mode:**
```bash
# Process one table at a time
php bin/console reverse:generate --tables=critical_table --force
```

2. **Temporarily increase limits:**
```bash
php -d memory_limit=1G -d max_execution_time=600 bin/console reverse:generate
```

---

**This guide covers the most common issues. For specific cases, don't hesitate to consult the community or create an issue on GitHub.**