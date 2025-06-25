# Guide de Contribution - ReverseEngineeringBundle

Merci de votre intérêt pour contribuer au ReverseEngineeringBundle ! Ce guide vous aidera à comprendre comment participer efficacement au développement du projet.

## 🎯 Comment Contribuer

### 🐛 Signaler des Bugs

1. **Vérifiez les issues existantes** pour éviter les doublons
2. **Utilisez le template de bug report** disponible sur GitHub
3. **Fournissez des informations détaillées** :
   - Version du bundle
   - Version de PHP et Symfony
   - SGBD utilisé et version
   - Étapes pour reproduire le problème
   - Comportement attendu vs observé
   - Logs d'erreur complets

### 💡 Proposer des Fonctionnalités

1. **Ouvrez une discussion** avant de commencer le développement
2. **Utilisez le template de feature request**
3. **Décrivez clairement** :
   - Le problème que cela résout
   - La solution proposée
   - Les alternatives considérées
   - L'impact sur l'API existante

### 🔧 Contribuer au Code

1. **Fork** le repository
2. **Créez une branche** pour votre fonctionnalité :
   ```bash
   git checkout -b feature/ma-nouvelle-fonctionnalite
   ```
3. **Développez** en suivant nos standards
4. **Testez** votre code
5. **Soumettez** une Pull Request

## 🏗️ Architecture du Projet

### Structure des Répertoires

```
src/
├── Bundle/                 # Bundle principal Symfony
├── Command/               # Commandes CLI
├── DependencyInjection/   # Configuration du container
├── Exception/             # Exceptions personnalisées
├── Resources/             # Templates et configuration
│   ├── config/           # Configuration des services
│   └── templates/        # Templates Twig
└── Service/              # Services métier
    ├── DatabaseAnalyzer.php      # Analyse de la BDD
    ├── MetadataExtractor.php     # Extraction métadonnées
    ├── EntityGenerator.php       # Génération entités
    ├── FileWriter.php           # Écriture fichiers
    └── ReverseEngineeringService.php # Orchestration

tests/
├── Unit/                 # Tests unitaires
├── Integration/          # Tests d'intégration
├── Performance/          # Tests de performance
└── Command/             # Tests des commandes CLI
```

### Services Principaux

1. **`DatabaseAnalyzer`** : Analyse la structure de la base de données
   - Connexion et validation
   - Listage des tables
   - Extraction des métadonnées de schéma

2. **`MetadataExtractor`** : Extrait et mappe les métadonnées
   - Mapping des types de données
   - Détection des relations
   - Normalisation des noms

3. **`EntityGenerator`** : Génère le code PHP des entités
   - Utilisation de templates Twig
   - Génération des propriétés et méthodes
   - Support attributs PHP 8+ et annotations

4. **`FileWriter`** : Écrit les fichiers sur le disque
   - Gestion des conflits
   - Validation des permissions
   - Création des répertoires

5. **`ReverseEngineeringService`** : Orchestre tout le processus
   - Coordination des services
   - Gestion des options
   - Gestion d'erreurs globale

## 📋 Standards de Développement

### Standards de Code

- **PSR-12** : Standard de style de code PHP
- **PHPStan niveau 8** : Analyse statique stricte
- **PHP 8.1+** : Utilisation des fonctionnalités modernes
- **Types stricts** : `declare(strict_types=1)` obligatoire
- **Documentation** : PHPDoc complète pour toutes les méthodes publiques

### Conventions de Nommage

#### Classes
```php
// Services
class DatabaseAnalyzer
class MetadataExtractor

// Exceptions
class DatabaseConnectionException extends ReverseEngineeringException

// Commands
class ReverseGenerateCommand extends Command
```

#### Méthodes
```php
// Actions principales
public function generateEntities(array $options = []): array
public function extractTableMetadata(string $tableName): array

// Validation/Test
public function validateConnection(): bool
public function testDatabaseConnection(): bool

// Getters/Setters
public function getTableName(): string
public function setOutputDirectory(string $dir): void
```

#### Variables
```php
// CamelCase pour les variables
$tableName = 'users';
$entityMetadata = [];
$outputDirectory = '/path/to/entities';

// Snake_case pour les clés de configuration
$config = [
    'output_dir' => '/path',
    'generate_repository' => true,
    'use_annotations' => false,
];
```

### Gestion d'Erreurs

#### Hiérarchie des Exceptions
```php
ReverseEngineeringException (base)
├── DatabaseConnectionException
├── MetadataExtractionException
├── EntityGenerationException
└── FileWriteException
```

#### Bonnes Pratiques
```php
// ✅ Bon : Exception spécifique avec contexte
throw new EntityGenerationException(
    "Impossible de générer l'entité pour la table '{$tableName}' : {$reason}",
    0,
    $previousException
);

// ❌ Mauvais : Exception générique
throw new Exception('Erreur');
```

## 🧪 Tests et Qualité

### Types de Tests

1. **Tests Unitaires** (`tests/Unit/`)
   - Un test par service/classe
   - Mocking des dépendances
   - Couverture de tous les chemins d'exécution

