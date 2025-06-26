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

# Ou utiliser le script utilitaire
./docker-test.sh start
```

### 2. Vérifier que MySQL est prêt

```bash
# Attendre que le conteneur soit healthy
docker-compose ps

# Vérifier les logs MySQL
docker-compose logs mysql

# Ou utiliser le script utilitaire
./docker-test.sh status
```

### 3. Générer et récupérer les entités Sakila

```bash
# Génération et copie automatique (recommandé)
./docker-test.sh generate-and-copy

# Les entités seront disponibles dans ./generated-entities/
```

### 4. Exécuter les tests avec Sakila

```bash
# Tests d'intégration Sakila uniquement
docker-compose exec php vendor/bin/phpunit tests/Integration/SakilaIntegrationTest.php

# Ou utiliser le script utilitaire
./docker-test.sh test-sakila

# Tous les tests
./docker-test.sh test-all
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

#### Génération dans le conteneur uniquement
```bash
# Générer toutes les entités Sakila dans le conteneur
docker-compose exec php php scripts/generate-entities.php \
    --namespace="Sakila\\Entity" \
    --output-dir="generated/sakila"

# Ou utiliser le script utilitaire
./docker-test.sh generate
```

#### Génération et copie automatique vers l'hôte local
```bash
# Génération et copie automatique (recommandé)
./docker-test.sh generate-and-copy

# Avec répertoire de destination personnalisé
./docker-test.sh generate-and-copy ./my-entities

# Avec répertoire et namespace personnalisés
./docker-test.sh generate-and-copy ./src/Entity "MyApp\\Entity"
```

#### Avantages de la commande `generate-and-copy`
- ✅ Génération automatique dans le conteneur Docker
- ✅ Copie automatique des fichiers vers l'hôte local
- ✅ Validation de la syntaxe PHP des fichiers copiés
- ✅ Nettoyage automatique des fichiers temporaires
- ✅ Statistiques détaillées (temps, taille, nombre de fichiers)
- ✅ Correction automatique des permissions
- ✅ Résumé complet des opérations

## 🔄 Commande `generate-and-copy` - Guide complet

### Description
La commande `generate-and-copy` automatise complètement le processus de génération d'entités depuis la base de données Sakila et leur récupération sur l'hôte local. Cette commande combine la génération dans le conteneur Docker avec la copie automatique des fichiers générés.

### Syntaxe
```bash
./docker-test.sh generate-and-copy [répertoire_destination] [namespace]
```

### Paramètres
- **`répertoire_destination`** (optionnel) : Répertoire local où copier les entités générées
  - Défaut : `./generated-entities`
  - Exemple : `./src/Entity`, `./my-entities`

- **`namespace`** (optionnel) : Namespace PHP pour les entités générées
  - Défaut : `Sakila\\Entity`
  - Exemple : `MyApp\\Entity`, `App\\Entity\\Sakila`

### Exemples d'utilisation

#### Utilisation basique
```bash
# Génération avec paramètres par défaut
./docker-test.sh generate-and-copy

# Résultat : Entités dans ./generated-entities/ avec namespace Sakila\Entity
```

#### Répertoire personnalisé
```bash
# Spécifier un répertoire de destination
./docker-test.sh generate-and-copy ./my-entities

# Résultat : Entités dans ./my-entities/ avec namespace Sakila\Entity
```

#### Répertoire et namespace personnalisés
```bash
# Spécifier répertoire et namespace
./docker-test.sh generate-and-copy ./src/Entity "MyApp\\Entity"

# Résultat : Entités dans ./src/Entity/ avec namespace MyApp\Entity
```

### Processus détaillé

1. **Vérification de l'environnement**
   - Contrôle que Docker et Docker Compose sont installés
   - Vérification que l'environnement MySQL est démarré

2. **Préparation**
   - Création du répertoire de destination local
   - Nettoyage du répertoire de génération dans le conteneur

3. **Génération des entités**
   - Exécution du script de génération dans le conteneur PHP
   - Mesure du temps d'exécution
   - Validation de la génération

