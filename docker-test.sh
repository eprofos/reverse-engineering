#!/bin/bash

# Utility script for Docker tests with Sakila
# Usage: ./docker-test.sh [command]

set -e

# Colors for display
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Utility functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if Docker is installed
check_docker() {
    if ! command -v docker &> /dev/null; then
        log_error "Docker is not installed"
        exit 1
    fi
    
    if ! command -v docker-compose &> /dev/null; then
        log_error "Docker Compose is not installed"
        exit 1
    fi
}

# Start Docker environment
start_environment() {
    log_info "Starting Docker environment..."
    
    docker-compose up -d
    
    log_info "Waiting for MySQL to be ready..."
    
    # Wait for MySQL to be ready (maximum 2 minutes)
    local max_attempts=60
    local attempt=0
    
    while [ $attempt -lt $max_attempts ]; do
        if docker-compose exec -T mysql mysqladmin ping -h localhost -u root -proot_password --silent 2>/dev/null; then
            log_success "MySQL is ready!"
            break
        fi
        
        echo -n "."
        sleep 2
        ((attempt++))
    done
    
    if [ $attempt -eq $max_attempts ]; then
        log_error "Timeout: MySQL not ready after 2 minutes"
        docker-compose logs mysql
        exit 1
    fi
    
    # Check that Sakila is properly initialized
    log_info "Checking Sakila database..."
    
    local table_count=$(docker-compose exec -T mysql mysql -u sakila_user -psakila_password -D sakila -e "SHOW TABLES;" 2>/dev/null | wc -l)
    
    if [ "$table_count" -gt 15 ]; then
        log_success "Sakila database initialized with $((table_count-1)) tables"
    else
        log_warning "Sakila database seems incomplete ($((table_count-1)) tables)"
    fi
    
    log_success "Docker environment ready!"
    log_info "Available services:"
    log_info "  - MySQL: localhost:3306 (sakila_user/sakila_password)"
    log_info "  - phpMyAdmin: http://localhost:8080"
}

# Stop Docker environment
stop_environment() {
    log_info "Stopping Docker environment..."
    docker-compose down
    log_success "Environment stopped"
}

# Clean environment completely
clean_environment() {
    log_warning "Complete environment cleanup (data will be lost)..."
    docker-compose down -v --rmi local
    log_success "Environment cleaned"
}

# Run Sakila tests
run_sakila_tests() {
    log_info "Running Sakila integration tests..."
    
    if ! docker-compose ps | grep -q "reverse_engineering_mysql.*Up"; then
        log_error "L'environnement Docker n'est pas démarré"
        log_info "Utilisez: $0 start"
        exit 1
    fi
    
    docker-compose exec php vendor/bin/phpunit tests/Integration/SakilaIntegrationTest.php --display-warnings
    
    if [ $? -eq 0 ]; then
        log_success "Sakila tests successful!"
    else
        log_error "Sakila tests failed"
        exit 1
    fi
}

# Run all tests
run_all_tests() {
    log_info "Running all tests..."
    
    if ! docker-compose ps | grep -q "reverse_engineering_mysql.*Up"; then
        log_error "L'environnement Docker n'est pas démarré"
        log_info "Utilisez: $0 start"
        exit 1
    fi
    
    docker-compose exec php vendor/bin/phpunit --display-warnings
    
    if [ $? -eq 0 ]; then
        log_success "All tests successful!"
    else
        log_error "Some tests failed"
        exit 1
    fi
}

# Generate entities from Sakila
generate_entities() {
    log_info "Generating entities from Sakila..."
    
    if ! docker-compose ps | grep -q "reverse_engineering_mysql.*Up"; then
        log_error "L'environnement Docker n'est pas démarré"
        log_info "Utilisez: $0 start"
        exit 1
    fi
    
    local output_dir="generated/sakila"
    local namespace="Sakila\\Entity"
    
    # Create output directory
    mkdir -p "$output_dir"
    
    # Use dedicated PHP script instead of bin/console
    docker-compose exec php php scripts/generate-entities.php \
        --namespace="$namespace" \
        --output-dir="$output_dir" \
        --force
    
    if [ $? -eq 0 ]; then
        log_success "Entities generated in $output_dir"
        log_info "Namespace used: $namespace"
        
        # Count generated files
        local file_count=$(find "$output_dir" -name "*.php" | wc -l)
        log_info "Generated files: $file_count"
        
        # Validate PHP syntax of generated files
        log_info "Validating PHP syntax..."
        local syntax_errors=0
        for file in $(find "$output_dir" -name "*.php"); do
            if ! docker-compose exec -T php php -l "$file" > /dev/null 2>&1; then
                log_warning "Syntax error in: $file"
                ((syntax_errors++))
            fi
        done
        
        if [ $syntax_errors -eq 0 ]; then
            log_success "PHP syntax validated for all files"
        else
            log_warning "$syntax_errors file(s) with syntax errors"
        fi
    else
        log_error "Entity generation failed"
        exit 1
    fi
}

