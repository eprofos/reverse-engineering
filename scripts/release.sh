#!/bin/bash

# Release preparation script for ReverseEngineeringBundle
# Automates tag creation, validation and release preparation

set -e

# Colors for display
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to display colored messages
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

# Help function
show_help() {
    echo "Usage: $0 [VERSION] [OPTIONS]"
    echo ""
    echo "Prepares a new ReverseEngineeringBundle release"
    echo ""
    echo "Arguments:"
    echo "  VERSION     Version to create (e.g.: 0.1.0, 0.2.0, 1.0.0)"
    echo ""
    echo "Options:"
    echo "  -h, --help     Show this help"
    echo "  -d, --dry-run  Simulation mode (no modifications)"
    echo "  -f, --force    Force creation even if checks fail"
    echo ""
    echo "Examples:"
    echo "  $0 0.1.1                    # Creates version 0.1.1"
    echo "  $0 0.2.0 --dry-run         # Simulates creation of version 0.2.0"
    echo "  $0 1.0.0 --force           # Forces creation of version 1.0.0"
}

# Default variables
VERSION=""
DRY_RUN=false
FORCE=false

# Parse arguments
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
            print_error "Unknown option: $1"
            show_help
            exit 1
            ;;
        *)
            if [ -z "$VERSION" ]; then
                VERSION="$1"
            else
                print_error "Too many arguments"
                show_help
                exit 1
            fi
            shift
            ;;
    esac
done

# Check that version is provided
if [ -z "$VERSION" ]; then
    print_error "Version required"
    show_help
    exit 1
fi

# Check version format (semver)
if ! [[ $VERSION =~ ^[0-9]+\.[0-9]+\.[0-9]+(-[a-zA-Z0-9]+)?$ ]]; then
    print_error "Invalid version format. Use semver format (e.g.: 1.0.0, 0.1.0-beta)"
    exit 1
fi

# Check that we are in the right directory
if [ ! -f "composer.json" ]; then
    print_error "This script must be run from the project root"
    exit 1
fi

print_header "🚀 PREPARING RELEASE v$VERSION"

if [ "$DRY_RUN" = true ]; then
    print_warning "SIMULATION MODE ENABLED - No modifications will be made"
fi

# 1. Check Git repository status
print_status "1. Checking Git status..."

if ! git diff --quiet; then
    if [ "$FORCE" = false ]; then
        print_error "Uncommitted changes are present. Commit or stash your changes."
        exit 1
    else
        print_warning "Uncommitted changes are present (ignored with --force)"
    fi
fi

if ! git diff --cached --quiet; then
    if [ "$FORCE" = false ]; then
        print_error "Changes are staged. Commit your changes."
        exit 1
    else
        print_warning "Changes are staged (ignored with --force)"
    fi
fi

# Check that we are on main/master branch
CURRENT_BRANCH=$(git branch --show-current)
if [[ "$CURRENT_BRANCH" != "main" && "$CURRENT_BRANCH" != "master" ]]; then
    if [ "$FORCE" = false ]; then
        print_error "You must be on main or master branch to create a release"
        exit 1
    else
        print_warning "Not on main/master branch (ignored with --force)"
    fi
fi

# Check that tag doesn't already exist
if git tag -l | grep -q "^v$VERSION$"; then
    print_error "Tag v$VERSION already exists"
    exit 1
fi

print_success "Git status OK"

# 2. Complete project validation
print_status "2. Complete project validation..."

if [ "$DRY_RUN" = false ]; then
    if [ -f "scripts/validate.sh" ]; then
        chmod +x scripts/validate.sh
        ./scripts/validate.sh
    else
        print_warning "Validation script not found, manual validation..."
        
        # Basic tests
        if command -v vendor/bin/phpunit &> /dev/null; then
            vendor/bin/phpunit
        else
            print_error "PHPUnit not available"
            exit 1
        fi
    fi
    print_success "Validation successful"
else
    print_status "Validation skipped in simulation mode"
fi

# 3. CHANGELOG update
print_status "3. Checking CHANGELOG..."

if [ -f "CHANGELOG.md" ]; then
    if grep -q "## \[Unreleased\]" CHANGELOG.md; then
        print_warning "CHANGELOG contains an [Unreleased] section"
        print_status "Remember to update CHANGELOG with changes for this version"
    fi
    
    if ! grep -q "## \[$VERSION\]" CHANGELOG.md; then
        print_warning "Version $VERSION is not present in CHANGELOG"
        if [ "$FORCE" = false ]; then
            print_error "Add version $VERSION to CHANGELOG before continuing"
            exit 1
        fi
    else
        print_success "Version $VERSION found in CHANGELOG"
    fi
