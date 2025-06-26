#!/bin/bash

# Script to run the complete test suite for ReverseEngineeringBundle

set -e

echo "🧪 Running ReverseEngineeringBundle test suite"
echo "========================================================="

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

# Check if PHPUnit is installed
if ! command -v vendor/bin/phpunit &> /dev/null; then
    print_error "PHPUnit is not installed. Run 'composer install' first."
    exit 1
fi

# Create coverage directory if it doesn't exist
mkdir -p coverage

print_status "Cleaning cache files..."
rm -rf .phpunit.cache
rm -rf coverage/*

echo ""
print_status "1. Running unit tests..."
echo "-----------------------------------"
vendor/bin/phpunit --testsuite=Unit --colors=always

echo ""
print_status "2. Running integration tests..."
echo "---------------------------------------"
vendor/bin/phpunit --testsuite=Integration --colors=always

echo ""
print_status "3. Running command tests..."
echo "-------------------------------------"
vendor/bin/phpunit --testsuite=Command --colors=always

echo ""
print_status "4. Running exception tests..."
echo "-------------------------------------"
vendor/bin/phpunit --testsuite=Exception --colors=always

echo ""
print_status "5. Running performance tests..."
echo "---------------------------------------"
vendor/bin/phpunit --testsuite=Performance --colors=always

echo ""
print_status "6. Generating coverage report..."
echo "-----------------------------------------"
vendor/bin/phpunit --coverage-html=coverage/html --coverage-text --coverage-clover=coverage/clover.xml

echo ""
print_success "✅ All tests executed successfully!"

# Display coverage summary
if [ -f "coverage/coverage.txt" ]; then
    echo ""
    print_status "📊 Code coverage summary:"
    echo "-----------------------------------"
    tail -n 10 coverage/coverage.txt
fi

echo ""
print_status "📁 Generated reports:"
echo "  - HTML report: coverage/html/index.html"
echo "  - Clover report: coverage/clover.xml"
echo "  - Text report: coverage/coverage.txt"

echo ""
print_success "🎉 Test suite completed!"