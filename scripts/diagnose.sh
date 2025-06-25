#!/bin/bash

# Script de diagnostic pour ReverseEngineeringBundle
# Vérifie l'environnement et la configuration

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

print_header "🔍 DIAGNOSTIC REVERSEENGINEERINGBUNDLE"

# 1. Vérification de l'environnement PHP
print_status "1. Vérification de l'environnement PHP..."
echo "Version PHP: $(php --version | head -n 1)"

# Vérifier les extensions requises
REQUIRED_EXTENSIONS=("pdo" "json" "mbstring" "xml" "ctype" "iconv")
MISSING_EXTENSIONS=()

for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if php -m | grep -q "^$ext$"; then
        print_success "✓ Extension $ext présente"
    else
        print_error "✗ Extension $ext manquante"
        MISSING_EXTENSIONS+=("$ext")
    fi
done

# Vérifier les drivers de base de données
DB_DRIVERS=("pdo_mysql" "pdo_pgsql" "pdo_sqlite")
AVAILABLE_DRIVERS=()

for driver in "${DB_DRIVERS[@]}"; do
    if php -m | grep -q "$driver"; then
        print_success "✓ Driver $driver disponible"
        AVAILABLE_DRIVERS+=("$driver")
    else
        print_warning "⚠ Driver $driver non disponible"
    fi
done

