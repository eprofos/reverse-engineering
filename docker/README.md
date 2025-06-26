# Environnement Docker pour ReverseEngineeringBundle

Ce répertoire contient la configuration Docker pour tester le bundle avec la base de données Sakila.

## 🐳 Services Docker

### MySQL 8.0 avec Sakila
- **Image** : `mysql:8.0`
- **Port** : `3306`
- **Base de données** : `sakila`
- **Utilisateur** : `sakila_user`
- **Mot de passe** : `sakila_password`

### PHP 8.2 CLI
- **Image** : PHP 8.2 avec extensions nécessaires
- **Extensions** : PDO, PDO_MySQL, MySQLi, Zip, GD, MBString, XML, BCMath
- **Composer** : Inclus

### phpMyAdmin (optionnel)
- **Port** : `8080`
- **URL** : http://localhost:8080

## 🚀 Démarrage rapide

### 1. Démarrer l'environnement

```bash
# Depuis la racine du projet
docker-compose up -d
```

### 2. Vérifier que MySQL est prêt

```bash
# Attendre que le conteneur soit healthy
docker-compose ps

# Vérifier les logs MySQL
docker-compose logs mysql
```

### 3. Exécuter les tests avec Sakila

```bash
# Tests d'intégration Sakila uniquement
docker-compose exec php vendor/bin/phpunit tests/Integration/SakilaIntegrationTest.php

# Tous les tests
docker-compose exec php vendor/bin/phpunit
```

## 📊 Base de données Sakila

La base de données Sakila est une base de données d'exemple MySQL qui simule un magasin de location de DVD. Elle contient :

### Tables principales
- **`actor`** : Acteurs des films
- **`film`** : Catalogue des films
- **`customer`** : Clients du magasin
- **`rental`** : Locations de films
- **`payment`** : Paiements
- **`inventory`** : Inventaire des films
- **`store`** : Magasins
- **`staff`** : Personnel
- **`address`** : Adresses
- **`city`** : Villes
- **`country`** : Pays
- **`category`** : Catégories de films
- **`language`** : Langues

### Relations complexes
- **Many-to-One** : `customer` → `address`, `film` → `language`
- **One-to-Many** : `customer` → `rental`, `film` → `inventory`
- **Many-to-Many** : `film` ↔ `actor` (via `film_actor`), `film` ↔ `category` (via `film_category`)

### Types de données variés
- **Entiers** : `TINYINT`, `SMALLINT`, `MEDIUMINT`, `INT`
- **Décimaux** : `DECIMAL(4,2)`, `DECIMAL(5,2)`
- **Texte** : `VARCHAR`, `CHAR`, `TEXT`
- **Dates** : `DATE`, `DATETIME`, `TIMESTAMP`, `YEAR`
- **Énumérations** : `ENUM('G','PG','PG-13','R','NC-17')`
- **Sets** : `SET('Trailers','Commentaries',...)`
- **Booléens** : `BOOLEAN`
- **Binaire** : `BLOB`

## 🧪 Tests disponibles

### Tests d'intégration Sakila
```bash
# Test complet de génération d'entités
docker-compose exec php vendor/bin/phpunit tests/Integration/SakilaIntegrationTest.php::testCompleteEntityGeneration

# Test des relations complexes
docker-compose exec php vendor/bin/phpunit tests/Integration/SakilaIntegrationTest.php::testComplexRelations

# Test des types de données
docker-compose exec php vendor/bin/phpunit tests/Integration/SakilaIntegrationTest.php::testDataTypeMapping

# Test de performance
docker-compose exec php vendor/bin/phpunit tests/Integration/SakilaIntegrationTest.php::testPerformanceOnFullDatabase
```

### Génération manuelle d'entités
```bash
# Générer toutes les entités Sakila
docker-compose exec php bin/console reverse:generate \
    --namespace="Sakila\\Entity" \
    --output-dir="generated/sakila"

# Générer des tables spécifiques
docker-compose exec php bin/console reverse:generate \
    --tables=actor --tables=film --tables=customer \
    --namespace="Sakila\\Entity"
```

## 🔧 Configuration

### Variables d'environnement
Les variables suivantes peuvent être modifiées dans [`docker-compose.yml`](../docker-compose.yml) :

```yaml
environment:
  MYSQL_ROOT_PASSWORD: root_password
  MYSQL_DATABASE: sakila
  MYSQL_USER: sakila_user
  MYSQL_PASSWORD: sakila_password
```

### Configuration MySQL
La configuration MySQL est dans [`mysql/conf/my.cnf`](mysql/conf/my.cnf) :
- Charset UTF8MB4
- InnoDB optimisé
- Query cache activé
- Logs des requêtes lentes

### Configuration PHP
La configuration PHP est dans [`php/conf/php.ini`](php/conf/php.ini) :
- Mémoire : 512M
- Temps d'exécution : 300s
- Extensions MySQL activées
- OPcache optimisé

## 🐛 Dépannage

### MySQL ne démarre pas
```bash
# Vérifier les logs
docker-compose logs mysql

# Redémarrer le service
docker-compose restart mysql

# Reconstruire l'image
docker-compose build --no-cache mysql
```

### Connexion refusée
```bash
# Vérifier que le port 3306 est libre
netstat -tlnp | grep 3306

# Attendre que MySQL soit prêt
docker-compose exec mysql mysqladmin ping -h localhost -u root -p
```

### Base de données vide
```bash
# Vérifier l'initialisation
docker-compose logs mysql | grep -i sakila

# Réinitialiser les données
docker-compose down -v
docker-compose up -d
```

### Tests échouent
```bash
# Vérifier la connexion depuis PHP
docker-compose exec php php -r "
try {
    \$pdo = new PDO('mysql:host=mysql;dbname=sakila', 'sakila_user', 'sakila_password');
    echo 'Connexion OK\n';
    \$stmt = \$pdo->query('SELECT COUNT(*) FROM actor');
    echo 'Acteurs: ' . \$stmt->fetchColumn() . '\n';
} catch (Exception \$e) {
    echo 'Erreur: ' . \$e->getMessage() . '\n';
}
"
```

## 📈 Performance

### Métriques attendues
- **Temps de génération** : < 30 secondes pour toutes les tables
- **Mémoire utilisée** : < 128 MB
- **Tables traitées** : 15+ tables principales
- **Entités générées** : 15+ entités avec relations

### Optimisations
- Index sur les clés étrangères
- Query cache MySQL activé
- OPcache PHP configuré
- Connexions persistantes

## 🔒 Sécurité

### Accès réseau
- MySQL accessible uniquement depuis localhost:3306
- phpMyAdmin accessible depuis localhost:8080
- Pas d'exposition externe par défaut

### Authentification
- Utilisateur MySQL dédié (non-root)
- Mots de passe configurables
- Base de données isolée

## 📝 Maintenance

### Sauvegarde
```bash
# Exporter la base Sakila
docker-compose exec mysql mysqldump -u sakila_user -p sakila > sakila_backup.sql
```

### Nettoyage
```bash
# Arrêter et supprimer les conteneurs
docker-compose down

# Supprimer les volumes (données perdues)
docker-compose down -v

# Supprimer les images
docker-compose down --rmi all
```

### Mise à jour
```bash
# Mettre à jour les images
docker-compose pull

# Reconstruire les services
docker-compose build --no-cache

# Redémarrer
docker-compose up -d