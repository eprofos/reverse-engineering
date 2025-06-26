# Usage Examples - ReverseEngineeringBundle

This comprehensive guide provides practical examples and real-world scenarios for using the ReverseEngineeringBundle effectively. Learn through step-by-step examples covering common use cases, advanced configurations, and enterprise-level implementations.

## 📋 Table of Contents

- [Common Use Cases](#common-use-cases)
- [Database Structure Examples](#database-structure-examples)
- [Advanced Customization](#advanced-customization)
- [CI/CD Integration](#cicd-integration)
- [Monitoring and Logging](#monitoring-and-logging)
- [Enterprise Scenarios](#enterprise-scenarios)
- [Troubleshooting Examples](#troubleshooting-examples)

## 🎯 Common Use Cases

### 1. Legacy Application Migration

#### Complete Database Analysis

```bash
# Step 1: Analyze the entire database structure
echo "🔍 Analyzing database structure..."
php bin/console reverse:generate --dry-run

# Step 2: Generate all entities with detailed output
echo "⚙️ Generating all entities..."
php bin/console reverse:generate --force --verbose

# Step 3: Validate generated entities
echo "✅ Validating generated entities..."
php bin/console doctrine:schema:validate
```

#### Phased Migration Approach

```bash
#!/bin/bash
# scripts/phased-migration.sh

echo "🏗️ Legacy Application Migration - Phased Approach"

# Phase 1: Core Business Entities (Users, Customers)
echo "📊 Phase 1: Core Business Entities"
php bin/console reverse:generate \
    --tables=users \
    --tables=customers \
    --tables=user_profiles \
    --namespace="App\Entity\Core" \
    --output-dir="src/Entity/Core" \
    --force

# Phase 2: Product Catalog
echo "🛍️ Phase 2: Product Catalog"
php bin/console reverse:generate \
    --tables=products \
    --tables=categories \
    --tables=brands \
    --tables=product_images \
    --namespace="App\Entity\Catalog" \
    --output-dir="src/Entity/Catalog" \
    --force

# Phase 3: Order Management
echo "📦 Phase 3: Order Management"
php bin/console reverse:generate \
    --tables=orders \
    --tables=order_items \
    --tables=shipping_addresses \
    --tables=payment_methods \
    --namespace="App\Entity\Order" \
    --output-dir="src/Entity/Order" \
    --force

# Phase 4: Financial Data
echo "💰 Phase 4: Financial Data"
php bin/console reverse:generate \
    --tables=invoices \
    --tables=payments \
    --tables=transactions \
    --tables=refunds \
    --namespace="App\Entity\Finance" \
    --output-dir="src/Entity/Finance" \
    --force

# Phase 5: Analytics and Reporting
echo "📈 Phase 5: Analytics and Reporting"
php bin/console reverse:generate \
    --tables=analytics_events \
    --tables=reports \
    --tables=metrics \
    --namespace="App\Entity\Analytics" \
    --output-dir="src/Entity/Analytics" \
    --force

echo "✅ Phased migration completed successfully!"
echo "📁 Check generated files in src/Entity/ subdirectories"
```

### 2. Selective Table Generation

#### Specific Table Selection

```bash
# Generate only user-related tables
php bin/console reverse:generate \
    --tables=users \
    --tables=user_profiles \
    --tables=user_permissions \
    --tables=user_sessions \
    --namespace="App\Entity\User" \
    --output-dir="src/Entity/User"

# Generate e-commerce core tables
php bin/console reverse:generate \
    --tables=products \
    --tables=categories \
    --tables=orders \
    --tables=order_items \
    --namespace="App\Entity\Shop" \
    --output-dir="src/Entity/Shop"
```

#### Exclusion-Based Generation

```bash
# Generate all tables except cache, logs, and temporary tables
php bin/console reverse:generate \
    --exclude=cache_items \
    --exclude=log_entries \
    --exclude=sessions \
    --exclude=temp_data \
    --exclude=migration_versions \
    --force

# Exclude system and audit tables
php bin/console reverse:generate \
    --exclude=doctrine_migration_versions \
    --exclude=audit_log \
    --exclude=system_config \
    --exclude=backup_metadata \
    --namespace="App\Entity\Business" \
    --output-dir="src/Entity/Business"
```

### 3. Module-Based Organization

#### User Management Module

```bash
# Generate user management entities
php bin/console reverse:generate \
    --tables=users \
    --tables=user_profiles \
    --tables=user_permissions \
    --tables=user_groups \
    --tables=user_roles \
    --namespace="App\Entity\UserManagement" \
    --output-dir="src/Entity/UserManagement" \
    --force

# Validate user module entities
php bin/console doctrine:mapping:info --filter="App\Entity\UserManagement"
```

#### E-commerce Module

```bash
# Generate e-commerce entities with detailed configuration
php bin/console reverse:generate \
    --tables=products \
    --tables=categories \
    --tables=brands \
    --tables=product_variants \
    --tables=product_images \
    --tables=product_reviews \
    --tables=shopping_carts \
    --tables=cart_items \
    --tables=orders \
    --tables=order_items \
    --tables=coupons \
    --tables=discounts \
    --namespace="App\Entity\Ecommerce" \
    --output-dir="src/Entity/Ecommerce" \
    --force

echo "🛍️ E-commerce module entities generated successfully!"
```

#### Content Management Module

```bash
# Generate CMS entities
php bin/console reverse:generate \
    --tables=posts \
    --tables=pages \
    --tables=comments \
    --tables=tags \
    --tables=post_tags \
    --tables=categories \
    --tables=media_files \
    --tables=menus \
    --tables=menu_items \
    --namespace="App\Entity\CMS" \
    --output-dir="src/Entity/CMS" \
    --force

echo "📝 CMS module entities generated successfully!"
```

### 4. Multi-Environment Setup

#### Development Environment

```bash
# Development database with full access
export DATABASE_URL="mysql://dev_user:dev_pass@localhost:3306/myapp_dev"

echo "🔧 Development Environment - Full Generation"
php bin/console reverse:generate \
    --namespace="App\Entity\Dev" \
    --output-dir="src/Entity/Dev" \
    --force \
    --verbose

# Generate test fixtures based on entities
php bin/console doctrine:fixtures:load --no-interaction
```

#### Staging Environment

```bash
# Staging database with limited access
export DATABASE_URL="mysql://staging_user:staging_pass@staging-server:3306/myapp_staging"

echo "🎭 Staging Environment - Validation Only"
php bin/console reverse:generate \
    --dry-run \
    --verbose

# Compare with development entities
diff -r src/Entity/Dev src/Entity/Staging || echo "⚠️ Differences detected between dev and staging"
```

#### Production Environment

```bash
# Production database with read-only access
export DATABASE_URL="mysql://readonly_user:readonly_pass@prod-server:3306/myapp_prod"

echo "🏭 Production Environment - Analysis Only"
php bin/console reverse:generate \
    --dry-run \
    --tables=users \
    --tables=orders \
    --tables=products

# Generate production-ready entities with optimizations
php bin/console reverse:generate \
    --namespace="App\Entity\Production" \
    --output-dir="src/Entity/Production" \
    --force
```

## 📊 Database Structure Examples

### E-commerce Platform

#### Complete E-commerce Schema

```sql
-- Categories with hierarchical structure
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    image_url VARCHAR(500),
    parent_id INT NULL,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    meta_title VARCHAR(255),
    meta_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_parent_id (parent_id),
    INDEX idx_slug (slug),
    INDEX idx_active_sort (is_active, sort_order)
);

-- Brands/Manufacturers
CREATE TABLE brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    logo_url VARCHAR(500),
    website_url VARCHAR(500),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_active (is_active)
);

-- Products with comprehensive attributes
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    short_description TEXT,
    description LONGTEXT,
    price DECIMAL(12,4) NOT NULL,
    compare_price DECIMAL(12,4) NULL,
    cost_price DECIMAL(12,4) NULL,
    weight DECIMAL(8,3) DEFAULT 0,
    dimensions VARCHAR(100),
    stock_quantity INT DEFAULT 0,
    low_stock_threshold INT DEFAULT 5,
    track_inventory BOOLEAN DEFAULT TRUE,
    allow_backorders BOOLEAN DEFAULT FALSE,
    category_id INT NOT NULL,
    brand_id INT NULL,
    status ENUM('draft', 'active', 'inactive', 'archived') DEFAULT 'draft',
    visibility ENUM('visible', 'hidden', 'search_only') DEFAULT 'visible',
    featured BOOLEAN DEFAULT FALSE,
    digital BOOLEAN DEFAULT FALSE,
    requires_shipping BOOLEAN DEFAULT TRUE,
    tax_class VARCHAR(50) DEFAULT 'standard',
    meta_title VARCHAR(255),
    meta_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL,
    INDEX idx_sku (sku),
    INDEX idx_slug (slug),
    INDEX idx_category (category_id),
    INDEX idx_brand (brand_id),
    INDEX idx_status_visibility (status, visibility),
    INDEX idx_featured (featured),
    INDEX idx_price (price),
    FULLTEXT idx_search (name, short_description, description)
);

-- Product variants (size, color, etc.)
CREATE TABLE product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    sku VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(12,4) NULL,
    compare_price DECIMAL(12,4) NULL,
    cost_price DECIMAL(12,4) NULL,
    weight DECIMAL(8,3) NULL,
    stock_quantity INT DEFAULT 0,
    position INT DEFAULT 0,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_sku (sku),
    INDEX idx_default (is_default)
);

-- Product images
CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    variant_id INT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255),
    alt_text VARCHAR(255),
    position INT DEFAULT 0,
    is_primary BOOLEAN DEFAULT FALSE,
    file_size INT,
    mime_type VARCHAR(100),
    width INT,
    height INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_variant_id (variant_id),
    INDEX idx_primary (is_primary)
);

-- Customers
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(20),
    birth_date DATE,
    gender ENUM('male', 'female', 'other') NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    accepts_marketing BOOLEAN DEFAULT FALSE,
    total_spent DECIMAL(12,4) DEFAULT 0,
    orders_count INT DEFAULT 0,
    last_order_at TIMESTAMP NULL,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_total_spent (total_spent),
    INDEX idx_last_order (last_order_at)
);

-- Customer addresses
CREATE TABLE customer_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    type ENUM('billing', 'shipping', 'both') DEFAULT 'both',
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    company VARCHAR(255),
    address_line_1 VARCHAR(255) NOT NULL,
    address_line_2 VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    state_province VARCHAR(100),
    postal_code VARCHAR(20),
    country_code CHAR(2) NOT NULL,
    phone VARCHAR(20),
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer_id (customer_id),
    INDEX idx_type (type),
    INDEX idx_default (is_default)
);

-- Orders
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT NULL,
    email VARCHAR(255) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded') DEFAULT 'pending',
    financial_status ENUM('pending', 'paid', 'partially_paid', 'refunded', 'partially_refunded') DEFAULT 'pending',
    fulfillment_status ENUM('unfulfilled', 'partial', 'fulfilled') DEFAULT 'unfulfilled',
    currency_code CHAR(3) DEFAULT 'USD',
    subtotal DECIMAL(12,4) NOT NULL,
    tax_amount DECIMAL(12,4) DEFAULT 0,
    shipping_amount DECIMAL(12,4) DEFAULT 0,
    discount_amount DECIMAL(12,4) DEFAULT 0,
    total_amount DECIMAL(12,4) NOT NULL,
    notes TEXT,
    billing_address JSON,
    shipping_address JSON,
    shipping_method VARCHAR(100),
    payment_method VARCHAR(100),
    payment_reference VARCHAR(255),
    shipped_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    INDEX idx_order_number (order_number),
    INDEX idx_customer_id (customer_id),
    INDEX idx_status (status),
    INDEX idx_financial_status (financial_status),
    INDEX idx_created_at (created_at),
    INDEX idx_total_amount (total_amount)
);

-- Order items
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT NULL,
    sku VARCHAR(100) NOT NULL,
    name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(12,4) NOT NULL,
    total_price DECIMAL(12,4) NOT NULL,
    weight DECIMAL(8,3) DEFAULT 0,
    requires_shipping BOOLEAN DEFAULT TRUE,
    is_gift_card BOOLEAN DEFAULT FALSE,
    properties JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,
    INDEX idx_order_id (order_id),
    INDEX idx_product_id (product_id),
    INDEX idx_variant_id (variant_id),
    INDEX idx_sku (sku)
);

-- Shopping carts
CREATE TABLE shopping_carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NULL,
    session_id VARCHAR(255) NULL,
    currency_code CHAR(3) DEFAULT 'USD',
    subtotal DECIMAL(12,4) DEFAULT 0,
    tax_amount DECIMAL(12,4) DEFAULT 0,
    total_amount DECIMAL(12,4) DEFAULT 0,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer_id (customer_id),
    INDEX idx_session_id (session_id),
    INDEX idx_expires_at (expires_at)
);

-- Cart items
CREATE TABLE cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(12,4) NOT NULL,
    total_price DECIMAL(12,4) NOT NULL,
    properties JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES shopping_carts(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
    INDEX idx_cart_id (cart_id),
    INDEX idx_product_id (product_id),
    INDEX idx_variant_id (variant_id)
);
```

#### Generate E-commerce Entities

```bash
# Generate complete e-commerce platform entities
echo "🛍️ Generating E-commerce Platform Entities"

php bin/console reverse:generate \
    --tables=categories \
    --tables=brands \
    --tables=products \
    --tables=product_variants \
    --tables=product_images \
    --tables=customers \
    --tables=customer_addresses \
    --tables=orders \
    --tables=order_items \
    --tables=shopping_carts \
    --tables=cart_items \
    --namespace="App\Entity\Ecommerce" \
    --output-dir="src/Entity/Ecommerce" \
    --force

echo "✅ E-commerce entities generated successfully!"
echo "📊 Generated entities:"
find src/Entity/Ecommerce -name "*.php" -exec basename {} .php \; | sort
```

### Blog/CMS Platform

#### Complete CMS Schema

```sql
-- Users with role-based access
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    display_name VARCHAR(150),
    bio TEXT,
    avatar_url VARCHAR(500),
    role ENUM('super_admin', 'admin', 'editor', 'author', 'contributor', 'subscriber') DEFAULT 'subscriber',
    status ENUM('active', 'inactive', 'suspended', 'pending') DEFAULT 'pending',
    email_verified BOOLEAN DEFAULT FALSE,
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    last_login_at TIMESTAMP NULL,
    last_activity_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status),
    INDEX idx_last_activity (last_activity_at)
);

-- Content categories
CREATE TABLE content_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    parent_id INT NULL,
    sort_order INT DEFAULT 0,
    color VARCHAR(7),
    icon VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    meta_title VARCHAR(255),
    meta_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES content_categories(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_parent_id (parent_id),
    INDEX idx_active_sort (is_active, sort_order)
);

-- Posts/Articles
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    excerpt TEXT,
    content LONGTEXT,
    featured_image_url VARCHAR(500),
    status ENUM('draft', 'published', 'scheduled', 'private', 'archived') DEFAULT 'draft',
    visibility ENUM('public', 'private', 'password_protected') DEFAULT 'public',
    password VARCHAR(255) NULL,
    author_id INT NOT NULL,
    category_id INT NULL,
    comment_status ENUM('open', 'closed', 'moderated') DEFAULT 'open',
    ping_status ENUM('open', 'closed') DEFAULT 'open',
    is_featured BOOLEAN DEFAULT FALSE,
    is_sticky BOOLEAN DEFAULT FALSE,
    view_count INT DEFAULT 0,
    like_count INT DEFAULT 0,
    comment_count INT DEFAULT 0,
    reading_time INT DEFAULT 0,
    seo_title VARCHAR(255),
    seo_description TEXT,
    seo_keywords TEXT,
    published_at TIMESTAMP NULL,
    scheduled_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id),
    FOREIGN KEY (category_id) REFERENCES content_categories(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_author_id (author_id),
    INDEX idx_category_id (category_id),
    INDEX idx_status (status),
    INDEX idx_published_at (published_at),
    INDEX idx_featured (is_featured),
    INDEX idx_sticky (is_sticky),
    FULLTEXT idx_search (title, excerpt, content)
);

-- Tags
CREATE TABLE tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    color VARCHAR(7),
    usage_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_usage_count (usage_count)
);

-- Post-Tag relationships
CREATE TABLE post_tags (
    post_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (post_id, tag_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

-- Comments
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    parent_id INT NULL,
    author_id INT NULL,
    author_name VARCHAR(100),
    author_email VARCHAR(255),
    author_url VARCHAR(500),
    author_ip VARCHAR(45),
    content TEXT NOT NULL,
    status ENUM('pending', 'approved', 'spam', 'trash') DEFAULT 'pending',
    agent VARCHAR(255),
    like_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_post_id (post_id),
    INDEX idx_parent_id (parent_id),
    INDEX idx_author_id (author_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Pages
CREATE TABLE pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content LONGTEXT,
    excerpt TEXT,
    featured_image_url VARCHAR(500),
    status ENUM('draft', 'published', 'private', 'archived') DEFAULT 'draft',
    visibility ENUM('public', 'private', 'password_protected') DEFAULT 'public',
    password VARCHAR(255) NULL,
    author_id INT NOT NULL,
    parent_id INT NULL,
    sort_order INT DEFAULT 0,
    template VARCHAR(100) DEFAULT 'default',
    comment_status ENUM('open', 'closed') DEFAULT 'closed',
    view_count INT DEFAULT 0,
    seo_title VARCHAR(255),
    seo_description TEXT,
    seo_keywords TEXT,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id),
    FOREIGN KEY (parent_id) REFERENCES pages(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_author_id (author_id),
    INDEX idx_parent_id (parent_id),
    INDEX idx_status (status),
    INDEX idx_sort_order (sort_order)
);

-- Media files
CREATE TABLE media_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    title VARCHAR(255),
    alt_text VARCHAR(255),
    caption TEXT,
    description TEXT,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT NOT NULL,
    width INT NULL,
    height INT NULL,
    duration INT NULL,
    path VARCHAR(500) NOT NULL,
    url VARCHAR(500) NOT NULL,
    thumbnail_url VARCHAR(500),
    author_id INT NOT NULL,
    is_private BOOLEAN DEFAULT FALSE,
    download_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id),
    INDEX idx_filename (filename),
    INDEX idx_mime_type (mime_type),
    INDEX idx_author_id (author_id),
    INDEX idx_private (is_private)
);
```

#### Generate CMS Entities

```bash
# Generate complete CMS platform entities
echo "📝 Generating CMS Platform Entities"

php bin/console reverse:generate \
    --tables=users \
    --tables=content_categories \
    --tables=posts \
    --tables=tags \
    --tables=post_tags \
    --tables=comments \
    --tables=pages \
    --tables=media_files \
    --namespace="App\Entity\CMS" \
    --output-dir="src/Entity/CMS" \
    --force

echo "✅ CMS entities generated successfully!"

# Generate additional repository methods
echo "🔧 Generating custom repository methods..."
php bin/console make:repository --entity="App\Entity\CMS\Post"
php bin/console make:repository --entity="App\Entity\CMS\Comment"
```

## 🔧 Advanced Customization

### Environment-Specific Configuration

#### Development Configuration

```yaml
# config/packages/dev/reverse_engineering.yaml
reverse_engineering:
    database:
        driver: pdo_mysql
        host: localhost
        port: 3306
        dbname: myapp_dev
        user: dev_user
        password: dev_password
        options:
            1002: "SET SESSION sql_mode=''"
            1013: "SET SESSION foreign_key_checks=0"
    
    generation:
        namespace: App\Entity\Dev
        output_dir: src/Entity/Dev
        generate_repository: true
        use_annotations: false
        add_validation: true
        add_serialization_groups: true
        
    templates:
        entity: 'dev_entity.php.twig'
        repository: 'dev_repository.php.twig'
        
    performance:
        batch_size: 10
        memory_limit: '256M'
        timeout: 300
        
    features:
        generate_fixtures: true
        generate_tests: true
        validate_schema: true
        
    logging:
        enabled: true
        level: debug
        file: '%kernel.logs_dir%/reverse_engineering_dev.log'
```

#### Production Configuration

```yaml
# config/packages/prod/reverse_engineering.yaml
reverse_engineering:
    database:
        driver: pdo_mysql
        host: '%env(DATABASE_HOST)%'
        port: '%env(DATABASE_PORT)%'
        dbname: '%env(DATABASE_NAME)%'
        user: '%env(DATABASE_USER)%'
        password: '%env(DATABASE_PASSWORD)%'
        options:
            1002: "SET SESSION sql_mode='STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'"
    
    generation:
        namespace: App\Entity
        output_dir: src/Entity
        generate_repository: true
        use_annotations: false
        add_validation: true
        add_serialization_groups: false
        
    templates:
        entity: 'production_entity.php.twig'
        repository: 'production_repository.php.twig'
        
    performance:
        batch_size: 50
        memory_limit: '512M'
        timeout: 600
        
    features:
        generate_fixtures: false
        generate_tests: false
        validate_schema: true
        
    security:
        validate_ssl: true
        read_only: true
        
    logging:
        enabled: true
        level: info
        file: '%kernel.logs_dir%/reverse_engineering_prod.log'
```

### Automated Generation Scripts

#### Complete Generation Workflow

```bash
#!/bin/bash
# scripts/complete-generation-workflow.