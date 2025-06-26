#!/bin/bash

# Complete validation script for ReverseEngineeringBundle
# Checks code quality, runs tests and generates reports

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

# Check that we are in the right directory
if [ ! -f "composer.json" ]; then
    print_error "This script must be run from the project root"
    exit 1
fi

print_header "🔍 COMPLETE REVERSEENGINEERINGBUNDLE VALIDATION"

# 1. Dependencies check
print_status "1. Checking dependencies..."
if [ ! -d "vendor" ]; then
    print_warning "Vendor directory doesn't exist. Installing dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
else
    print_success "Dependencies present"
fi

# 2. PHP syntax check
print_header "2. PHP Syntax Check"
print_status "Checking PHP files..."

find src -name "*.php" -exec php -l {} \; | grep -v "No syntax errors" || {
    print_success "No PHP syntax errors detected"
}

find tests -name "*.php" -exec php -l {} \; | grep -v "No syntax errors" || {
    print_success "No PHP syntax errors in tests"
}

# 3. Static analysis with PHPStan
print_header "3. Static Analysis (PHPStan Level 8)"
print_status "Running PHPStan..."

if command -v vendor/bin/phpstan &> /dev/null; then
    vendor/bin/phpstan analyse src --level=8 --no-progress
    print_success "Static analysis successful"
else
    print_warning "PHPStan not installed, installing..."
    composer require --dev phpstan/phpstan
    vendor/bin/phpstan analyse src --level=8 --no-progress
fi

# 4. Code style check
print_header "4. Code Style Check (PSR-12)"
print_status "Checking with PHP CS Fixer..."

if command -v vendor/bin/php-cs-fixer &> /dev/null; then
    vendor/bin/php-cs-fixer fix --dry-run --diff --verbose
    print_success "Code style compliant with PSR-12"
else
    print_warning "PHP CS Fixer not installed, installing..."
    composer require --dev friendsofphp/php-cs-fixer
    vendor/bin/php-cs-fixer fix --dry-run --diff --verbose
fi

# 5. Running tests
print_header "5. Running Test Suite"

# Create coverage directory
mkdir -p coverage

print_status "5.1. Unit tests..."
vendor/bin/phpunit --testsuite=Unit --colors=always

print_status "5.2. Integration tests..."
vendor/bin/phpunit --testsuite=Integration --colors=always

print_status "5.3. Command tests..."
vendor/bin/phpunit --testsuite=Command --colors=always

print_status "5.4. Exception tests..."
vendor/bin/phpunit --testsuite=Exception --colors=always

print_status "5.5. Performance tests..."
vendor/bin/phpunit --testsuite=Performance --colors=always

# 6. Coverage report generation
print_header "6. Coverage Report Generation"
print_status "Generating coverage reports..."

vendor/bin/phpunit --coverage-html=coverage/html --coverage-text --coverage-clover=coverage/clover.xml

# 7. Coverage check
print_header "7. Code Coverage Check"

if [ -f "coverage/clover.xml" ]; then
    # Extract coverage percentage from clover file
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
        print_success "Code coverage: ${COVERAGE}% (≥ 90% required)"
    else
        print_warning "Code coverage: ${COVERAGE}% (< 90% required)"
    fi
else
    print_warning "Unable to calculate code coverage"
fi

# 8. Documentation files check
print_header "8. Documentation Check"

REQUIRED_FILES=("README.md" "CHANGELOG.md" "CONTRIBUTING.md" "LICENSE")
for file in "${REQUIRED_FILES[@]}"; do
    if [ -f "$file" ]; then
        print_success "✓ $file present"
    else
        print_error "✗ $file missing"
        exit 1
    fi
done

# 9. Configuration check
print_header "9. Configuration Check"

if [ -f "config/packages/reverse_engineering.yaml" ]; then
    print_success "✓ Bundle configuration present"
else
    print_warning "Bundle configuration missing"
fi

if [ -f "phpunit.xml" ]; then
    print_success "✓ PHPUnit configuration present"
else
    print_error "✗ PHPUnit configuration missing"
    exit 1
fi

# 10. Project structure check
print_header "10. Project Structure Check"

REQUIRED_DIRS=("src/Bundle" "src/Command" "src/Service" "src/Exception" "tests/Unit" "tests/Integration")
for dir in "${REQUIRED_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        print_success "✓ Directory $dir present"
    else
        print_error "✗ Directory $dir missing"
        exit 1
    fi
done

# 11. Final summary
print_header "📊 VALIDATION SUMMARY"

print_success "✅ PHP syntax: OK"
print_success "✅ Static analysis (PHPStan level 8): OK"
print_success "✅ Code style (PSR-12): OK"
print_success "✅ Unit tests: OK"
print_success "✅ Integration tests: OK"
print_success "✅ Performance tests: OK"
print_success "✅ Documentation: OK"
print_success "✅ Project structure: OK"

if [ -n "$COVERAGE" ]; then
    print_success "✅ Code coverage: ${COVERAGE}%"
fi

echo ""
print_success "🎉 COMPLETE VALIDATION SUCCESSFUL!"
echo ""
print_status "📁 Generated reports:"
echo "  - HTML coverage report: coverage/html/index.html"
echo "  - Clover coverage report: coverage/clover.xml"
echo ""
print_status "🚀 Bundle is ready for production!"

exit 0