else
    print_warning "CHANGELOG.md not found"
fi

# 4. composer.json update (if necessary)
print_status "4. Checking composer.json..."

COMPOSER_VERSION=$(php -r "
    \$composer = json_decode(file_get_contents('composer.json'), true);
    echo \$composer['version'] ?? 'undefined';
")

if [ "$COMPOSER_VERSION" != "undefined" ] && [ "$COMPOSER_VERSION" != "$VERSION" ]; then
    print_status "Updating version in composer.json: $COMPOSER_VERSION -> $VERSION"
    
    if [ "$DRY_RUN" = false ]; then
        php -r "
            \$composer = json_decode(file_get_contents('composer.json'), true);
            \$composer['version'] = '$VERSION';
            file_put_contents('composer.json', json_encode(\$composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
        "
        print_success "composer.json updated"
    else
        print_status "composer.json would be updated (simulation)"
    fi
fi

# 5. Git tag creation
print_status "5. Creating Git tag..."

TAG_MESSAGE="Release version $VERSION

$(date '+%Y-%m-%d %H:%M:%S')

Changes included in this version:
- See CHANGELOG.md for complete details

ReverseEngineeringBundle v$VERSION
Developed by Eprofos Team"

if [ "$DRY_RUN" = false ]; then
    git add -A
    git commit -m "chore: prepare release v$VERSION" || true
    git tag -a "v$VERSION" -m "$TAG_MESSAGE"
    print_success "Tag v$VERSION created"
else
    print_status "Tag v$VERSION would be created (simulation)"
fi

# 6. Release notes generation
print_status "6. Generating release notes..."

RELEASE_NOTES_FILE="release-notes-v$VERSION.md"

cat > "$RELEASE_NOTES_FILE" << EOF
# Release Notes - ReverseEngineeringBundle v$VERSION

**Release date:** $(date '+%m/%d/%Y')

## 📦 Installation

\`\`\`bash
composer require eprofos/reverse-engineering-bundle:^$VERSION
\`\`\`

## 🔄 Update

\`\`\`bash
composer update eprofos/reverse-engineering-bundle
\`\`\`

## 📋 Changes

EOF

# Extract changes from CHANGELOG if available
if [ -f "CHANGELOG.md" ] && grep -q "## \[$VERSION\]" CHANGELOG.md; then
    echo "" >> "$RELEASE_NOTES_FILE"
    echo "### Change Details" >> "$RELEASE_NOTES_FILE"
    echo "" >> "$RELEASE_NOTES_FILE"
    
    # Extract this version's section from CHANGELOG
    sed -n "/## \[$VERSION\]/,/## \[/p" CHANGELOG.md | head -n -1 >> "$RELEASE_NOTES_FILE"
fi

cat >> "$RELEASE_NOTES_FILE" << EOF

## 🔧 Compatibility

- **PHP** : 8.1+
- **Symfony** : 7.0+
- **Doctrine DBAL** : 3.0+
- **Doctrine ORM** : 2.15+

## 📚 Documentation

- [README](https://github.com/eprofos/reverse-engineering-bundle#readme)
- [Guide de contribution](https://github.com/eprofos/reverse-engineering-bundle/blob/main/CONTRIBUTING.md)
- [Changelog complet](https://github.com/eprofos/reverse-engineering-bundle/blob/main/CHANGELOG.md)

## 🐛 Report an Issue

[Create an issue](https://github.com/eprofos/reverse-engineering-bundle/issues/new)

---

**Developed with ❤️ by the Eprofos team**
EOF

print_success "Release notes generated: $RELEASE_NOTES_FILE"

# 7. Final instructions
print_header "📋 FINAL INSTRUCTIONS"

echo ""
print_success "✅ Release v$VERSION prepared successfully!"
echo ""

if [ "$DRY_RUN" = false ]; then
    print_status "Next steps:"
    echo "  1. Check changes: git log --oneline -10"
    echo "  2. Push changes: git push origin $CURRENT_BRANCH"
    echo "  3. Push tag: git push origin v$VERSION"
    echo "  4. Create GitHub release with file: $RELEASE_NOTES_FILE"
    echo "  5. Publish on Packagist (if configured)"
    echo ""
    print_status "Generated files:"
    echo "  - Git tag: v$VERSION"
    echo "  - Release notes: $RELEASE_NOTES_FILE"
else
    print_status "In simulation mode, no modifications were made"
    print_status "Run again without --dry-run to create the release"
fi

echo ""
print_success "🎉 Release v$VERSION ready!"

exit 0