if [ ${#AVAILABLE_DRIVERS[@]} -eq 0 ]; then
    print_error "Aucun driver de base de données disponible!"
fi

# 2. Vérification de Composer
print_header "2. Vérification de Composer"
if command -v composer &> /dev/null; then
    echo "Version Composer: $(composer --version)"
    print_success "✓ Composer installé"
else
    print_error "✗ Composer non trouvé"
fi

# 3. Vérification du bundle
print_header "3. Vérification du bundle"

if [ -f "composer.json" ]; then
    print_success "✓ Fichier composer.json présent"
    
    # Vérifier si le bundle est installé
    if composer show eprofos/reverse-engineering-bundle &> /dev/null; then
        BUNDLE_VERSION=$(composer show eprofos/reverse-engineering-bundle | grep "versions" | awk '{print $3}')
        print_success "✓ Bundle installé (version: $BUNDLE_VERSION)"
    else
        print_warning "⚠ Bundle non installé via Composer"
    fi
else
    print_error "✗ Fichier composer.json non trouvé"
fi

# Vérifier la structure du projet
REQUIRED_DIRS=("src/Bundle" "src/Service" "src/Command" "src/Exception")
for dir in "${REQUIRED_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        print_success "✓ Répertoire $dir présent"
    else
        print_error "✗ Répertoire $dir manquant"
    fi
done

# 4. Vérification de la configuration
print_header "4. Vérification de la configuration"

if [ -f "config/packages/reverse_engineering.yaml" ]; then
    print_success "✓ Fichier de configuration présent"
    
    # Vérifier la syntaxe YAML
    if command -v php &> /dev/null && [ -f "bin/console" ]; then
        if php bin/console lint:yaml config/packages/reverse_engineering.yaml &> /dev/null; then
            print_success "✓ Configuration YAML valide"
        else
            print_error "✗ Erreur de syntaxe dans la configuration YAML"
        fi
    fi
else
    print_warning "⚠ Fichier de configuration manquant"
    echo "  Créez le fichier config/packages/reverse_engineering.yaml"
fi

# Vérifier les bundles Symfony
if [ -f "config/bundles.php" ]; then
    if grep -q "ReverseEngineeringBundle" config/bundles.php; then
        print_success "✓ Bundle enregistré dans config/bundles.php"
    else
        print_warning "⚠ Bundle non enregistré dans config/bundles.php"
    fi
fi

# 5. Test de la commande CLI
print_header "5. Test de la commande CLI"

if [ -f "bin/console" ]; then
    print_success "✓ Console Symfony présente"
    
    # Tester si la commande est disponible
    if php bin/console list | grep -q "reverse:generate"; then
        print_success "✓ Commande reverse:generate disponible"
    else
        print_error "✗ Commande reverse:generate non trouvée"
    fi
else
    print_error "✗ Console Symfony non trouvée"
fi

# 6. Test de connexion base de données
print_header "6. Test de connexion base de données"

if [ -f "bin/console" ] && [ -f "config/packages/reverse_engineering.yaml" ]; then
    print_status "Test de connexion..."
    
    # Essayer un dry-run pour tester la connexion
    if php bin/console reverse:generate --dry-run --tables=non_existent_table 2>&1 | grep -q "Connection"; then
        print_error "✗ Erreur de connexion à la base de données"
        echo "  Vérifiez vos paramètres de connexion"
    else
        print_success "✓ Connexion à la base de données OK"
    fi
else
    print_warning "⚠ Impossible de tester la connexion (configuration manquante)"
fi

# 7. Vérification des permissions
print_header "7. Vérification des permissions"

# Vérifier les permissions du répertoire src/Entity
if [ -d "src/Entity" ]; then
    if [ -w "src/Entity" ]; then
        print_success "✓ Répertoire src/Entity accessible en écriture"
    else
        print_error "✗ Répertoire src/Entity non accessible en écriture"
        echo "  Exécutez: chmod 755 src/Entity"
    fi
else
    print_warning "⚠ Répertoire src/Entity n'existe pas"
    echo "  Il sera créé automatiquement lors de la génération"
fi

# 8. Vérification des tests
print_header "8. Vérification des tests"

if [ -f "phpunit.xml" ]; then
    print_success "✓ Configuration PHPUnit présente"
    
    if [ -d "tests" ]; then
        TEST_COUNT=$(find tests -name "*Test.php" | wc -l)
        print_success "✓ $TEST_COUNT fichiers de test trouvés"
    else
        print_warning "⚠ Répertoire tests manquant"
    fi
else
    print_warning "⚠ Configuration PHPUnit manquante"
fi

# 9. Vérification de la mémoire PHP
print_header "9. Vérification des limites PHP"

MEMORY_LIMIT=$(php -r "echo ini_get('memory_limit');")
MAX_EXECUTION_TIME=$(php -r "echo ini_get('max_execution_time');")

echo "Limite mémoire: $MEMORY_LIMIT"
echo "Temps d'exécution max: ${MAX_EXECUTION_TIME}s"

# Convertir la limite mémoire en bytes pour comparaison
MEMORY_BYTES=$(php -r "
    \$limit = '$MEMORY_LIMIT';
    \$bytes = (int)\$limit;
    if (strpos(\$limit, 'K') !== false) \$bytes *= 1024;
    if (strpos(\$limit, 'M') !== false) \$bytes *= 1024 * 1024;
    if (strpos(\$limit, 'G') !== false) \$bytes *= 1024 * 1024 * 1024;
    echo \$bytes;
")

if [ "$MEMORY_BYTES" -ge 134217728 ]; then  # 128MB
    print_success "✓ Limite mémoire suffisante"
else
    print_warning "⚠ Limite mémoire faible (recommandé: 128M+)"
fi

if [ "$MAX_EXECUTION_TIME" -ge 60 ] || [ "$MAX_EXECUTION_TIME" -eq 0 ]; then
    print_success "✓ Temps d'exécution suffisant"
else
    print_warning "⚠ Temps d'exécution limité (recommandé: 60s+)"
fi

# 10. Résumé et recommandations
print_header "📋 RÉSUMÉ ET RECOMMANDATIONS"

echo ""
print_status "État général:"

ISSUES=0

if [ ${#MISSING_EXTENSIONS[@]} -gt 0 ]; then
    print_error "Extensions PHP manquantes: ${MISSING_EXTENSIONS[*]}"
    echo "  Installez avec: sudo apt-get install php-${MISSING_EXTENSIONS[*]// / php-}"
    ((ISSUES++))
fi

if [ ${#AVAILABLE_DRIVERS[@]} -eq 0 ]; then
    print_error "Aucun driver de base de données disponible"
    echo "  Installez avec: sudo apt-get install php-mysql php-pgsql php-sqlite3"
    ((ISSUES++))
fi

if [ ! -f "config/packages/reverse_engineering.yaml" ]; then
    print_warning "Configuration manquante"
    echo "  Créez le fichier de configuration avec les paramètres de votre base de données"
    ((ISSUES++))
fi

if [ $ISSUES -eq 0 ]; then
    print_success "🎉 Aucun problème critique détecté!"
    echo ""
    print_status "Prochaines étapes:"
    echo "  1. Configurez votre base de données dans config/packages/reverse_engineering.yaml"
    echo "  2. Testez avec: php bin/console reverse:generate --dry-run"
    echo "  3. Générez vos entités: php bin/console reverse:generate"
else
    print_warning "⚠ $ISSUES problème(s) détecté(s)"
    echo "  Corrigez les problèmes ci-dessus avant d'utiliser le bundle"
fi

echo ""
print_status "Pour plus d'aide:"
echo "  - Documentation: https://github.com/eprofos/reverse-engineering-bundle#readme"
echo "  - Issues: https://github.com/eprofos/reverse-engineering-bundle/issues"
echo "  - Troubleshooting: docs/TROUBLESHOOTING.md"

echo ""
print_header "🔍 DIAGNOSTIC TERMINÉ"

exit 0