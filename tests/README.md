# Tests du ReverseEngineeringBundle

Ce répertoire contient la suite de tests complète pour le ReverseEngineeringBundle, conçue pour assurer la qualité et la fiabilité du bundle d'ingénierie inverse de base de données.

## 📋 Structure des Tests

### Tests Unitaires (`tests/Unit/`)
Tests isolés pour chaque composant du bundle :

- **`Service/`** - Tests des services principaux
  - `DatabaseAnalyzerTest.php` - Tests de l'analyseur de base de données
  - `MetadataExtractorTest.php` - Tests de l'extracteur de métadonnées
  - `EntityGeneratorTest.php` - Tests du générateur d'entités
  - `FileWriterTest.php` - Tests de l'écriture de fichiers
  - `ReverseEngineeringServiceTest.php` - Tests du service principal

- **`Exception/`** - Tests des exceptions personnalisées
  - `ReverseEngineeringExceptionTest.php` - Exception de base
  - `DatabaseConnectionExceptionTest.php` - Exceptions de connexion BDD

### Tests d'Intégration (`tests/Integration/`)
Tests du processus complet de bout en bout :

- `ReverseEngineeringIntegrationTest.php` - Tests d'intégration complète avec base de données réelle

### Tests de Commande (`tests/Command/`)
Tests de l'interface en ligne de commande :

- `ReverseGenerateCommandTest.php` - Tests de la commande CLI

### Tests de Performance (`tests/Performance/`)
Tests de performance et de charge :

- `ReverseEngineeringPerformanceTest.php` - Tests de performance avec grandes tables et nombreuses entités

## 🚀 Exécution des Tests

### Méthode Rapide
```bash
# Exécuter tous les tests avec le script automatisé
./run-tests.sh
```

### Méthodes Manuelles

#### Tous les tests
```bash
vendor/bin/phpunit
```

#### Tests par catégorie
```bash
# Tests unitaires uniquement
vendor/bin/phpunit --testsuite=Unit

# Tests d'intégration uniquement
vendor/bin/phpunit --testsuite=Integration

# Tests de performance uniquement
vendor/bin/phpunit --testsuite=Performance

# Tests de commande uniquement
vendor/bin/phpunit --testsuite=Command

# Tests d'exceptions uniquement
vendor/bin/phpunit --testsuite=Exception
```

#### Tests spécifiques
```bash
# Test d'un service spécifique
vendor/bin/phpunit tests/Unit/Service/DatabaseAnalyzerTest.php

# Test d'une méthode spécifique
vendor/bin/phpunit --filter testAnalyzeTablesWithIncludeFilter
```

## 📊 Couverture de Code

### Génération des rapports
```bash
# Rapport HTML (recommandé)
vendor/bin/phpunit --coverage-html=coverage/html

# Rapport texte
vendor/bin/phpunit --coverage-text

# Rapport Clover (pour CI/CD)
vendor/bin/phpunit --coverage-clover=coverage/clover.xml
```

### Objectifs de Couverture
- **Couverture globale** : > 90%
- **Services principaux** : > 95%
- **Exceptions** : 100%
- **Commandes** : > 85%

## 🧪 Types de Tests

### 1. Tests Unitaires
- **Objectif** : Tester chaque composant isolément
- **Mocking** : Utilisation extensive de mocks pour les dépendances
- **Couverture** : Tous les chemins d'exécution et cas d'erreur

### 2. Tests d'Intégration
- **Objectif** : Tester le processus complet
- **Base de données** : SQLite en mémoire pour les tests
- **Scénarios** : Génération complète d'entités avec relations

### 3. Tests de Performance
- **Objectif** : Valider les performances sous charge
- **Métriques** :
  - Temps d'exécution
  - Utilisation mémoire
  - Traitement de grandes tables (50+ colonnes)
  - Traitement de nombreuses tables (100+)

### 4. Tests de Commande
- **Objectif** : Valider l'interface CLI
- **Couverture** : Toutes les options et cas d'erreur
- **Simulation** : Utilisation de CommandTester

## 🛠️ Configuration des Tests

### Fichiers de Configuration
- `phpunit.xml` - Configuration principale PHPUnit
- `tests/bootstrap.php` - Bootstrap des tests
- `tests/TestHelper.php` - Utilitaires pour les tests

### Variables d'Environnement
```bash
# Base de données de test (définie dans phpunit.xml)
DATABASE_URL=sqlite:///:memory:

# Mode debug pour les tests
APP_DEBUG=1
APP_ENV=test
```

## 📝 Conventions de Test

### Nommage
- **Classes** : `{ClasseName}Test.php`
- **Méthodes** : `test{MethodName}{Scenario}()`
- **Exemples** :
  - `testGenerateEntitySuccess()`
  - `testGenerateEntityThrowsExceptionOnError()`

### Structure des Tests
```php
public function testMethodNameScenario(): void
{
    // Arrange - Préparer les données
    $input = 'test data';
    
    // Act - Exécuter l'action
    $result = $this->service->method($input);
    
    // Assert - Vérifier le résultat
    $this->assertEquals('expected', $result);
}
```

### Assertions Recommandées
- `assertEquals()` - Égalité de valeurs
- `assertSame()` - Identité d'objets
- `assertInstanceOf()` - Type d'objet
- `assertArrayHasKey()` - Présence de clé
- `assertStringContains()` - Contenu de chaîne
- `expectException()` - Exceptions attendues

## 🔧 Outils de Développement

### Analyse Statique
```bash
# PHPStan (niveau 8)
vendor/bin/phpstan analyse src --level=8

# PHP CS Fixer
vendor/bin/php-cs-fixer fix
```

### Debugging des Tests
```bash
# Mode verbose
vendor/bin/phpunit --verbose

# Affichage des erreurs détaillées
vendor/bin/phpunit --debug

# Test spécifique avec output
vendor/bin/phpunit --filter testMethodName --verbose
```

## 📈 Métriques de Qualité

### Objectifs de Performance
- **Analyse de 100 tables** : < 1 seconde
- **Génération de 50 entités** : < 10 secondes
- **Table avec 50 colonnes** : < 2 secondes
- **Utilisation mémoire** : < 50MB pour 30 entités

### Critères de Qualité
- ✅ Tous les tests passent
- ✅ Couverture > 90%
- ✅ Aucune violation PHPStan niveau 8
- ✅ Code formaté selon PSR-12
- ✅ Performance dans les limites définies

## 🚨 Résolution de Problèmes

### Tests qui Échouent
1. Vérifier les dépendances : `composer install`
2. Nettoyer le cache : `rm -rf .phpunit.cache`
3. Vérifier la configuration de la base de données
4. Exécuter les tests en mode verbose pour plus de détails

### Problèmes de Performance
1. Vérifier la mémoire disponible
2. Optimiser les requêtes de test
3. Réduire la taille des jeux de données de test

### Problèmes de Couverture
1. Vérifier que Xdebug est installé et activé
2. S'assurer que tous les fichiers sont inclus dans la couverture
3. Ajouter des tests pour les branches non couvertes

## 📚 Ressources

- [Documentation PHPUnit](https://phpunit.de/documentation.html)
- [Mocking avec PHPUnit](https://phpunit.de/manual/current/en/test-doubles.html)
- [Doctrine DBAL Testing](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/testing.html)
- [Symfony Console Testing](https://symfony.com/doc/current/console.html#testing-commands)

---

**Note** : Cette suite de tests est conçue pour être exécutée dans un environnement de développement. Pour la production, utilisez uniquement les tests de validation nécessaires.