#!/bin/bash

# Script de préparation des releases pour ReverseEngineeringBundle
# Automatise la création de tags, la validation et la préparation des releases

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

# Fonction d'aide
show_help() {
    echo "Usage: $0 [VERSION] [OPTIONS]"
    echo ""
    echo "Prépare une nouvelle release du ReverseEngineeringBundle"
    echo ""
    echo "Arguments:"
    echo "  VERSION     Version à créer (ex: 0.1.0, 0.2.0, 1.0.0)"
    echo ""
    echo "Options:"
    echo "  -h, --help     Affiche cette aide"
    echo "  -d, --dry-run  Mode simulation (pas de modifications)"
    echo "  -f, --force    Force la création même si des vérifications échouent"
    echo ""
    echo "Exemples:"
    echo "  $0 0.1.1                    # Crée la version 0.1.1"
    echo "  $0 0.2.0 --dry-run         # Simule la création de la version 0.2.0"
    echo "  $0 1.0.0 --force           # Force la création de la version 1.0.0"
}

# Variables par défaut
VERSION=""
DRY_RUN=false
FORCE=false

# Analyse des arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -h|--help)
            show_help
            exit 0
            ;;
        -d|--dry-run)
            DRY_RUN=true
            shift
            ;;
        -f|--force)
            FORCE=true
            shift
            ;;
        -*)
            print_error "Option inconnue: $1"
            show_help
            exit 1
            ;;
        *)
            if [ -z "$VERSION" ]; then
                VERSION="$1"
            else
                print_error "Trop d'arguments"
                show_help
                exit 1
            fi
            shift
            ;;
    esac
done

# Vérifier que la version est fournie
if [ -z "$VERSION" ]; then
    print_error "Version requise"
    show_help
    exit 1
fi

# Vérifier le format de la version (semver)
if ! [[ $VERSION =~ ^[0-9]+\.[0-9]+\.[0-9]+(-[a-zA-Z0-9]+)?$ ]]; then
    print_error "Format de version invalide. Utilisez le format semver (ex: 1.0.0, 0.1.0-beta)"
    exit 1
fi

# Vérifier que nous sommes dans le bon répertoire
if [ ! -f "composer.json" ]; then
    print_error "Ce script doit être exécuté depuis la racine du projet"
    exit 1
fi

print_header "🚀 PRÉPARATION DE LA RELEASE v$VERSION"

if [ "$DRY_RUN" = true ]; then
    print_warning "MODE SIMULATION ACTIVÉ - Aucune modification ne sera effectuée"
fi

# 1. Vérifier l'état du repository Git
print_status "1. Vérification de l'état Git..."

if ! git diff --quiet; then
    if [ "$FORCE" = false ]; then
        print_error "Des modifications non commitées sont présentes. Committez ou stashez vos changements."
        exit 1
    else
        print_warning "Des modifications non commitées sont présentes (ignorées avec --force)"
    fi
fi

if ! git diff --cached --quiet; then
    if [ "$FORCE" = false ]; then
        print_error "Des modifications sont en staging. Committez vos changements."
        exit 1
    else
        print_warning "Des modifications sont en staging (ignorées avec --force)"
    fi
fi

# Vérifier que nous sommes sur la branche main/master
CURRENT_BRANCH=$(git branch --show-current)
if [[ "$CURRENT_BRANCH" != "main" && "$CURRENT_BRANCH" != "master" ]]; then
    if [ "$FORCE" = false ]; then
        print_error "Vous devez être sur la branche main ou master pour créer une release"
        exit 1
    else
        print_warning "Pas sur la branche main/master (ignoré avec --force)"
    fi
fi

# Vérifier que le tag n'existe pas déjà
if git tag -l | grep -q "^v$VERSION$"; then
    print_error "Le tag v$VERSION existe déjà"
    exit 1
fi

print_success "État Git OK"

# 2. Validation complète du projet
print_status "2. Validation complète du projet..."

if [ "$DRY_RUN" = false ]; then
    if [ -f "scripts/validate.sh" ]; then
        chmod +x scripts/validate.sh
        ./scripts/validate.sh
    else
        print_warning "Script de validation non trouvé, validation manuelle..."
        
        # Tests basiques
        if command -v vendor/bin/phpunit &> /dev/null; then
            vendor/bin/phpunit
        else
            print_error "PHPUnit non disponible"
            exit 1
        fi
    fi
    print_success "Validation réussie"
else
    print_status "Validation ignorée en mode simulation"
fi

# 3. Mise à jour du CHANGELOG
print_status "3. Vérification du CHANGELOG..."

if [ -f "CHANGELOG.md" ]; then
    if grep -q "## \[Unreleased\]" CHANGELOG.md; then
        print_warning "Le CHANGELOG contient une section [Unreleased]"
        print_status "Pensez à mettre à jour le CHANGELOG avec les changements de cette version"
    fi
    
    if ! grep -q "## \[$VERSION\]" CHANGELOG.md; then
        print_warning "La version $VERSION n'est pas présente dans le CHANGELOG"
        if [ "$FORCE" = false ]; then
            print_error "Ajoutez la version $VERSION au CHANGELOG avant de continuer"
            exit 1
        fi
    else
        print_success "Version $VERSION trouvée dans le CHANGELOG"
    fi
