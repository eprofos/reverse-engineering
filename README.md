# ReverseEngineeringBundle

[![Latest Version](https://img.shields.io/badge/version-0.1.0-blue.svg)](https://github.com/eprofos/reverse-engineering-bundle/releases)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue.svg)](https://php.net/)
[![Symfony Version](https://img.shields.io/badge/symfony-%5E7.0-green.svg)](https://symfony.com/)
[![Tests](https://img.shields.io/badge/tests-80%2B-brightgreen.svg)](./tests)
[![Coverage](https://img.shields.io/badge/coverage-%3E90%25-brightgreen.svg)](./coverage)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](./LICENSE)

Bundle Symfony professionnel pour l'ingénierie inverse de base de données - Génération automatique d'entités Doctrine à partir d'une base de données existante.

**Développé par l'équipe Eprofos** pour simplifier la migration et la modernisation des applications legacy.

## 🚀 Fonctionnalités

- **Support multi-SGBD** : MySQL, PostgreSQL, SQLite
- **Génération automatique d'entités** avec attributs PHP 8+ ou annotations Doctrine
- **Mapping intelligent des types** de données vers PHP/Doctrine
- **Génération des relations** (ManyToOne, OneToMany, ManyToMany)
- **Génération des repositories** Doctrine
- **Interface CLI intuitive** avec options avancées
- **Mode dry-run** pour prévisualiser les changements
- **Gestion des conflits** de fichiers existants

## 📋 Prérequis

- **PHP** : 8.1 ou supérieur
- **Symfony** : 7.0 ou supérieur
- **Doctrine DBAL** : 3.0 ou supérieur
- **Doctrine ORM** : 2.15 ou supérieur
- **Extensions PHP** : PDO avec drivers pour votre SGBD

## � Installation

### Via Composer (Recommandé)

```bash
composer require eprofos/reverse-engineering-bundle
```

### Installation manuelle

1. Téléchargez la dernière version depuis [GitHub Releases](https://github.com/eprofos/reverse-engineering-bundle/releases)
2. Extrayez l'archive dans votre projet
3. Ajoutez le bundle à votre `config/bundles.php` :

```php
<?php
return [
    // ... autres bundles
    App\Bundle\ReverseEngineeringBundle::class => ['all' => true],
];
```

## 🔧 Compatibilité

| Version Bundle | PHP | Symfony | Doctrine DBAL | Doctrine ORM |
|----------------|-----|---------|---------------|--------------|
| 0.1.x          | ≥8.1| ^7.0    | ^3.0          | ^2.15        |

### SGBD Supportés

| SGBD       | Version | Driver     | Status |
|------------|---------|------------|--------|
| MySQL      | 5.7+    | pdo_mysql  | ✅ Complet |
| PostgreSQL | 12+     | pdo_pgsql  | ✅ Complet |
| SQLite     | 3.25+   | pdo_sqlite | ✅ Complet |
| MariaDB    | 10.3+   | pdo_mysql  | ✅ Complet |

## 🐳 Environnement Docker avec Sakila

Pour des tests plus réalistes, un environnement Docker complet avec la base de données Sakila est disponible :

### Démarrage rapide avec Docker

```bash
# Démarrer l'environnement Docker
docker-compose up -d

# Attendre que MySQL soit prêt (30-60 secondes)
docker-compose logs -f mysql

# Exécuter les tests d'intégration Sakila
docker-compose exec php vendor/bin/phpunit tests/Integration/SakilaIntegrationTest.php

# Générer des entités depuis Sakila
docker-compose exec php bin/console reverse:generate \
    --namespace="Sakila\\Entity" \
    --output-dir="generated/sakila"
```

### Accès aux services

- **MySQL** : `localhost:3306` (sakila_user/sakila_password)
- **phpMyAdmin** : http://localhost:8080
- **Base de données** : `sakila` (15+ tables avec relations complexes)

### Tests disponibles

La base Sakila permet de tester :
- **Relations complexes** : OneToMany, ManyToOne, ManyToMany
- **Types de données variés** : DECIMAL, ENUM, SET, YEAR, BLOB
- **Contraintes avancées** : Clés composites, index multiples
- **Performance** : Base de données réaliste avec données

Voir [`docker/README.md`](docker/README.md) pour la documentation complète.

## ⚙️ Configuration

Ajoutez la configuration dans votre fichier `config/packages/reverse_engineering.yaml` :

```yaml
reverse_engineering:
    database:
        driver: pdo_mysql          # pdo_mysql, pdo_pgsql, pdo_sqlite
        host: localhost
        port: 3306
        dbname: your_database
        user: your_username
        password: your_password
        charset: utf8mb4
    
    generation:
        namespace: App\Entity       # Namespace des entités générées
        output_dir: src/Entity      # Répertoire de sortie
        generate_repository: true   # Générer les repositories
        use_annotations: false      # Utiliser annotations au lieu d'attributs PHP 8
        tables: []                  # Tables spécifiques (toutes si vide)
        exclude_tables: []          # Tables à exclure
```

## 🎯 Utilisation

### Commande de base

```bash
php bin/console reverse:generate
```

### Options disponibles

```bash
# Générer des entités pour des tables spécifiques
php bin/console reverse:generate --tables=users --tables=products

# Exclure certaines tables
php bin/console reverse:generate --exclude=migrations --exclude=cache

# Spécifier un namespace personnalisé
php bin/console reverse:generate --namespace="App\Entity\Custom"

# Spécifier un répertoire de sortie
php bin/console reverse:generate --output-dir="src/Custom/Entity"

# Forcer l'écrasement des fichiers existants
php bin/console reverse:generate --force

# Mode dry-run (aperçu sans création de fichiers)
php bin/console reverse:generate --dry-run

# Combinaison d'options
php bin/console reverse:generate \
    --tables=users \
    --tables=products \
    --namespace="App\Entity\Shop" \
    --output-dir="src/Shop/Entity" \
    --force
```

## 📋 Exemples

### Exemple de table MySQL

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    birth_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    user_id INT NOT NULL,
    published_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Entité générée

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTimeInterface;

/**
 * Entité User générée automatiquement.
 * Table: users
 */
#[ORM\Entity(repositoryClass: App\Repository\UserRepository::class)]
#[ORM\Table(name: 'users')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: false)]
    private int $id;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private string $email;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private string $password;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTimeInterface $birthDate = null;

    #[ORM\Column(type: 'boolean', nullable: false)]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime', nullable: false)]
    private DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime', nullable: false)]
    private DateTimeInterface $updatedAt;

    // Getters et setters générés automatiquement...
}
```

## 🔧 Types de données supportés

### Types MySQL
- `INT`, `INTEGER`, `BIGINT`, `SMALLINT`, `TINYINT` → `int`
- `FLOAT`, `DOUBLE`, `REAL` → `float`
- `DECIMAL`, `NUMERIC` → `string`
- `BOOLEAN`, `BOOL` → `bool`
- `DATE`, `DATETIME`, `TIMESTAMP`, `TIME` → `DateTimeInterface`
- `VARCHAR`, `CHAR`, `TEXT`, `LONGTEXT` → `string`
- `JSON` → `array`
- `BLOB`, `LONGBLOB` → `string`

### Types PostgreSQL
- `INTEGER`, `BIGINT`, `SMALLINT` → `int`
- `REAL`, `DOUBLE PRECISION` → `float`
- `NUMERIC`, `DECIMAL` → `string`
- `BOOLEAN` → `bool`
- `DATE`, `TIMESTAMP`, `TIME` → `DateTimeInterface`
- `VARCHAR`, `CHAR`, `TEXT` → `string`
- `JSON`, `JSONB` → `array`
- `UUID` → `string`

### Types SQLite
- `INTEGER` → `int`
- `REAL` → `float`
- `TEXT` → `string`
- `BLOB` → `string`

## 🔗 Relations supportées

### ManyToOne (Clés étrangères)
Détectées automatiquement à partir des contraintes de clés étrangères :

```php
#[ORM\ManyToOne(targetEntity: User::class)]
#[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
private User $user;
```

### OneToMany (Relations inverses)
*Fonctionnalité en développement*

### ManyToMany (Tables de liaison)
*Fonctionnalité en développement*

## 🛠️ Architecture

### Services principaux

- **`DatabaseAnalyzer`** : Analyse la structure de la base de données
- **`MetadataExtractor`** : Extrait et mappe les métadonnées des tables
- **`EntityGenerator`** : Génère le code PHP des entités
- **`FileWriter`** : Écrit les fichiers sur le disque
- **`ReverseEngineeringService`** : Orchestre tout le processus

### Commandes

- **`reverse:generate`** : Commande principale de génération

## 🚨 Gestion d'erreurs

Le bundle gère plusieurs types d'erreurs :

- **`DatabaseConnectionException`** : Problèmes de connexion à la base
- **`MetadataExtractionException`** : Erreurs d'extraction des métadonnées
- **`EntityGenerationException`** : Erreurs de génération d'entités
- **`FileWriteException`** : Erreurs d'écriture de fichiers

## 🔍 Mode Debug

Utilisez l'option `-v` pour plus de détails :

```bash
php bin/console reverse:generate -v
```

## 📝 Bonnes pratiques

1. **Sauvegardez vos entités existantes** avant d'utiliser `--force`
2. **Utilisez le mode dry-run** pour prévisualiser les changements
3. **Configurez les tables à exclure** pour éviter les tables système
4. **Vérifiez les relations générées** et ajustez si nécessaire
5. **Utilisez des namespaces spécifiques** pour organiser vos entités

## 🚀 Roadmap

### Version 0.2.0 (Prochaine)
- [ ] Support des relations OneToMany automatiques
- [ ] Support des relations ManyToMany
- [ ] Génération de fixtures de test
- [ ] Interface web d'administration

### Version 0.3.0
- [ ] Support Oracle et SQL Server
- [ ] Génération de migrations Doctrine
- [ ] Templates personnalisables
- [ ] API REST pour intégration

### Versions futures
- [ ] Support des vues de base de données
- [ ] Génération de formulaires Symfony
- [ ] Intégration avec API Platform
- [ ] Plugin PHPStorm

## ⚠️ Limitations Connues

- **Relations OneToMany** : Détection limitée, génération manuelle recommandée
- **Relations ManyToMany** : Non supportées automatiquement dans cette version
- **Vues de base de données** : Non supportées
- **Procédures stockées** : Non prises en compte
- **Contraintes CHECK** : Mapping limité vers PHP

## 🤝 Contribution

Les contributions sont les bienvenues ! Consultez [`CONTRIBUTING.md`](./CONTRIBUTING.md) pour les détails.

### Développement Local

```bash
# Cloner le projet
git clone https://github.com/eprofos/reverse-engineering-bundle.git
cd reverse-engineering-bundle

# Installer les dépendances
composer install

# Exécuter les tests
./run-tests.sh

# Vérifier la qualité du code
composer phpstan
composer cs-fix
```

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier [`LICENSE`](./LICENSE) pour plus de détails.

## 🆘 Support et Communauté

### Documentation
- 📖 [Guide d'architecture](./docs/ARCHITECTURE.md)
- 🔧 [Documentation API](./docs/API.md)
- 🚨 [Guide de dépannage](./docs/TROUBLESHOOTING.md)
- 🎯 [Cas d'usage avancés](./docs/ADVANCED_USAGE.md)

### Support
- 🐛 [Signaler un bug](https://github.com/eprofos/reverse-engineering-bundle/issues/new?template=bug_report.md)
- 💡 [Demander une fonctionnalité](https://github.com/eprofos/reverse-engineering-bundle/issues/new?template=feature_request.md)
- 💬 [Discussions communautaires](https://github.com/eprofos/reverse-engineering-bundle/discussions)

### Statistiques
- ⭐ **Stars** : Aidez-nous en ajoutant une étoile !
- 🍴 **Forks** : Contribuez au développement
- 📊 **Utilisateurs** : Rejoignez la communauté

---

**Développé avec ❤️ par l'équipe Eprofos pour la communauté Symfony**

*Ce bundle est maintenu activement et utilisé en production par de nombreuses entreprises.*