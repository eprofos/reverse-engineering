# Configuration Docker avec Sakila - Guide Complet

## 📋 Résumé de l'environnement créé

L'environnement Docker avec la base de données Sakila a été configuré avec succès pour tester le ReverseEngineeringBundle sur une base de données complexe et réaliste.

## 🏗️ Architecture mise en place

### Services Docker
- **MySQL 8.0** avec base Sakila complète
- **PHP 8.2** avec toutes les extensions nécessaires
- **phpMyAdmin** pour l'administration
- **Volumes persistants** pour les données

### Fichiers créés

#### Configuration Docker
- [`docker-compose.yml`](docker-compose.yml) - Configuration des services
- [`docker/php/Dockerfile`](docker/php/Dockerfile) - Image PHP personnalisée
- [`docker/php/conf/php.ini`](docker/php/conf/php.ini) - Configuration PHP
- [`docker/mysql/conf/my.cnf`](docker/mysql/conf/my.cnf) - Configuration MySQL
- [`docker/mysql/init/01-sakila-schema.sql`](docker/mysql/init/01-sakila-schema.sql) - Schéma Sakila
- [`docker/mysql/init/02-download-sakila-data.sh`](docker/mysql/init/02-download-sakila-data.sh) - Script de données

#### Tests d'intégration
- [`tests/Integration/SakilaIntegrationTest.php`](tests/Integration/SakilaIntegrationTest.php) - Tests complets avec Sakila
- [`tests/TestHelper.php`](tests/TestHelper.php) - Méthodes utilitaires Docker
- [`tests/bootstrap.php`](tests/bootstrap.php) - Configuration des tests mise à jour
- [`phpunit.docker.xml`](phpunit.docker.xml) - Configuration PHPUnit pour Docker

#### Scripts et documentation
- [`docker-test.sh`](docker-test.sh) - Script utilitaire pour Docker
- [`docker/README.md`](docker/README.md) - Documentation complète Docker
- [`.env.docker`](.env.docker) - Variables d'environnement
- [`.gitignore`](.gitignore) - Exclusions Docker ajoutées

## 🚀 Utilisation rapide

### 1. Démarrer l'environnement
```bash
# Méthode simple
./docker-test.sh start

# Ou manuellement
docker-compose up -d
```

### 2. Exécuter les tests Sakila
```bash
# Tests d'intégration Sakila
./docker-test.sh test-sakila

# Ou manuellement
docker-compose exec php vendor/bin/phpunit tests/Integration/SakilaIntegrationTest.php
```

### 3. Générer des entités
```bash
# Génération automatique
./docker-test.sh generate

# Ou manuellement
docker-compose exec php bin/console reverse:generate \
    --namespace="Sakila\\Entity" \
    --output-dir="generated/sakila"
```

## 🧪 Tests disponibles

### Tests d'intégration Sakila
Le fichier [`SakilaIntegrationTest.php`](tests/Integration/SakilaIntegrationTest.php) contient :

1. **Test de connexion** - Validation de la connexion Docker
2. **Test de génération complète** - Toutes les entités Sakila
3. **Test des relations complexes** - OneToMany, ManyToOne, ManyToMany
4. **Test des types de données** - DECIMAL, ENUM, SET, YEAR, BLOB
5. **Test des contraintes** - Clés primaires, étrangères, index
6. **Test de performance** - Base complète avec métriques
7. **Test des relations Many-to-Many** - Tables de liaison
8. **Test avec exclusions** - Filtrage de tables
9. **Test de validation** - Syntaxe PHP générée
10. **Test des métadonnées** - Extraction complète

### Couverture des tables Sakila
- **15+ tables principales** : actor, film, customer, rental, payment, etc.
- **Relations complexes** : 10+ types de relations différentes
- **Types de données variés** : Tous les types MySQL supportés
- **Contraintes avancées** : Clés composites, index multiples

## 📊 Métriques de performance attendues

- **Temps de génération** : < 30 secondes pour toutes les tables
- **Mémoire utilisée** : < 128 MB
- **Tables traitées** : 15+ tables principales
- **Entités générées** : 15+ entités avec relations
- **Fichiers créés** : 30+ fichiers (entités + repositories)

## 🔧 Configuration avancée

### Variables d'environnement
Modifiables dans [`.env.docker`](.env.docker) :
- Mots de passe MySQL
- Limites PHP
- Ports exposés
- Chemins de volumes

### Personnalisation des tests
Le fichier [`phpunit.docker.xml`](phpunit.docker.xml) permet :
- Configuration spécifique Docker
- Suites de tests séparées
- Métriques de couverture
- Variables d'environnement

## 🐛 Dépannage

### Problèmes courants
1. **Port 3306 occupé** : Modifier `MYSQL_EXTERNAL_PORT` dans `.env.docker`
2. **MySQL lent à démarrer** : Attendre 60-90 secondes
3. **Tests échouent** : Vérifier que MySQL est prêt avec `./docker-test.sh status`
4. **Mémoire insuffisante** : Augmenter `PHP_MEMORY_LIMIT`

### Commandes de diagnostic
```bash
# Statut des conteneurs
./docker-test.sh status

# Logs MySQL
./docker-test.sh logs mysql

# Session PHP
./docker-test.sh shell-php

# Session MySQL
./docker-test.sh shell-mysql
```

## 📈 Prochaines étapes

### Améliorations possibles
1. **CI/CD** : Intégration dans GitHub Actions
2. **Tests de régression** : Comparaison avec versions précédentes
3. **Benchmarks** : Métriques de performance automatisées
4. **Multi-SGBD** : PostgreSQL et SQLite avec Docker
5. **Monitoring** : Métriques en temps réel

### Utilisation en production
L'environnement Docker peut servir de base pour :
- Tests d'intégration continue
- Validation de nouvelles fonctionnalités
- Benchmarks de performance
- Formation et démonstrations

## ✅ Validation de l'installation

Pour vérifier que tout fonctionne :

```bash
# 1. Démarrer l'environnement
./docker-test.sh start

# 2. Vérifier le statut
./docker-test.sh status

# 3. Exécuter les tests
./docker-test.sh test-sakila

# 4. Générer des entités
./docker-test.sh generate

# 5. Vérifier les fichiers générés
ls -la generated/sakila/
```

Si toutes ces étapes réussissent, l'environnement Docker avec Sakila est opérationnel ! 🎉

## 📞 Support

En cas de problème :
1. Consulter [`docker/README.md`](docker/README.md) pour la documentation détaillée
2. Vérifier les logs avec `./docker-test.sh logs`
3. Redémarrer avec `./docker-test.sh stop && ./docker-test.sh start`
4. Nettoyer complètement avec `./docker-test.sh clean`