4. **Copie des fichiers**
   - Utilisation de `docker cp` pour copier les fichiers
   - Préservation de la structure des répertoires
   - Correction automatique des permissions

5. **Validation et nettoyage**
   - Validation de la syntaxe PHP (si PHP disponible sur l'hôte)
   - Nettoyage des fichiers temporaires dans le conteneur
   - Génération du rapport final

### Structure des fichiers générés

```
generated-entities/          # Répertoire de destination
├── Actor.php               # Entité Actor
├── ActorRepository.php     # Repository Actor
├── Film.php                # Entité Film
├── FilmRepository.php      # Repository Film
├── Customer.php            # Entité Customer
├── CustomerRepository.php  # Repository Customer
└── ...                     # Autres entités et repositories
```

### Informations affichées

La commande affiche un rapport détaillé incluant :

- **Temps de génération** : Durée de la génération des entités
- **Nombre de fichiers** : Entités et repositories générés
- **Taille totale** : Espace disque utilisé par les fichiers
- **Validation syntaxe** : Résultat de la validation PHP
- **Liste des fichiers** : Détail de chaque fichier généré avec sa taille

### Exemple de sortie

```bash
$ ./docker-test.sh generate-and-copy ./my-entities "MyApp\\Entity"

[INFO] Génération et copie automatique des entités...
[INFO] Répertoire de destination local: ./my-entities
[INFO] Namespace: MyApp\Entity
[INFO] Répertoire local créé: ./my-entities
[INFO] Nettoyage du répertoire de génération dans le conteneur...
[INFO] Génération des entités dans le conteneur Docker...
[SUCCESS] Entités générées avec succès en 12s
[INFO] Récupération de la liste des fichiers générés...
[INFO] Fichiers à copier: 32
[INFO] Copie des fichiers du conteneur vers l'hôte local...
[SUCCESS] Fichiers copiés avec succès vers ./my-entities
[INFO] Correction des permissions des fichiers...
[INFO] Validation de la syntaxe PHP des fichiers copiés...
[INFO] Nettoyage des fichiers temporaires dans le conteneur...

[SUCCESS] 🎉 Génération et copie terminées avec succès !

[INFO] 📊 Résumé des opérations:
[INFO]    - Temps de génération: 12s
[INFO]    - Fichiers générés: 32
[INFO]    - Fichiers copiés: 32
[INFO]    - Taille totale: 156K
[INFO]    - Répertoire de destination: ./my-entities
[INFO]    - Namespace utilisé: MyApp\Entity
[SUCCESS]    - Validation syntaxe: ✅ Tous les fichiers sont valides

[INFO] 📁 Fichiers générés:
[INFO]    - Actor.php (2.1K)
[INFO]    - ActorRepository.php (1.2K)
[INFO]    - Film.php (4.8K)
[INFO]    - FilmRepository.php (1.2K)
[INFO]    - ...

[INFO] 💡 Pour utiliser ces entités dans votre projet Symfony:
[INFO]    1. Copiez les fichiers vers src/Entity/ de votre projet
[INFO]    2. Ajustez le namespace selon votre configuration
[INFO]    3. Exécutez 'php bin/console doctrine:schema:validate'

[SUCCESS] Opération terminée avec succès !
```

### Intégration dans un projet Symfony

Après génération, pour utiliser les entités dans votre projet :

1. **Copier les fichiers**
   ```bash
   cp ./generated-entities/*.php /path/to/your/symfony/project/src/Entity/
   ```

2. **Ajuster le namespace** (si nécessaire)
   ```php
   // Remplacer dans tous les fichiers
   namespace Sakila\Entity;
   // Par
   namespace App\Entity;
   ```

3. **Valider avec Doctrine**
   ```bash
   cd /path/to/your/symfony/project
   php bin/console doctrine:schema:validate
   ```

4. **Générer les migrations** (si nécessaire)
   ```bash
   php bin/console doctrine:migrations:diff
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