2. **Tests d'Intégration** (`tests/Integration/`)
   - Tests de bout en bout
   - Base de données réelle (SQLite en mémoire)
   - Scénarios utilisateur complets

3. **Tests de Performance** (`tests/Performance/`)
   - Benchmarks avec grandes tables
   - Mesure de la mémoire utilisée
   - Validation des limites de performance

4. **Tests de Commande** (`tests/Command/`)
   - Tests CLI avec `CommandTester`
   - Validation des options et arguments
   - Tests des codes de retour

### Exécution des Tests

```bash
# Tous les tests
./run-tests.sh

# Tests par catégorie
vendor/bin/phpunit --testsuite=Unit
vendor/bin/phpunit --testsuite=Integration
vendor/bin/phpunit --testsuite=Performance

# Avec couverture
vendor/bin/phpunit --coverage-html=coverage/html

# Test spécifique
vendor/bin/phpunit tests/Unit/Service/DatabaseAnalyzerTest.php
```

### Objectifs de Qualité

- **Couverture de code** : > 90%
- **PHPStan** : Niveau 8 sans erreur
- **Tests** : Tous les tests doivent passer
- **Performance** : Respecter les benchmarks définis

### Outils de Qualité

```bash
# Analyse statique
composer phpstan

# Correction du style de code
composer cs-fix

# Validation complète
./scripts/validate.sh
```

## 🔄 Processus de Pull Request

### Checklist Avant Soumission

- [ ] **Code** : Respecte les standards PSR-12
- [ ] **Tests** : Tous les tests passent
- [ ] **Couverture** : Nouvelles fonctionnalités testées
- [ ] **PHPStan** : Niveau 8 sans erreur
- [ ] **Documentation** : PHPDoc à jour
- [ ] **CHANGELOG** : Entrée ajoutée si nécessaire
- [ ] **Commit** : Messages clairs et descriptifs

### Format des Messages de Commit

```bash
# Format : type(scope): description

# Types :
feat(generator): ajout support des relations OneToMany
fix(analyzer): correction détection clés étrangères PostgreSQL
docs(readme): mise à jour exemples d'utilisation
test(unit): ajout tests pour MetadataExtractor
refactor(service): simplification de l'architecture
perf(analyzer): optimisation requêtes pour grandes tables
```

### Processus de Review

1. **Validation automatique** : CI/CD vérifie la qualité
2. **Review par les mainteneurs** : Code et architecture
3. **Tests manuels** : Validation fonctionnelle
4. **Merge** : Après approbation

## 🎯 Domaines de Contribution

### Priorité Haute
- **Relations OneToMany/ManyToMany** : Détection et génération automatiques
- **Support Oracle/SQL Server** : Nouveaux drivers de base de données
- **Performance** : Optimisation pour très grandes bases de données
- **Templates** : Personnalisation avancée des entités générées

### Priorité Moyenne
- **Interface Web** : Administration via navigateur
- **Migrations Doctrine** : Génération automatique
- **API REST** : Intégration avec d'autres outils
- **Fixtures** : Génération de données de test

### Priorité Basse
- **Plugin IDE** : Intégration PHPStorm/VSCode
- **Support des vues** : Génération d'entités read-only
- **Procédures stockées** : Mapping vers des services

## 📚 Ressources

### Documentation
- [Architecture détaillée](./docs/ARCHITECTURE.md)
- [Documentation API](./docs/API.md)
- [Guide de dépannage](./docs/TROUBLESHOOTING.md)
- [Cas d'usage avancés](./docs/ADVANCED_USAGE.md)

### Outils de Développement
- [PHPUnit](https://phpunit.de/) - Framework de tests
- [PHPStan](https://phpstan.org/) - Analyse statique
- [PHP CS Fixer](https://cs.symfony.com/) - Style de code
- [Doctrine DBAL](https://www.doctrine-project.org/projects/dbal.html) - Abstraction base de données

### Communauté
- [Issues GitHub](https://github.com/eprofos/reverse-engineering-bundle/issues)
- [Discussions](https://github.com/eprofos/reverse-engineering-bundle/discussions)
- [Pull Requests](https://github.com/eprofos/reverse-engineering-bundle/pulls)

## 🤝 Code de Conduite

Ce projet adhère au [Code de Conduite Contributor Covenant](https://www.contributor-covenant.org/fr/version/2/1/code_of_conduct/).
En participant, vous vous engagez à respecter ce code.

### Nos Engagements

- **Respect** : Traiter tous les contributeurs avec respect
- **Inclusion** : Accueillir toutes les perspectives et expériences
- **Collaboration** : Travailler ensemble vers des objectifs communs
- **Professionnalisme** : Maintenir un environnement professionnel

## 📞 Contact

- **Mainteneur principal** : Eprofos Team
- **Issues** : [GitHub Issues](https://github.com/eprofos/reverse-engineering-bundle/issues)
- **Discussions** : [GitHub Discussions](https://github.com/eprofos/reverse-engineering-bundle/discussions)

---

**Merci de contribuer au ReverseEngineeringBundle ! Ensemble, nous construisons un outil puissant pour la communauté Symfony.** 🚀