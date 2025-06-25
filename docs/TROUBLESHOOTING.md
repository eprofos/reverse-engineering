# Guide de Dépannage - ReverseEngineeringBundle

Ce guide vous aide à résoudre les problèmes courants rencontrés lors de l'utilisation du ReverseEngineeringBundle.

## 🚨 Problèmes de Connexion Base de Données

### Erreur : "Connection refused" ou "Access denied"

**Symptômes :**
```
DatabaseConnectionException: SQLSTATE[HY000] [2002] Connection refused
DatabaseConnectionException: SQLSTATE[28000] [1045] Access denied for user
```

**Causes possibles :**
- Paramètres de connexion incorrects
- Base de données non démarrée
- Permissions utilisateur insuffisantes
- Firewall bloquant la connexion

**Solutions :**

1. **Vérifier les paramètres de connexion :**
```yaml
# config/packages/reverse_engineering.yaml
reverse_engineering:
    database:
        driver: pdo_mysql
        host: localhost    # Vérifier l'hôte
        port: 3306        # Vérifier le port
        dbname: myapp     # Vérifier le nom de la BDD
        user: username    # Vérifier l'utilisateur
        password: password # Vérifier le mot de passe
```

2. **Tester la connexion manuellement :**
```bash
# MySQL
mysql -h localhost -P 3306 -u username -p myapp

# PostgreSQL
psql -h localhost -p 5432 -U username -d myapp

# Vérifier que le service est démarré
sudo systemctl status mysql
sudo systemctl status postgresql
```

3. **Vérifier les permissions utilisateur :**
```sql
-- MySQL
SHOW GRANTS FOR 'username'@'localhost';
GRANT SELECT ON myapp.* TO 'username'@'localhost';

-- PostgreSQL
\du username
GRANT USAGE ON SCHEMA public TO username;
GRANT SELECT ON ALL TABLES IN SCHEMA public TO username;
```

### Erreur : "Driver not found"

**Symptômes :**
```
DatabaseConnectionException: Driver pdo_mysql not found
```

**Solutions :**

1. **Installer l'extension PHP manquante :**
```bash
# Ubuntu/Debian
sudo apt-get install php-mysql php-pgsql php-sqlite3

# CentOS/RHEL
sudo yum install php-mysql php-pgsql php-sqlite

# Vérifier les extensions installées
php -m | grep -E "(mysql|pgsql|sqlite)"
```

2. **Vérifier la configuration PHP :**
```bash
php -i | grep -E "(mysql|pgsql|sqlite)"
```

### Erreur : "Unknown database" ou "Database does not exist"

**Solutions :**

1. **Créer la base de données :**
```sql
-- MySQL
CREATE DATABASE myapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- PostgreSQL
CREATE DATABASE myapp WITH ENCODING 'UTF8';
```

2. **Vérifier l'existence de la base :**
```sql
-- MySQL
SHOW DATABASES;

-- PostgreSQL
\l
```

---

## 🔍 Problèmes d'Extraction de Métadonnées

### Erreur : "Table not found" ou "Permission denied"

**Symptômes :**
```
MetadataExtractionException: Table 'users' doesn't exist
MetadataExtractionException: Access denied for table 'users'
```

**Solutions :**

1. **Vérifier l'existence des tables :**
```bash
php bin/console reverse:generate --dry-run --verbose
```

2. **Lister les tables disponibles :**
```sql
-- MySQL
SHOW TABLES;

-- PostgreSQL
\dt

-- SQLite
.tables
```

3. **Vérifier les permissions :**
```sql
-- MySQL
SHOW GRANTS FOR CURRENT_USER();

-- PostgreSQL
SELECT * FROM information_schema.table_privileges WHERE grantee = CURRENT_USER;
```

### Erreur : "Unsupported column type"

**Symptômes :**
```
MetadataExtractionException: Unsupported column type 'GEOMETRY'
```

**Solutions :**

1. **Exclure les tables avec types non supportés :**
```bash
php bin/console reverse:generate --exclude=spatial_table
```

2. **Mapping personnalisé (développement futur) :**
```php
// Configuration personnalisée pour types spéciaux
$customMapping = [
    'GEOMETRY' => 'string',
    'POINT' => 'string',
    'POLYGON' => 'string'
];
```

### Erreur : "Foreign key constraint error"

**Solutions :**

1. **Vérifier l'intégrité des clés étrangères :**
```sql
-- MySQL
SELECT * FROM information_schema.KEY_COLUMN_USAGE 
WHERE REFERENCED_TABLE_NAME IS NOT NULL;

-- PostgreSQL
SELECT * FROM information_schema.referential_constraints;
```

2. **Générer les tables dans l'ordre des dépendances :**
```bash
# Générer d'abord les tables parentes
php bin/console reverse:generate --tables=categories
php bin/console reverse:generate --tables=products
```

---

## ⚙️ Problèmes de Génération d'Entités

### Erreur : "Template not found" ou "Twig error"

**Symptômes :**
```
EntityGenerationException: Template "entity.php.twig" not found
EntityGenerationException: Syntax error in template
```

