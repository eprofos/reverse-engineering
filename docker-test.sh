#!/bin/bash

# Script utilitaire pour les tests Docker avec Sakila
# Usage: ./docker-test.sh [command]

set -e

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonctions utilitaires
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

# Vérifier si Docker est installé
check_docker() {
    if ! command -v docker &> /dev/null; then
        log_error "Docker n'est pas installé"
        exit 1
    fi
    
    if ! command -v docker-compose &> /dev/null; then
        log_error "Docker Compose n'est pas installé"
        exit 1
    fi
}

# Démarrer l'environnement Docker
start_environment() {
    log_info "Démarrage de l'environnement Docker..."
    
    docker-compose up -d
    
    log_info "Attente que MySQL soit prêt..."
    
    # Attendre que MySQL soit prêt (maximum 2 minutes)
    local max_attempts=60
    local attempt=0
    
    while [ $attempt -lt $max_attempts ]; do
        if docker-compose exec -T mysql mysqladmin ping -h localhost -u root -proot_password --silent 2>/dev/null; then
            log_success "MySQL est prêt !"
            break
        fi
        
        echo -n "."
        sleep 2
        ((attempt++))
    done
    
    if [ $attempt -eq $max_attempts ]; then
        log_error "Timeout : MySQL n'est pas prêt après 2 minutes"
        docker-compose logs mysql
        exit 1
    fi
    
    # Vérifier que Sakila est bien initialisée
    log_info "Vérification de la base Sakila..."
    
    local table_count=$(docker-compose exec -T mysql mysql -u sakila_user -psakila_password -D sakila -e "SHOW TABLES;" 2>/dev/null | wc -l)
    
    if [ "$table_count" -gt 15 ]; then
        log_success "Base Sakila initialisée avec $((table_count-1)) tables"
    else
        log_warning "Base Sakila semble incomplète ($((table_count-1)) tables)"
    fi
    
    log_success "Environnement Docker prêt !"
    log_info "Services disponibles :"
    log_info "  - MySQL: localhost:3306 (sakila_user/sakila_password)"
    log_info "  - phpMyAdmin: http://localhost:8080"
}

# Arrêter l'environnement Docker
stop_environment() {
    log_info "Arrêt de l'environnement Docker..."
    docker-compose down
    log_success "Environnement arrêté"
}

# Nettoyer complètement l'environnement
clean_environment() {
    log_warning "Nettoyage complet de l'environnement (données perdues)..."
    docker-compose down -v --rmi local
    log_success "Environnement nettoyé"
}

# Exécuter les tests Sakila
run_sakila_tests() {
    log_info "Exécution des tests d'intégration Sakila..."
    
    if ! docker-compose ps | grep -q "reverse_engineering_mysql.*Up"; then
        log_error "L'environnement Docker n'est pas démarré"
        log_info "Utilisez: $0 start"
        exit 1
    fi
    
    docker-compose exec php vendor/bin/phpunit tests/Integration/SakilaIntegrationTest.php --display-warnings
    
    if [ $? -eq 0 ]; then
        log_success "Tests Sakila réussis !"
    else
        log_error "Échec des tests Sakila"
        exit 1
    fi
}

# Exécuter tous les tests
run_all_tests() {
    log_info "Exécution de tous les tests..."
    
    if ! docker-compose ps | grep -q "reverse_engineering_mysql.*Up"; then
        log_error "L'environnement Docker n'est pas démarré"
        log_info "Utilisez: $0 start"
        exit 1
    fi
    
    docker-compose exec php vendor/bin/phpunit --display-warnings
    
    if [ $? -eq 0 ]; then
        log_success "Tous les tests réussis !"
    else
        log_error "Échec de certains tests"
        exit 1
    fi
}

# Générer des entités depuis Sakila
generate_entities() {
    log_info "Génération des entités depuis Sakila..."
    
    if ! docker-compose ps | grep -q "reverse_engineering_mysql.*Up"; then
        log_error "L'environnement Docker n'est pas démarré"
        log_info "Utilisez: $0 start"
        exit 1
    fi
    
    local output_dir="generated/sakila"
    local namespace="Sakila\\Entity"
    
    # Créer le répertoire de sortie
    mkdir -p "$output_dir"
    
    docker-compose exec php bin/console reverse:generate \
        --namespace="$namespace" \
        --output-dir="$output_dir" \
        --force
    
    if [ $? -eq 0 ]; then
        log_success "Entités générées dans $output_dir"
        log_info "Namespace utilisé: $namespace"
        
        # Compter les fichiers générés
        local file_count=$(find "$output_dir" -name "*.php" | wc -l)
        log_info "Fichiers générés: $file_count"
    else
        log_error "Échec de la génération des entités"
        exit 1
    fi
}

# Afficher les logs
show_logs() {
    local service=${1:-mysql}
    log_info "Affichage des logs pour le service: $service"
    docker-compose logs -f "$service"
}

# Ouvrir une session dans le conteneur PHP
shell_php() {
    log_info "Ouverture d'une session dans le conteneur PHP..."
    docker-compose exec php bash
}

# Ouvrir une session MySQL
shell_mysql() {
    log_info "Connexion à MySQL..."
    docker-compose exec mysql mysql -u sakila_user -psakila_password sakila
}

# Afficher le statut
show_status() {
    log_info "Statut de l'environnement Docker:"
    docker-compose ps
    
    echo ""
    log_info "Utilisation des ressources:"
    docker stats --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}"
}

# Afficher l'aide
show_help() {
    echo "Usage: $0 [command]"
    echo ""
    echo "Commandes disponibles:"
    echo "  start           Démarrer l'environnement Docker"
    echo "  stop            Arrêter l'environnement Docker"
    echo "  clean           Nettoyer complètement l'environnement"
    echo "  test-sakila     Exécuter les tests d'intégration Sakila"
    echo "  test-all        Exécuter tous les tests"
    echo "  generate        Générer des entités depuis Sakila"
    echo "  logs [service]  Afficher les logs (défaut: mysql)"
    echo "  shell-php       Ouvrir une session dans le conteneur PHP"
    echo "  shell-mysql     Ouvrir une session MySQL"
    echo "  status          Afficher le statut des conteneurs"
    echo "  help            Afficher cette aide"
    echo ""
    echo "Exemples:"
    echo "  $0 start && $0 test-sakila"
    echo "  $0 generate"
    echo "  $0 logs mysql"
}

# Script principal
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

# Exécuter le script principal
main "$@"