# Generate entities and copy them to local host
generate_and_copy() {
    local local_output_dir="${1:-./generated-entities}"
    local namespace="${2:-Sakila\\Entity}"
    local container_output_dir="generated/sakila"
    
    log_info "Automatic entity generation and copy..."
    log_info "Local destination directory: $local_output_dir"
    # Display namespace by properly escaping backslashes for display
    local display_namespace=$(echo "$namespace" | sed 's/\\/\\\\/g')
    log_info "Namespace: $display_namespace"
    
    if ! docker-compose ps | grep -q "reverse_engineering_mysql.*Up"; then
        log_error "Docker environment is not started"
        log_info "Use: $0 start"
        exit 1
    fi
    
    # Create local destination directory
    mkdir -p "$local_output_dir"
    log_info "Local directory created: $local_output_dir"
    
    # Clean generation directory in container
    log_info "Cleaning generation directory in container..."
    docker-compose exec php rm -rf "$container_output_dir"
    docker-compose exec php mkdir -p "$container_output_dir"
    
    # Measure generation time
    local start_time=$(date +%s)
    
    # Generate entities in container
    log_info "Generating entities in Docker container..."
    docker-compose exec php php scripts/generate-entities.php \
        --namespace="$namespace" \
        --output-dir="$container_output_dir" \
        --force
    
    if [ $? -ne 0 ]; then
        log_error "Entity generation failed"
        exit 1
    fi
    
    local end_time=$(date +%s)
    local generation_time=$((end_time - start_time))
    
    log_success "Entities generated successfully in ${generation_time}s"
    
    # Get list of generated files in container
    log_info "Retrieving list of generated files..."
    local container_files=$(docker-compose exec -T php find "$container_output_dir" -name "*.php" -type f)
    
    if [ -z "$container_files" ]; then
        log_error "No generated PHP files found in container"
        exit 1
    fi
    
    # Count files
    local file_count=$(echo "$container_files" | wc -l)
    log_info "Files to copy: $file_count"
    
    # Copy files from container to host
    log_info "Copying files from container to local host..."
    
    # Copy entire directory at once
    docker cp "reverse_engineering_php:/var/www/html/$container_output_dir/." "$local_output_dir/"
    
    if [ $? -eq 0 ]; then
        log_success "Files copied successfully to $local_output_dir"
    else
        log_error "File copy failed"
        exit 1
    fi
    
    # Fix permissions of copied files
    log_info "Fixing file permissions..."
    chmod -R 644 "$local_output_dir"/*.php 2>/dev/null || true
    find "$local_output_dir" -type d -exec chmod 755 {} \; 2>/dev/null || true
    
    # Validate PHP syntax of copied files
    log_info "Validating PHP syntax of copied files..."
    local syntax_errors=0
    local validated_files=0
    
    # Count copied files
    validated_files=$(find "$local_output_dir" -name "*.php" -type f | wc -l)
    
    # Simplified validation with timeout
    if command -v php &> /dev/null; then
        local php_files=($(find "$local_output_dir" -name "*.php" -type f))
        for file in "${php_files[@]}"; do
            if ! timeout 5 php -l "$file" > /dev/null 2>&1; then
                log_warning "Syntax error in: $(basename "$file")"
                ((syntax_errors++))
            fi
        done
    fi
    
    # Calculate total file size
    local total_size=$(du -sh "$local_output_dir" 2>/dev/null | cut -f1)
    
    # Clean temporary files in container
    log_info "Cleaning temporary files in container..."
    docker-compose exec php rm -rf "$container_output_dir"
    
    # Display final summary
    echo ""
    log_success "🎉 Generation and copy completed successfully!"
    echo ""
    log_info "📊 Operations summary:"
    log_info "   - Generation time: ${generation_time}s"
    log_info "   - Generated files: $file_count"
    log_info "   - Copied files: $validated_files"
    log_info "   - Total size: ${total_size:-N/A}"
    log_info "   - Destination directory: $local_output_dir"
    log_info "   - Namespace used: $display_namespace"
    
    if command -v php &> /dev/null; then
        if [ $syntax_errors -eq 0 ]; then
            log_success "   - Syntax validation: ✅ All files are valid"
        else
            log_warning "   - Syntax validation: ⚠️  $syntax_errors error(s) detected"
        fi
    else
        log_info "   - Syntax validation: ⏭️  PHP not available on host"
    fi
    
    echo ""
    log_info "📁 Generated files:"
    for file in $(find "$local_output_dir" -name "*.php" -type f | sort); do
        local filename=$(basename "$file")
        local filesize=$(ls -lh "$file" 2>/dev/null | awk '{print $5}')
        log_info "   - $filename (${filesize:-N/A})"
    done
    
    echo ""
    log_info "💡 To use these entities in your Symfony project:"
    log_info "   1. Copy files to src/Entity/ in your project"
    log_info "   2. Adjust namespace according to your configuration"
    log_info "   3. Run 'php bin/console doctrine:schema:validate'"
    
    log_success "Operation completed successfully!"
}

# Show logs
show_logs() {
    local service=${1:-mysql}
    log_info "Showing logs for service: $service"
    docker-compose logs -f "$service"
}

# Open session in PHP container
shell_php() {
    log_info "Opening session in PHP container..."
    docker-compose exec php bash
}

# Open MySQL session
shell_mysql() {
    log_info "Connecting to MySQL..."
    docker-compose exec mysql mysql -u sakila_user -psakila_password sakila
}

# Show status
show_status() {
    log_info "Docker environment status:"
    docker-compose ps
    
    echo ""
    log_info "Resource usage:"
    docker stats --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}"
}

# Show help
show_help() {
    echo "Usage: $0 [command] [options]"
    echo ""
    echo "Available commands:"
    echo "  start                    Start Docker environment"
    echo "  stop                     Stop Docker environment"
    echo "  clean                    Clean environment completely"
    echo "  test-sakila              Run Sakila integration tests"
    echo "  test-all                 Run all tests"
    echo "  generate                 Generate entities from Sakila (in container)"
    echo "  generate-and-copy [dir] [namespace]  Generate and copy entities to local host"
    echo "  logs [service]           Show logs (default: mysql)"
    echo "  shell-php                Open session in PHP container"
    echo "  shell-mysql              Open MySQL session"
    echo "  status                   Show container status"
    echo "  help                     Show this help"
    echo ""
    echo "Options for generate-and-copy:"
    echo "  [dir]        Local destination directory (default: ./generated-entities)"
    echo "  [namespace]  Namespace for entities (default: Sakila\\Entity)"
    echo ""
    echo "Examples:"
    echo "  $0 start && $0 test-sakila"
    echo "  $0 generate"
    echo "  $0 generate-and-copy"
    echo "  $0 generate-and-copy ./my-entities"
    echo "  $0 generate-and-copy ./my-entities \"MyApp\\\\Entity\""
    echo "  $0 logs mysql"
}

# Main script
main() {
    check_docker
    
    case "${1:-help}" in
        "start")
            start_environment
            ;;
        "stop")
            stop_environment
            ;;
        "clean")
            clean_environment
            ;;
        "test-sakila")
            run_sakila_tests
            ;;
        "test-all")
            run_all_tests
            ;;
        "generate")
            generate_entities
            ;;
        "generate-and-copy")
            generate_and_copy "$2" "$3"
            ;;
        "logs")
            show_logs "$2"
            ;;
        "shell-php")
            shell_php
            ;;
        "shell-mysql")
            shell_mysql
            ;;
        "status")
            show_status
            ;;
        "help"|*)
            show_help
            ;;
    esac
}

# Execute main script
main "$@"