**Solutions :**

1. **Vérifier l'existence du template :**
```bash
ls -la src/Resources/templates/
```

2. **Réinstaller le bundle :**
```bash
composer reinstall eprofos/reverse-engineering-bundle
```

3. **Vérifier la configuration Twig :**
```yaml
# config/packages/twig.yaml
twig:
    paths:
        '%kernel.project_dir%/src/Resources/templates': 'ReverseEngineering'
```

### Erreur : "Invalid namespace" ou "Class name conflict"

**Symptômes :**
```
EntityGenerationException: Invalid namespace 'App\Entity\123Invalid'
EntityGenerationException: Class 'User' already exists
```

**Solutions :**

1. **Utiliser un namespace valide :**
```bash
php bin/console reverse:generate --namespace="App\\Entity\\Generated"
```

2. **Forcer l'écrasement ou utiliser un répertoire différent :**
```bash
php bin/console reverse:generate --force
# ou
php bin/console reverse:generate --output-dir="src/Entity/New"
```

### Erreur : "Memory limit exceeded"

**Symptômes :**
```
Fatal error: Allowed memory size exhausted
```

**Solutions :**

1. **Augmenter la limite mémoire :**
```bash
php -d memory_limit=512M bin/console reverse:generate
```

2. **Traiter les tables par petits lots :**
```bash
# Traiter 10 tables à la fois
php bin/console reverse:generate --tables=table1 --tables=table2 --tables=table3
```

3. **Optimiser la configuration PHP :**
```ini
; php.ini
memory_limit = 512M
max_execution_time = 300
```

---

## 📁 Problèmes d'Écriture de Fichiers

### Erreur : "Permission denied" ou "Directory not writable"

**Symptômes :**
```
FileWriteException: Permission denied: /path/to/src/Entity/User.php
FileWriteException: Directory '/path/to/src/Entity' is not writable
```

**Solutions :**

1. **Vérifier les permissions :**
```bash
ls -la src/
chmod 755 src/Entity/
chown -R www-data:www-data src/Entity/
```

2. **Créer le répertoire manuellement :**
```bash
mkdir -p src/Entity/Generated
chmod 755 src/Entity/Generated
```

3. **Utiliser un répertoire temporaire :**
```bash
php bin/console reverse:generate --output-dir="/tmp/entities"
```

### Erreur : "File already exists"

**Solutions :**

1. **Utiliser l'option force :**
```bash
php bin/console reverse:generate --force
```

2. **Sauvegarder les fichiers existants :**
```bash
cp -r src/Entity src/Entity.backup.$(date +%Y%m%d)
php bin/console reverse:generate --force
```

### Erreur : "Invalid filename" ou "Path too long"

**Solutions :**

1. **Utiliser des noms de tables plus courts :**
```bash
# Renommer la table si possible
ALTER TABLE very_long_table_name_that_causes_issues RENAME TO short_name;
```

2. **Utiliser un répertoire de sortie plus court :**
```bash
php bin/console reverse:generate --output-dir="src/E"
```

---

## 🐛 Problèmes de Performance

### Génération très lente

**Symptômes :**
- Génération qui prend plusieurs minutes
- Utilisation excessive de la mémoire
- Timeouts de connexion

**Solutions :**

1. **Optimiser la connexion base de données :**
```yaml
reverse_engineering:
    database:
        options:
            # MySQL
            1002: "SET SESSION sql_mode=''"  # PDO::MYSQL_ATTR_INIT_COMMAND
            # PostgreSQL
            'application_name': 'ReverseEngineering'
```

2. **Traiter par lots :**
```bash
# Script de traitement par lots
#!/bin/bash
TABLES=(users products orders categories)
for table in "${TABLES[@]}"; do
    echo "Processing $table..."
    php bin/console reverse:generate --tables=$table
done
```

3. **Utiliser des index sur les tables système :**
```sql
-- MySQL - Optimiser information_schema (si possible)
-- PostgreSQL - Analyser les statistiques
ANALYZE;
```

### Utilisation excessive de mémoire

**Solutions :**

1. **Limiter le nombre de tables traitées :**
```bash
php bin/console reverse:generate --tables=table1 --tables=table2
```

2. **Optimiser la configuration PHP :**
```ini
; php.ini
memory_limit = 256M
opcache.enable = 1
opcache.memory_consumption = 128
```

---

## 🔧 Problèmes de Configuration

### Configuration non prise en compte

**Solutions :**

1. **Vider le cache :**
```bash
php bin/console cache:clear
php bin/console cache:clear --env=prod
```

2. **Vérifier la syntaxe YAML :**
```bash
php bin/console lint:yaml config/packages/reverse_engineering.yaml
```

3. **Vérifier l'ordre de chargement des bundles :**
```php
// config/bundles.php
return [
    // ... autres bundles
    App\Bundle\ReverseEngineeringBundle::class => ['all' => true],
];
```

### Variables d'environnement non résolues

**Solutions :**

