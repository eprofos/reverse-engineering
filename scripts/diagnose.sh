#!/bin/bash

# Diagnostic script for ReverseEngineeringBundle
# Checks environment and configuration

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

print_header "🔍 REVERSEENGINEERINGBUNDLE DIAGNOSTIC"

# 1. PHP environment check
print_status "1. Checking PHP environment..."
echo "Version PHP: $(php --version | head -n 1)"

# Check required extensions
REQUIRED_EXTENSIONS=("pdo" "json" "mbstring" "xml" "ctype" "iconv")
MISSING_EXTENSIONS=()

for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if php -m | grep -q "^$ext$"; then
        print_success "✓ Extension $ext present"
    else
        print_error "✗ Extension $ext missing"
        MISSING_EXTENSIONS+=("$ext")
    fi
done

# Check database drivers
DB_DRIVERS=("pdo_mysql" "pdo_pgsql" "pdo_sqlite")
AVAILABLE_DRIVERS=()

for driver in "${DB_DRIVERS[@]}"; do
    if php -m | grep -q "$driver"; then
        print_success "✓ Driver $driver available"
        AVAILABLE_DRIVERS+=("$driver")
    else
        print_warning "⚠ Driver $driver not available"
    fi
done

if [ ${#AVAILABLE_DRIVERS[@]} -eq 0 ]; then
    print_error "No database drivers available!"
fi

# 2. Composer check
print_header "2. Composer Check"
if command -v composer &> /dev/null; then
    echo "Version Composer: $(composer --version)"
    print_success "✓ Composer installed"
else
    print_error "✗ Composer not found"
fi

# 3. Bundle check
print_header "3. Bundle Check"

if [ -f "composer.json" ]; then
    print_success "✓ composer.json file present"
    
    # Check if bundle is installed
    if composer show eprofos/reverse-engineering-bundle &> /dev/null; then
        BUNDLE_VERSION=$(composer show eprofos/reverse-engineering-bundle | grep "versions" | awk '{print $3}')
        print_success "✓ Bundle installed (version: $BUNDLE_VERSION)"
    else
        print_warning "⚠ Bundle not installed via Composer"
    fi
else
    print_error "✗ composer.json file not found"
fi

# Check project structure
REQUIRED_DIRS=("src/Bundle" "src/Service" "src/Command" "src/Exception")
for dir in "${REQUIRED_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        print_success "✓ Directory $dir present"
    else
        print_error "✗ Directory $dir missing"
    fi
done

# 4. Configuration check
print_header "4. Configuration Check"

if [ -f "config/packages/reverse_engineering.yaml" ]; then
    print_success "✓ Configuration file present"
    
    # Check YAML syntax
    if command -v php &> /dev/null && [ -f "bin/console" ]; then
        if php bin/console lint:yaml config/packages/reverse_engineering.yaml &> /dev/null; then
            print_success "✓ Valid YAML configuration"
        else
            print_error "✗ YAML configuration syntax error"
        fi
    fi
else
    print_warning "⚠ Configuration file missing"
    echo "  Create file config/packages/reverse_engineering.yaml"
fi

# Check Symfony bundles
if [ -f "config/bundles.php" ]; then
    if grep -q "ReverseEngineeringBundle" config/bundles.php; then
        print_success "✓ Bundle registered in config/bundles.php"
    else
        print_warning "⚠ Bundle not registered in config/bundles.php"
    fi
fi

# 5. CLI command test
print_header "5. CLI Command Test"

if [ -f "bin/console" ]; then
    print_success "✓ Symfony console present"
    
    # Test if command is available
    if php bin/console list | grep -q "reverse:generate"; then
        print_success "✓ reverse:generate command available"
    else
        print_error "✗ reverse:generate command not found"
    fi
else
    print_error "✗ Symfony console not found"
fi

# 6. Database connection test
print_header "6. Database Connection Test"

if [ -f "bin/console" ] && [ -f "config/packages/reverse_engineering.yaml" ]; then
    print_status "Testing connection..."
    
    # Try dry-run to test connection
    if php bin/console reverse:generate --dry-run --tables=non_existent_table 2>&1 | grep -q "Connection"; then
        print_error "✗ Database connection error"
        echo "  Check your connection parameters"
    else
        print_success "✓ Database connection OK"
    fi
else
    print_warning "⚠ Unable to test connection (missing configuration)"
fi

# 7. Permissions check
print_header "7. Permissions Check"

# Check src/Entity directory permissions
if [ -d "src/Entity" ]; then
    if [ -w "src/Entity" ]; then
        print_success "✓ src/Entity directory writable"
    else
        print_error "✗ src/Entity directory not writable"
        echo "  Run: chmod 755 src/Entity"
    fi
else
    print_warning "⚠ src/Entity directory doesn't exist"
    echo "  It will be created automatically during generation"
fi

# 8. Tests check
print_header "8. Tests Check"

if [ -f "phpunit.xml" ]; then
    print_success "✓ PHPUnit configuration present"
    
    if [ -d "tests" ]; then
        TEST_COUNT=$(find tests -name "*Test.php" | wc -l)
        print_success "✓ $TEST_COUNT test files found"
    else
        print_warning "⚠ tests directory missing"
    fi
else
    print_warning "⚠ PHPUnit configuration missing"
fi

# 9. PHP memory check
print_header "9. PHP Limits Check"

MEMORY_LIMIT=$(php -r "echo ini_get('memory_limit');")
MAX_EXECUTION_TIME=$(php -r "echo ini_get('max_execution_time');")

echo "Memory limit: $MEMORY_LIMIT"
echo "Max execution time: ${MAX_EXECUTION_TIME}s"

# Convert memory limit to bytes for comparison
MEMORY_BYTES=$(php -r "
    \$limit = '$MEMORY_LIMIT';
    \$bytes = (int)\$limit;
    if (strpos(\$limit, 'K') !== false) \$bytes *= 1024;
    if (strpos(\$limit, 'M') !== false) \$bytes *= 1024 * 1024;
    if (strpos(\$limit, 'G') !== false) \$bytes *= 1024 * 1024 * 1024;
    echo \$bytes;
")

if [ "$MEMORY_BYTES" -ge 134217728 ]; then  # 128MB
    print_success "✓ Sufficient memory limit"
else
    print_warning "⚠ Low memory limit (recommended: 128M+)"
fi

if [ "$MAX_EXECUTION_TIME" -ge 60 ] || [ "$MAX_EXECUTION_TIME" -eq 0 ]; then
    print_success "✓ Sufficient execution time"
else
    print_warning "⚠ Limited execution time (recommended: 60s+)"
fi

# 10. Summary and recommendations
print_header "📋 SUMMARY AND RECOMMENDATIONS"

echo ""
print_status "General status:"

ISSUES=0

if [ ${#MISSING_EXTENSIONS[@]} -gt 0 ]; then
    print_error "Missing PHP extensions: ${MISSING_EXTENSIONS[*]}"
    echo "  Install with: sudo apt-get install php-${MISSING_EXTENSIONS[*]// / php-}"
    ((ISSUES++))
fi

if [ ${#AVAILABLE_DRIVERS[@]} -eq 0 ]; then
    print_error "No database drivers available"
    echo "  Install with: sudo apt-get install php-mysql php-pgsql php-sqlite3"
    ((ISSUES++))
fi

if [ ! -f "config/packages/reverse_engineering.yaml" ]; then
    print_warning "Missing configuration"
    echo "  Create configuration file with your database parameters"
    ((ISSUES++))
fi

if [ $ISSUES -eq 0 ]; then
    print_success "🎉 No critical issues detected!"
    echo ""
    print_status "Next steps:"
    echo "  1. Configure your database in config/packages/reverse_engineering.yaml"
    echo "  2. Test with: php bin/console reverse:generate --dry-run"
    echo "  3. Generate your entities: php bin/console reverse:generate"
else
    print_warning "⚠ $ISSUES issue(s) detected"
    echo "  Fix the issues above before using the bundle"
fi

echo ""
print_status "For more help:"
echo "  - Documentation: https://github.com/eprofos/reverse-engineering-bundle#readme"
echo "  - Issues: https://github.com/eprofos/reverse-engineering-bundle/issues"
echo "  - Troubleshooting: docs/TROUBLESHOOTING.md"

echo ""
print_header "🔍 DIAGNOSTIC COMPLETED"

exit 0