else
    print_warning "CHANGELOG.md non trouvé"
fi

# 4. Mise à jour du composer.json (si nécessaire)
print_status "4. Vérification du composer.json..."

COMPOSER_VERSION=$(php -r "
    \$composer = json_decode(file_get_contents('composer.json'), true);
    echo \$composer['version'] ?? 'non-définie';
")

if [ "$COMPOSER_VERSION" != "non-définie" ] && [ "$COMPOSER_VERSION" != "$VERSION" ]; then
    print_status "Mise à jour de la version dans composer.json: $COMPOSER_VERSION -> $VERSION"
    
    if [ "$DRY_RUN" = false ]; then
        php -r "
            \$composer = json_decode(file_get_contents('composer.json'), true);
            \$composer['version'] = '$VERSION';
            file_put_contents('composer.json', json_encode(\$composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
        "
        print_success "composer.json mis à jour"
    else
        print_status "composer.json serait mis à jour (simulation)"
    fi
fi

# 5. Création du tag Git
print_status "5. Création du tag Git..."

TAG_MESSAGE="Release version $VERSION

$(date '+%Y-%m-%d %H:%M:%S')

Changements inclus dans cette version :
- Voir CHANGELOG.md pour les détails complets

Bundle ReverseEngineeringBundle v$VERSION
Développé par Eprofos Team"

if [ "$DRY_RUN" = false ]; then
    git add -A
    git commit -m "chore: prepare release v$VERSION" || true
    git tag -a "v$VERSION" -m "$TAG_MESSAGE"
    print_success "Tag v$VERSION créé"
else
    print_status "Tag v$VERSION serait créé (simulation)"
fi

# 6. Génération des notes de release
print_status "6. Génération des notes de release..."

RELEASE_NOTES_FILE="release-notes-v$VERSION.md"

cat > "$RELEASE_NOTES_FILE" << EOF
# Release Notes - ReverseEngineeringBundle v$VERSION

**Date de release :** $(date '+%d/%m/%Y')

## 📦 Installation

\`\`\`bash
composer require eprofos/reverse-engineering-bundle:^$VERSION
\`\`\`

## 🔄 Mise à jour

\`\`\`bash
composer update eprofos/reverse-engineering-bundle
\`\`\`

## 📋 Changements

EOF

# Extraire les changements du CHANGELOG si disponible
if [ -f "CHANGELOG.md" ] && grep -q "## \[$VERSION\]" CHANGELOG.md; then
    echo "" >> "$RELEASE_NOTES_FILE"
    echo "### Détails des changements" >> "$RELEASE_NOTES_FILE"
    echo "" >> "$RELEASE_NOTES_FILE"
    
    # Extraire la section de cette version du CHANGELOG
    sed -n "/## \[$VERSION\]/,/## \[/p" CHANGELOG.md | head -n -1 >> "$RELEASE_NOTES_FILE"
fi

cat >> "$RELEASE_NOTES_FILE" << EOF

## 🔧 Compatibilité

- **PHP** : 8.1+
- **Symfony** : 7.0+
- **Doctrine DBAL** : 3.0+
- **Doctrine ORM** : 2.15+

## 📚 Documentation

- [README](https://github.com/eprofos/reverse-engineering-bundle#readme)
- [Guide de contribution](https://github.com/eprofos/reverse-engineering-bundle/blob/main/CONTRIBUTING.md)
- [Changelog complet](https://github.com/eprofos/reverse-engineering-bundle/blob/main/CHANGELOG.md)

## 🐛 Signaler un problème

[Créer une issue](https://github.com/eprofos/reverse-engineering-bundle/issues/new)

---

**Développé avec ❤️ par l'équipe Eprofos**
EOF

print_success "Notes de release générées: $RELEASE_NOTES_FILE"

# 7. Instructions finales
print_header "📋 INSTRUCTIONS FINALES"

echo ""
print_success "✅ Release v$VERSION préparée avec succès !"
echo ""

if [ "$DRY_RUN" = false ]; then
    print_status "Prochaines étapes :"
    echo "  1. Vérifiez les changements : git log --oneline -10"
    echo "  2. Poussez les changements : git push origin $CURRENT_BRANCH"
    echo "  3. Poussez le tag : git push origin v$VERSION"
    echo "  4. Créez une release sur GitHub avec le fichier : $RELEASE_NOTES_FILE"
    echo "  5. Publiez sur Packagist (si configuré)"
    echo ""
    print_status "Fichiers générés :"
    echo "  - Tag Git : v$VERSION"
    echo "  - Notes de release : $RELEASE_NOTES_FILE"
else
    print_status "En mode simulation, aucune modification n'a été effectuée"
    print_status "Relancez sans --dry-run pour créer la release"
fi

echo ""
print_success "🎉 Release v$VERSION prête !"

exit 0