1. **Vérifier le fichier .env :**
```bash
# .env
DATABASE_URL=mysql://user:pass@localhost:3306/dbname
```

2. **Utiliser la résolution de variables :**
```yaml
reverse_engineering:
    database:
        driver: '%env(DB_DRIVER)%'
        host: '%env(DB_HOST)%'
        dbname: '%env(DB_NAME)%'
```

---

## 🧪 Problèmes de Tests

### Tests qui échouent

**Solutions :**

1. **Vérifier l'environnement de test :**
```bash
# Vérifier la configuration de test
cat phpunit.xml
```

2. **Nettoyer le cache de test :**
```bash
rm -rf .phpunit.cache
vendor/bin/phpunit --cache-clear
```

3. **Vérifier les dépendances de test :**
```bash
composer install --dev
```

### Base de données de test non accessible

**Solutions :**

1. **Utiliser SQLite en mémoire :**
```xml
<!-- phpunit.xml -->
<php>
    <env name="DATABASE_URL" value="sqlite:///:memory:" />
</php>
```

2. **Créer une base de test dédiée :**
```sql
CREATE DATABASE myapp_test;
GRANT ALL ON myapp_test.* TO 'test_user'@'localhost';
```

---

## 📊 Outils de Diagnostic

### Script de diagnostic automatique

```bash
#!/bin/bash
# scripts/diagnose.sh

echo "=== DIAGNOSTIC REVERSEENGINEERINGBUNDLE ==="

echo "1. Vérification PHP..."
php --version
php -m | grep -E "(pdo|mysql|pgsql|sqlite)"

echo "2. Vérification Composer..."
composer --version
composer show eprofos/reverse-engineering-bundle

echo "3. Vérification configuration..."
if [ -f "config/packages/reverse_engineering.yaml" ]; then
    echo "✓ Fichier de configuration présent"
else
    echo "✗ Fichier de configuration manquant"
fi

echo "4. Test de connexion..."
php bin/console reverse:generate --dry-run --tables=non_existent 2>&1 | head -5

echo "5. Vérification permissions..."
ls -la src/Entity/ 2>/dev/null || echo "Répertoire src/Entity non trouvé"

echo "6. Vérification cache..."
ls -la var/cache/ | head -3

echo "=== FIN DIAGNOSTIC ==="
```

### Commandes de debug utiles

```bash
# Informations détaillées sur la configuration
php bin/console debug:config reverse_engineering

# Services disponibles
php bin/console debug:container reverse

# Vérification des routes (si applicable)
php bin/console debug:router | grep reverse

# Logs en temps réel
tail -f var/log/dev.log | grep -i reverse

# Test de connexion base de données
php bin/console dbal:run-sql "SELECT 1"
```

---

## 📞 Obtenir de l'Aide

### Informations à fournir lors d'un rapport de bug

1. **Version du bundle :**
```bash
composer show eprofos/reverse-engineering-bundle
```

2. **Configuration PHP :**
```bash
php --version
php -m | grep -E "(pdo|mysql|pgsql|sqlite)"
```

3. **Configuration Symfony :**
```bash
php bin/console --version
```

4. **Configuration base de données :**
```yaml
# Masquer les mots de passe !
reverse_engineering:
    database:
        driver: pdo_mysql
        host: localhost
        port: 3306
        # ...
```

5. **Message d'erreur complet :**
```bash
php bin/console reverse:generate --verbose 2>&1
```

6. **Logs applicatifs :**
```bash
tail -50 var/log/dev.log
```

### Canaux de support

- **Issues GitHub :** [Signaler un bug](https://github.com/eprofos/reverse-engineering-bundle/issues/new?template=bug_report.md)
- **Discussions :** [Poser une question](https://github.com/eprofos/reverse-engineering-bundle/discussions)
- **Documentation :** [Guide complet](https://github.com/eprofos/reverse-engineering-bundle#readme)

---

## 🔄 Procédure de Récupération d'Urgence

### En cas de corruption des entités générées

1. **Restaurer depuis la sauvegarde :**
```bash
# Si vous avez fait une sauvegarde
cp -r src/Entity.backup/* src/Entity/
```

2. **Régénérer proprement :**
```bash
# Nettoyer le répertoire
rm -rf src/Entity/Generated/

# Régénérer avec validation
php bin/console reverse:generate --dry-run
php bin/console reverse:generate --output-dir="src/Entity/Generated"
```

3. **Valider les entités générées :**
```bash
# Vérifier la syntaxe PHP
find src/Entity -name "*.php" -exec php -l {} \;

# Valider avec Doctrine
php bin/console doctrine:schema:validate
```

### En cas de problème de performance critique

1. **Mode dégradé :**
```bash
# Traiter une seule table à la fois
php bin/console reverse:generate --tables=critical_table --force
```

2. **Augmenter les limites temporairement :**
```bash
php -d memory_limit=1G -d max_execution_time=600 bin/console reverse:generate
```

---

**Ce guide couvre les problèmes les plus courants. Pour des cas spécifiques, n'hésitez pas à consulter la communauté ou créer une issue sur GitHub.**