# Quick Start Guide - ReverseEngineeringBundle

Get up and running with the ReverseEngineeringBundle in under 10 minutes! This guide provides a streamlined path from installation to generating your first entities.

## ⚡ 5-Minute Setup

### Step 1: Install the Bundle (1 minute)

```bash
# Install via Composer
composer require eprofos/reverse-engineering-bundle

# Verify installation
composer show eprofos/reverse-engineering-bundle
```

### Step 2: Configure Database Connection (2 minutes)

Create the configuration file:

```bash
# Create configuration file
cat > config/packages/reverse_engineering.yaml << 'EOF'
reverse_engineering:
    database:
        driver: pdo_mysql
        host: localhost
        port: 3306
        dbname: your_database_name
        user: your_username
        password: your_password
        charset: utf8mb4
    
    generation:
        namespace: App\Entity
        output_dir: src/Entity
        generate_repository: true
        use_annotations: false
EOF
```

**Alternative: Use Environment Variables**

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
    generation:
        namespace: App\Entity
        output_dir: src/Entity
```

```bash
# Add to .env file
DB_DRIVER=pdo_mysql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=your_database
DB_USER=your_username
DB_PASSWORD=your_password
```

### Step 3: Test Connection (1 minute)

```bash
# Test database connection
php bin/console reverse:generate --dry-run

# Expected output:
# [OK] Database connection successful
# [INFO] Found X tables in database
# [INFO] Preview mode - no files will be created
```

### Step 4: Generate Your First Entities (1 minute)

```bash
# Generate all entities
php bin/console reverse:generate

# Or generate specific tables
php bin/console reverse:generate --tables=users --tables=products
```

## 🎯 Common Use Cases

### Use Case 1: Legacy Database Migration

**Scenario**: You have an existing MySQL database and want to create Symfony entities.

```bash
# Step 1: Preview what will be generated
php bin/console reverse:generate --dry-run --verbose

# Step 2: Generate entities with backup
cp -r src/Entity src/Entity.backup.$(date +%Y%m%d) 2>/dev/null || true
php bin/console reverse:generate --force

# Step 3: Validate generated entities
php bin/console doctrine:schema:validate
```

### Use Case 2: Modular Entity Organization

**Scenario**: Organize entities by business domain.

```bash
# User management module
php bin/console reverse:generate \
    --tables=users --tables=user_profiles --tables=user_roles \
    --namespace="App\Entity\User" \
    --output-dir="src/Entity/User"

# Product catalog module
php bin/console reverse:generate \
    --tables=products --tables=categories --tables=brands \
    --namespace="App\Entity\Product" \
    --output-dir="src/Entity/Product"

# Order management module
php bin/console reverse:generate \
    --tables=orders --tables=order_items --tables=payments \
    --namespace="App\Entity\Order" \
    --output-dir="src/Entity/Order"
```

### Use Case 3: Development with Docker

**Scenario**: Test with the included Sakila database environment.

```bash
# Start Docker environment
docker-compose up -d

# Wait for MySQL to be ready
docker-compose logs -f mysql

# Generate entities from Sakila database
./docker-test.sh generate-and-copy

# Run integration tests
./docker-test.sh test-sakila
```

## 🔧 Configuration Examples

### MySQL Configuration

```yaml
reverse_engineering:
    database:
        driver: pdo_mysql
        host: localhost
        port: 3306
        dbname: myapp
        user: myuser
        password: mypassword
        charset: utf8mb4
        options:
            1002: "SET SESSION sql_mode=''"  # Disable strict mode
    generation:
        namespace: App\Entity
        output_dir: src/Entity
        exclude_tables:
            - doctrine_migration_versions
            - messenger_messages
            - cache_items
```

### PostgreSQL Configuration

```yaml
reverse_engineering:
    database:
        driver: pdo_pgsql
        host: localhost
        port: 5432
        dbname: myapp
        user: postgres
        password: password
        charset: utf8
    generation:
        namespace: App\Entity
        output_dir: src/Entity
```

### SQLite Configuration

```yaml
reverse_engineering:
    database:
        driver: pdo_sqlite
        path: '%kernel.project_dir%/var/data.db'
    generation:
        namespace: App\Entity
        output_dir: src/Entity
```

## 📋 Command Reference

### Basic Commands

```bash
# Generate all entities
php bin/console reverse:generate

# Preview without creating files
php bin/console reverse:generate --dry-run

# Generate specific tables
php bin/console reverse:generate --tables=users --tables=products

# Exclude system tables
php bin/console reverse:generate --exclude=migrations --exclude=cache

# Force overwrite existing files
php bin/console reverse:generate --force

# Verbose output for debugging
php bin/console reverse:generate --verbose
```

### Advanced Commands

```bash
# Custom namespace and directory
php bin/console reverse:generate \
    --namespace="MyApp\Entity" \
    --output-dir="src/MyApp/Entity"

# Generate without repositories
php bin/console reverse:generate --no-repository

# Use annotations instead of PHP 8 attributes
php bin/console reverse:generate --use-annotations

# Combine multiple options
php bin/console reverse:generate \
    --tables=users --tables=orders \
    --namespace="App\Entity\Core" \
    --output-dir="src/Entity/Core" \
    --force \
    --verbose
