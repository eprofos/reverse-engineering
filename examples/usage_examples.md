# Exemples d'utilisation du ReverseEngineeringBundle

## 🎯 Cas d'usage courants

### 1. Migration d'une application legacy

```bash
# Analyser toute la base de données
php bin/console reverse:generate --dry-run

# Générer toutes les entités
php bin/console reverse:generate --force
```

### 2. Génération sélective de tables

```bash
# Générer seulement les tables utilisateurs et produits
php bin/console reverse:generate \
    --tables=users \
    --tables=products \
    --tables=categories

# Exclure les tables de cache et de logs
php bin/console reverse:generate \
    --exclude=cache_items \
    --exclude=log_entries \
    --exclude=sessions
```

### 3. Organisation par modules

```bash
# Module utilisateurs
php bin/console reverse:generate \
    --tables=users \
    --tables=user_profiles \
    --tables=user_permissions \
    --namespace="App\Entity\User" \
    --output-dir="src/Entity/User"

# Module e-commerce
php bin/console reverse:generate \
    --tables=products \
    --tables=categories \
    --tables=orders \
    --tables=order_items \
    --namespace="App\Entity\Shop" \
    --output-dir="src/Entity/Shop"
```

### 4. Environnements multiples

```bash
# Base de données de développement
DATABASE_URL=mysql://dev:dev@localhost/myapp_dev \
php bin/console reverse:generate --dry-run

# Base de données de production (lecture seule)
DATABASE_URL=mysql://readonly:pass@prod-server/myapp \
php bin/console reverse:generate --dry-run
```

## 📊 Exemples de structures de base de données

### E-commerce simple

```sql
-- Tables principales
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE,
    description TEXT,
    parent_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id)
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INT DEFAULT 0,
    category_id INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(20),
    birth_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    order_number VARCHAR(50) UNIQUE,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10,2) NOT NULL,
    shipping_address TEXT,
    billing_address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);
```

### Génération pour l'e-commerce

```bash
php bin/console reverse:generate \
    --tables=categories \
    --tables=products \
    --tables=customers \
    --tables=orders \
    --tables=order_items \
    --namespace="App\Entity\Shop" \
    --output-dir="src/Entity/Shop"
```

### Blog/CMS

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    role ENUM('admin', 'editor', 'author', 'subscriber') DEFAULT 'subscriber',
    is_active BOOLEAN DEFAULT TRUE,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE,
    content LONGTEXT,
    excerpt TEXT,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    author_id INT NOT NULL,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id)
);

CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    author_name VARCHAR(100),
    author_email VARCHAR(255),
    content TEXT NOT NULL,
    status ENUM('pending', 'approved', 'spam') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);

CREATE TABLE tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE post_tags (
    post_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (post_id, tag_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);
```

### Génération pour le blog

```bash
php bin/console reverse:generate \
    --exclude=post_tags \
    --namespace="App\Entity\Blog" \
    --output-dir="src/Entity/Blog"
```

## 🔧 Personnalisation avancée

### Configuration par environnement

```yaml
# config/packages/dev/reverse_engineering.yaml
reverse_engineering:
    database:
        driver: pdo_mysql
        host: localhost
        dbname: myapp_dev
        user: dev_user
        password: dev_pass
    generation:
        namespace: App\Entity\Dev
        output_dir: src/Entity/Dev

# config/packages/prod/reverse_engineering.yaml
reverse_engineering:
    database:
        driver: pdo_mysql
        host: prod-server
        dbname: myapp_prod
        user: readonly_user
        password: readonly_pass
    generation:
        namespace: App\Entity
        output_dir: src/Entity
```

### Script de génération automatisée

```bash
#!/bin/bash
# scripts/generate-entities.sh

echo "🔄 Génération des entités depuis la base de données..."

# Vérifier la connexion
echo "📡 Test de connexion..."
php bin/console reverse:generate --dry-run --tables=users > /dev/null 2>&1
if [ $? -ne 0 ]; then
    echo "❌ Erreur de connexion à la base de données"
    exit 1
fi

echo "✅ Connexion OK"

# Sauvegarder les entités existantes
echo "💾 Sauvegarde des entités existantes..."
if [ -d "src/Entity" ]; then
    cp -r src/Entity src/Entity.backup.$(date +%Y%m%d_%H%M%S)
fi

# Générer les entités par modules
echo "⚙️ Génération des entités utilisateurs..."
php bin/console reverse:generate \
    --tables=users \
    --tables=user_profiles \
    --namespace="App\Entity\User" \
    --output-dir="src/Entity/User" \
    --force

echo "⚙️ Génération des entités produits..."
php bin/console reverse:generate \
    --tables=products \
    --tables=categories \
    --namespace="App\Entity\Product" \
    --output-dir="src/Entity/Product" \
    --force

echo "⚙️ Génération des entités commandes..."
php bin/console reverse:generate \
    --tables=orders \
    --tables=order_items \
    --namespace="App\Entity\Order" \
    --output-dir="src/Entity/Order" \
    --force

echo "✅ Génération terminée !"
echo "📁 Vérifiez les fichiers générés dans src/Entity/"
```

### Validation post-génération

```bash
#!/bin/bash
# scripts/validate-entities.sh

echo "🔍 Validation des entités générées..."

# Vérifier la syntaxe PHP
echo "📝 Vérification de la syntaxe PHP..."
find src/Entity -name "*.php" -exec php -l {} \; | grep -v "No syntax errors"

# Vérifier avec Doctrine
echo "🗄️ Validation du schéma Doctrine..."
php bin/console doctrine:schema:validate

# Vérifier les mappings
echo "🔗 Validation des mappings..."
php bin/console doctrine:mapping:info

echo "✅ Validation terminée !"
```

## 🚀 Intégration CI/CD

### GitHub Actions

```yaml
# .github/workflows/reverse-engineering.yml
name: Reverse Engineering

on:
  schedule:
    - cron: '0 2 * * 1'  # Tous les lundis à 2h
  workflow_dispatch:

jobs:
  generate-entities:
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

    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          
      - name: Install dependencies
        run: composer install
        
      - name: Generate entities
        run: |
          php bin/console reverse:generate --dry-run
          
      - name: Validate entities
        run: |
          php bin/console doctrine:schema:validate
```

## 📈 Monitoring et logs

### Configuration des logs

```yaml
# config/packages/monolog.yaml
monolog:
    channels: ['reverse_engineering']
    
    handlers:
        reverse_engineering:
            type: rotating_file
            path: '%kernel.logs_dir%/reverse_engineering.log'
            level: info
            channels: ['reverse_engineering']
            max_files: 10
```

### Métriques de génération

```bash
# Compter les entités générées
find src/Entity -name "*.php" | wc -l

# Analyser les types de relations
grep -r "ManyToOne\|OneToMany\|ManyToMany" src/Entity/ | wc -l

# Vérifier les tables non traitées
php bin/console reverse:generate --dry-run | grep "table non trouvée"