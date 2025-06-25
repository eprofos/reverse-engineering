#!/bin/bash

# Script pour exécuter la suite de tests complète du ReverseEngineeringBundle

set -e

echo "🧪 Exécution de la suite de tests ReverseEngineeringBundle"
echo "========================================================="

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

# Vérifier que PHPUnit est installé
if ! command -v vendor/bin/phpunit &> /dev/null; then
    print_error "PHPUnit n'est pas installé. Exécutez 'composer install' d'abord."
    exit 1
fi

# Créer le répertoire de couverture s'il n'existe pas
mkdir -p coverage

print_status "Nettoyage des fichiers de cache..."
rm -rf .phpunit.cache
rm -rf coverage/*

echo ""
print_status "1. Exécution des tests unitaires..."
echo "-----------------------------------"
vendor/bin/phpunit --testsuite=Unit --colors=always

echo ""
print_status "2. Exécution des tests d'intégration..."
echo "---------------------------------------"
vendor/bin/phpunit --testsuite=Integration --colors=always

echo ""
print_status "3. Exécution des tests de commande..."
echo "-------------------------------------"
vendor/bin/phpunit --testsuite=Command --colors=always

echo ""
print_status "4. Exécution des tests d'exceptions..."
echo "-------------------------------------"
vendor/bin/phpunit --testsuite=Exception --colors=always

echo ""
print_status "5. Exécution des tests de performance..."
echo "---------------------------------------"
vendor/bin/phpunit --testsuite=Performance --colors=always

echo ""
print_status "6. Génération du rapport de couverture..."
echo "-----------------------------------------"
vendor/bin/phpunit --coverage-html=coverage/html --coverage-text --coverage-clover=coverage/clover.xml

echo ""
print_success "✅ Tous les tests ont été exécutés avec succès!"

# Afficher le résumé de la couverture
if [ -f "coverage/coverage.txt" ]; then
    echo ""
    print_status "📊 Résumé de la couverture de code:"
    echo "-----------------------------------"
    tail -n 10 coverage/coverage.txt
fi

echo ""
print_status "📁 Rapports générés:"
echo "  - Rapport HTML: coverage/html/index.html"
echo "  - Rapport Clover: coverage/clover.xml"
echo "  - Rapport texte: coverage/coverage.txt"

echo ""
print_success "🎉 Suite de tests terminée!"