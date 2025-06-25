# Changelog

Toutes les modifications notables de ce projet seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Versioning Sémantique](https://semver.org/lang/fr/).

## [Unreleased]

### À venir
- Support des relations OneToMany automatiques
- Support des relations ManyToMany
- Génération de fixtures de test
- Interface web d'administration

## [0.1.0] - 2025-06-25

### Added
- **Génération automatique d'entités Doctrine** à partir de bases de données existantes
- **Support multi-SGBD** : MySQL, PostgreSQL, SQLite, MariaDB
- **Interface CLI robuste** avec commande `reverse:generate`
- **Options avancées** :
  - Sélection de tables spécifiques (`--tables`)
  - Exclusion de tables (`--exclude`)
  - Namespace personnalisé (`--namespace`)
  - Répertoire de sortie personnalisé (`--output-dir`)
  - Mode dry-run (`--dry-run`)
  - Force l'écrasement (`--force`)
- **Mapping intelligent des types** de données vers PHP/Doctrine
- **Génération des relations ManyToOne** automatique depuis les clés étrangères
- **Génération des repositories** Doctrine optionnelle
- **Support des attributs PHP 8+** et annotations Doctrine
- **Gestion des conflits** de fichiers existants
- **Architecture modulaire** avec 5 services principaux :
  - `DatabaseAnalyzer` - Analyse de la structure de base de données
  - `MetadataExtractor` - Extraction et mapping des métadonnées
  - `EntityGenerator` - Génération du code PHP des entités
  - `FileWriter` - Écriture sécurisée des fichiers
  - `ReverseEngineeringService` - Orchestration du processus
- **Suite de tests complète** :
  - 80+ tests unitaires, d'intégration et de performance
  - Couverture de code > 90%
  - Tests de compatibilité multi-SGBD
  - Tests de performance avec grandes tables
- **Gestion d'erreurs robuste** avec exceptions spécialisées :
  - `DatabaseConnectionException`
  - `MetadataExtractionException`
  - `EntityGenerationException`
  - `FileWriteException`
- **Configuration flexible** via fichiers YAML
- **Documentation complète** avec exemples d'utilisation
- **Templates Twig** personnalisables pour la génération d'entités

### Technical Details
- **PHP** : Compatibilité 8.1+
- **Symfony** : Compatible 7.0+
- **Doctrine DBAL** : Support 3.0+
- **Doctrine ORM** : Support 2.15+
- **Qualité du code** : PHPStan niveau 8, PSR-12
- **Tests** : PHPUnit 10+, couverture HTML/Clover

### Database Support
- **MySQL** 5.7+ avec driver `pdo_mysql`
- **PostgreSQL** 12+ avec driver `pdo_pgsql`
- **SQLite** 3.25+ avec driver `pdo_sqlite`
- **MariaDB** 10.3+ avec driver `pdo_mysql`

### Supported Data Types
#### MySQL/MariaDB
- Types numériques : `INT`, `BIGINT`, `DECIMAL`, `FLOAT`, `DOUBLE`
- Types texte : `VARCHAR`, `CHAR`, `TEXT`, `LONGTEXT`
- Types date/heure : `DATE`, `DATETIME`, `TIMESTAMP`, `TIME`
- Types spéciaux : `BOOLEAN`, `JSON`, `BLOB`

#### PostgreSQL
- Types numériques : `INTEGER`, `BIGINT`, `NUMERIC`, `REAL`, `DOUBLE PRECISION`
- Types texte : `VARCHAR`, `CHAR`, `TEXT`
- Types date/heure : `DATE`, `TIMESTAMP`, `TIME`
- Types spéciaux : `BOOLEAN`, `JSON`, `JSONB`, `UUID`

#### SQLite
- Types de base : `INTEGER`, `REAL`, `TEXT`, `BLOB`

### Known Limitations
- Relations OneToMany : Détection limitée, génération manuelle recommandée
- Relations ManyToMany : Non supportées automatiquement
- Vues de base de données : Non supportées
- Procédures stockées : Non prises en compte
- Contraintes CHECK : Mapping limité vers PHP

---

## Format des Versions

Ce projet utilise le [Versioning Sémantique](https://semver.org/lang/fr/) :

- **MAJOR** : Changements incompatibles de l'API
- **MINOR** : Ajout de fonctionnalités rétrocompatibles
- **PATCH** : Corrections de bugs rétrocompatibles

## Types de Changements

- **Added** : Nouvelles fonctionnalités
- **Changed** : Modifications de fonctionnalités existantes
- **Deprecated** : Fonctionnalités bientôt supprimées
- **Removed** : Fonctionnalités supprimées
- **Fixed** : Corrections de bugs
- **Security** : Corrections de sécurité

## Liens

- [Unreleased]: https://github.com/eprofos/reverse-engineering-bundle/compare/v0.1.0...HEAD
- [0.1.0]: https://github.com/eprofos/reverse-engineering-bundle/releases/tag/v0.1.0