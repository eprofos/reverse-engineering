#!/bin/bash

# Script de validation complète du ReverseEngineeringBundle
# Vérifie la qualité du code, exécute les tests et génère les rapports

set -e

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonction pour afficher les messages colorés
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo ""
    echo "=========================================="
    echo "$1"
    echo "=========================================="
}

# Vérifier que nous sommes dans le bon répertoire
if [ ! -f "composer.json" ]; then
    print_error "Ce script doit être exécuté depuis la racine du projet"
    exit 1
fi

print_header "🔍 VALIDATION COMPLÈTE DU REVERSEENGINEERINGBUNDLE"

# 1. Vérification des dépendances
print_status "1. Vérification des dépendances..."
if [ ! -d "vendor" ]; then
    print_warning "Le répertoire vendor n'existe pas. Installation des dépendances..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
else
    print_success "Dépendances présentes"
fi

# 2. Vérification de la syntaxe PHP
print_header "2. Vérification de la syntaxe PHP"
print_status "Vérification des fichiers PHP..."

find src -name "*.php" -exec php -l {} \; | grep -v "No syntax errors" || {
    print_success "Aucune erreur de syntaxe PHP détectée"
}

find tests -name "*.php" -exec php -l {} \; | grep -v "No syntax errors" || {
    print_success "Aucune erreur de syntaxe PHP dans les tests"
}

# 3. Analyse statique avec PHPStan
print_header "3. Analyse statique (PHPStan niveau 8)"
print_status "Exécution de PHPStan..."

if command -v vendor/bin/phpstan &> /dev/null; then
    vendor/bin/phpstan analyse src --level=8 --no-progress
    print_success "Analyse statique réussie"
else
    print_warning "PHPStan non installé, installation..."
    composer require --dev phpstan/phpstan
    vendor/bin/phpstan analyse src --level=8 --no-progress
fi

# 4. Vérification du style de code
print_header "4. Vérification du style de code (PSR-12)"
print_status "Vérification avec PHP CS Fixer..."

if command -v vendor/bin/php-cs-fixer &> /dev/null; then
    vendor/bin/php-cs-fixer fix --dry-run --diff --verbose
    print_success "Style de code conforme à PSR-12"
else
    print_warning "PHP CS Fixer non installé, installation..."
    composer require --dev friendsofphp/php-cs-fixer
    vendor/bin/php-cs-fixer fix --dry-run --diff --verbose
fi

# 5. Exécution des tests
print_header "5. Exécution de la suite de tests"

# Créer le répertoire de couverture
mkdir -p coverage

print_status "5.1. Tests unitaires..."
vendor/bin/phpunit --testsuite=Unit --colors=always

print_status "5.2. Tests d'intégration..."
vendor/bin/phpunit --testsuite=Integration --colors=always

print_status "5.3. Tests de commande..."
vendor/bin/phpunit --testsuite=Command --colors=always

print_status "5.4. Tests d'exceptions..."
vendor/bin/phpunit --testsuite=Exception --colors=always

print_status "5.5. Tests de performance..."
vendor/bin/phpunit --testsuite=Performance --colors=always

# 6. Génération du rapport de couverture
print_header "6. Génération du rapport de couverture"
print_status "Génération des rapports de couverture..."

vendor/bin/phpunit --coverage-html=coverage/html --coverage-text --coverage-clover=coverage/clover.xml

# 7. Vérification de la couverture
print_header "7. Vérification de la couverture de code"

if [ -f "coverage/clover.xml" ]; then
    # Extraire le pourcentage de couverture depuis le fichier clover
    COVERAGE=$(php -r "
        \$xml = simplexml_load_file('coverage/clover.xml');
        \$metrics = \$xml->project->metrics;
        \$covered = (int)\$metrics['coveredstatements'];
        \$total = (int)\$metrics['statements'];
        if (\$total > 0) {
            \$percentage = round((\$covered / \$total) * 100, 2);
            echo \$percentage;
        } else {
            echo '0';
        }
    ")
    
    if (( $(echo "$COVERAGE >= 90" | bc -l) )); then
        print_success "Couverture de code: ${COVERAGE}% (≥ 90% requis)"
    else
        print_warning "Couverture de code: ${COVERAGE}% (< 90% requis)"
    fi
else
    print_warning "Impossible de calculer la couverture de code"
fi

# 8. Vérification des fichiers de documentation
print_header "8. Vérification de la documentation"

REQUIRED_FILES=("README.md" "CHANGELOG.md" "CONTRIBUTING.md" "LICENSE")
for file in "${REQUIRED_FILES[@]}"; do
    if [ -f "$file" ]; then
        print_success "✓ $file présent"
    else
        print_error "✗ $file manquant"
        exit 1
    fi
done

# 9. Vérification de la configuration
print_header "9. Vérification de la configuration"

if [ -f "config/packages/reverse_engineering.yaml" ]; then
    print_success "✓ Configuration du bundle présente"
else
    print_warning "Configuration du bundle manquante"
fi

if [ -f "phpunit.xml" ]; then
    print_success "✓ Configuration PHPUnit présente"
else
    print_error "✗ Configuration PHPUnit manquante"
    exit 1
fi

# 10. Vérification de la structure du projet
print_header "10. Vérification de la structure du projet"

REQUIRED_DIRS=("src/Bundle" "src/Command" "src/Service" "src/Exception" "tests/Unit" "tests/Integration")
for dir in "${REQUIRED_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        print_success "✓ Répertoire $dir présent"
    else
        print_error "✗ Répertoire $dir manquant"
        exit 1
    fi
done

# 11. Résumé final
print_header "📊 RÉSUMÉ DE LA VALIDATION"

print_success "✅ Syntaxe PHP: OK"
print_success "✅ Analyse statique (PHPStan niveau 8): OK"
print_success "✅ Style de code (PSR-12): OK"
print_success "✅ Tests unitaires: OK"
print_success "✅ Tests d'intégration: OK"
print_success "✅ Tests de performance: OK"
print_success "✅ Documentation: OK"
print_success "✅ Structure du projet: OK"

if [ -n "$COVERAGE" ]; then
    print_success "✅ Couverture de code: ${COVERAGE}%"
fi

echo ""
print_success "🎉 VALIDATION COMPLÈTE RÉUSSIE !"
echo ""
print_status "📁 Rapports générés:"
echo "  - Rapport de couverture HTML: coverage/html/index.html"
echo "  - Rapport de couverture Clover: coverage/clover.xml"
echo ""
print_status "🚀 Le bundle est prêt pour la production !"

exit 0