```

## 🚨 Troubleshooting

### Connection Issues

**Problem**: `SQLSTATE[HY000] [2002] Connection refused`

**Solution**:
```bash
# Check if database server is running
sudo systemctl status mysql  # or postgresql

# Test connection manually
mysql -h localhost -u username -p database_name

# Check configuration
php bin/console debug:config reverse_engineering
```

**Problem**: `SQLSTATE[28000] [1045] Access denied`

**Solution**:
```bash
# Verify credentials
mysql -h localhost -u username -p

# Check user permissions
mysql -u root -p -e "SHOW GRANTS FOR 'username'@'localhost';"

# Grant necessary permissions
mysql -u root -p -e "GRANT SELECT ON database_name.* TO 'username'@'localhost';"
```

### Generation Issues

**Problem**: `No tables found in database`

**Solution**:
```bash
# List available tables
mysql -u username -p database_name -e "SHOW TABLES;"

# Check table permissions
mysql -u username -p -e "SELECT * FROM information_schema.tables WHERE table_schema = 'database_name';"
```

**Problem**: `Permission denied when writing files`

**Solution**:
```bash
# Check directory permissions
ls -la src/

# Create directory with proper permissions
mkdir -p src/Entity
chmod 755 src/Entity

# Fix ownership
sudo chown -R $USER:$USER src/Entity
```

### Validation Issues

**Problem**: Generated entities fail Doctrine validation

**Solution**:
```bash
# Validate schema
php bin/console doctrine:schema:validate

# Check mapping info
php bin/console doctrine:mapping:info

# Clear cache
php bin/console cache:clear
```

## 📊 Sample Database Schema

Here's a sample schema to test the bundle:

```sql
-- Create test database
CREATE DATABASE quickstart_test;
USE quickstart_test;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    parent_id INT,
    FOREIGN KEY (parent_id) REFERENCES categories(id)
);

-- Products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category_id INT NOT NULL,
    created_by INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Insert sample data
INSERT INTO users (email, first_name, last_name) VALUES
('john@example.com', 'John', 'Doe'),
('jane@example.com', 'Jane', 'Smith');

INSERT INTO categories (name, description) VALUES
('Electronics', 'Electronic devices and gadgets'),
('Books', 'Books and publications');

INSERT INTO products (name, description, price, category_id, created_by) VALUES
('Laptop', 'High-performance laptop', 999.99, 1, 1),
('Programming Book', 'Learn programming fundamentals', 49.99, 2, 2);
```

### Generate Entities from Sample Schema

```bash
# Configure for test database
cat > config/packages/reverse_engineering.yaml << 'EOF'
reverse_engineering:
    database:
        driver: pdo_mysql
        host: localhost
        port: 3306
        dbname: quickstart_test
        user: your_username
        password: your_password
    generation:
        namespace: App\Entity
        output_dir: src/Entity
EOF

# Generate entities
php bin/console reverse:generate

# Validate results
php bin/console doctrine:schema:validate
```

## 🎉 Success Checklist

After following this guide, you should have:

- ✅ **Bundle installed** and configured
- ✅ **Database connection** working
- ✅ **Entities generated** in `src/Entity/`
- ✅ **Repositories created** (if enabled)
- ✅ **Doctrine validation** passing
- ✅ **Relationships detected** from foreign keys

### Verify Your Setup

```bash
# Check generated files
ls -la src/Entity/

# Validate entity mapping
php bin/console doctrine:mapping:info

# Validate schema
php bin/console doctrine:schema:validate

# Check for syntax errors
find src/Entity -name "*.php" -exec php -l {} \;
```

## 🚀 Next Steps

Now that you have entities generated, consider these next steps:

### 1. Create Migrations
```bash
# Generate migration for your entities
php bin/console doctrine:migrations:diff

# Review the migration
cat migrations/VersionXXXXXXXXXXXX.php

# Execute migration
php bin/console doctrine:migrations:migrate
```

### 2. Add Business Logic
```php
// src/Entity/User.php
class User
{
    // ... generated properties and methods
    
    // Add custom business methods
    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }
    
    public function isActive(): bool
    {
        return $this->isActive;
    }
}
```

### 3. Create Custom Repositories
```php
// src/Repository/UserRepository.php
class UserRepository extends ServiceEntityRepository
{
    // ... generated code
    
    // Add custom query methods
    public function findActiveUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
```

### 4. Explore Advanced Features

- **Docker Environment**: Try the Sakila database setup
- **Custom Templates**: Create custom entity templates
- **Advanced Configuration**: Explore type mapping and exclusions
- **Integration**: Use with API Platform, Symfony Forms, etc.

## 📚 Additional Resources

- **[Complete Documentation](README.md)**: Full feature documentation
- **[API Reference](docs/API.md)**: Detailed API documentation
- **[Docker Setup](DOCKER_SETUP.md)**: Docker environment guide
- **[Troubleshooting](docs/TROUBLESHOOTING.md)**: Common issues and solutions
- **[Advanced Usage](docs/ADVANCED_USAGE.md)**: Advanced scenarios and customization

---

**🎉 Congratulations! You've successfully set up the ReverseEngineeringBundle and generated your first entities. You're now ready to modernize your legacy database with Symfony and Doctrine!**