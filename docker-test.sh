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
    
    # Utiliser le script PHP dédié au lieu de bin/console
    docker-compose exec php php scripts/generate-entities.php \
        --namespace="$namespace" \
        --output-dir="$output_dir" \
        --force
    
    if [ $? -eq 0 ]; then
        log_success "Entités générées dans $output_dir"
        log_info "Namespace utilisé: $namespace"
        
        # Compter les fichiers générés
        local file_count=$(find "$output_dir" -name "*.php" | wc -l)
        log_info "Fichiers générés: $file_count"
        
        # Valider la syntaxe PHP des fichiers générés
        log_info "Validation de la syntaxe PHP..."
        local syntax_errors=0
        for file in $(find "$output_dir" -name "*.php"); do
            if ! docker-compose exec -T php php -l "$file" > /dev/null 2>&1; then
                log_warning "Erreur de syntaxe dans: $file"
                ((syntax_errors++))
            fi
        done
        
        if [ $syntax_errors -eq 0 ]; then
            log_success "Syntaxe PHP validée pour tous les fichiers"
        else
            log_warning "$syntax_errors fichier(s) avec des erreurs de syntaxe"
        fi
    else
        log_error "Échec de la génération des entités"
        exit 1
    fi
}

# Générer des entités et les copier vers l'hôte local
generate_and_copy() {
    local local_output_dir="${1:-./generated-entities}"
    local namespace="${2:-Sakila\\\\Entity}"
    local container_output_dir="generated/sakila"
    
    log_info "Génération et copie automatique des entités..."
    log_info "Répertoire de destination local: $local_output_dir"
    log_info "Namespace: $namespace"
    
    if ! docker-compose ps | grep -q "reverse_engineering_mysql.*Up"; then
        log_error "L'environnement Docker n'est pas démarré"
        log_info "Utilisez: $0 start"
        exit 1
    fi
    
    # Créer le répertoire de destination local
    mkdir -p "$local_output_dir"
    log_info "Répertoire local créé: $local_output_dir"
    
    # Nettoyer le répertoire de génération dans le conteneur
    log_info "Nettoyage du répertoire de génération dans le conteneur..."
    docker-compose exec php rm -rf "$container_output_dir"
    docker-compose exec php mkdir -p "$container_output_dir"
    
    # Mesurer le temps de génération
    local start_time=$(date +%s)
    
    # Générer les entités dans le conteneur
    log_info "Génération des entités dans le conteneur Docker..."
    docker-compose exec php php scripts/generate-entities.php \
        --namespace="$namespace" \
        --output-dir="$container_output_dir" \
        --force
    
    if [ $? -ne 0 ]; then
        log_error "Échec de la génération des entités"
        exit 1
    fi
    
    local end_time=$(date +%s)
    local generation_time=$((end_time - start_time))
    
    log_success "Entités générées avec succès en ${generation_time}s"
    
    # Obtenir la liste des fichiers générés dans le conteneur
    log_info "Récupération de la liste des fichiers générés..."
    local container_files=$(docker-compose exec -T php find "$container_output_dir" -name "*.php" -type f)
    
    if [ -z "$container_files" ]; then
        log_error "Aucun fichier PHP généré trouvé dans le conteneur"
        exit 1
    fi
    
    # Compter les fichiers
    local file_count=$(echo "$container_files" | wc -l)
    log_info "Fichiers à copier: $file_count"
    
    # Copier les fichiers du conteneur vers l'hôte
    log_info "Copie des fichiers du conteneur vers l'hôte local..."
    
    # Copier tout le répertoire en une fois
    docker cp "reverse_engineering_php:/var/www/html/$container_output_dir/." "$local_output_dir/"
    
    if [ $? -eq 0 ]; then
        log_success "Fichiers copiés avec succès vers $local_output_dir"
    else
        log_error "Échec de la copie des fichiers"
        exit 1
    fi
    
    # Corriger les permissions des fichiers copiés
    log_info "Correction des permissions des fichiers..."
    chmod -R 644 "$local_output_dir"/*.php 2>/dev/null || true
    find "$local_output_dir" -type d -exec chmod 755 {} \; 2>/dev/null || true
    
    # Valider la syntaxe PHP des fichiers copiés
    log_info "Validation de la syntaxe PHP des fichiers copiés..."
    local syntax_errors=0
    local validated_files=0
    
    # Compter les fichiers copiés
    validated_files=$(find "$local_output_dir" -name "*.php" -type f | wc -l)
    
    # Validation simplifiée avec timeout
    if command -v php &> /dev/null; then
        local php_files=($(find "$local_output_dir" -name "*.php" -type f))
        for file in "${php_files[@]}"; do
            if ! timeout 5 php -l "$file" > /dev/null 2>&1; then
                log_warning "Erreur de syntaxe dans: $(basename "$file")"
                ((syntax_errors++))
            fi
        done
    fi
    
    # Calculer la taille totale des fichiers
    local total_size=$(du -sh "$local_output_dir" 2>/dev/null | cut -f1)
    
    # Nettoyer les fichiers temporaires dans le conteneur
    log_info "Nettoyage des fichiers temporaires dans le conteneur..."
    docker-compose exec php rm -rf "$container_output_dir"
    
    # Afficher le résumé final
    echo ""
    log_success "🎉 Génération et copie terminées avec succès !"
    echo ""
    log_info "📊 Résumé des opérations:"
    log_info "   - Temps de génération: ${generation_time}s"
    log_info "   - Fichiers générés: $file_count"
    log_info "   - Fichiers copiés: $validated_files"
    log_info "   - Taille totale: ${total_size:-N/A}"
    log_info "   - Répertoire de destination: $local_output_dir"
    log_info "   - Namespace utilisé: $namespace"
    
    if command -v php &> /dev/null; then
        if [ $syntax_errors -eq 0 ]; then
            log_success "   - Validation syntaxe: ✅ Tous les fichiers sont valides"
        else
            log_warning "   - Validation syntaxe: ⚠️  $syntax_errors erreur(s) détectée(s)"
        fi
    else
        log_info "   - Validation syntaxe: ⏭️  PHP non disponible sur l'hôte"
    fi
    
    echo ""
    log_info "📁 Fichiers générés:"
    for file in $(find "$local_output_dir" -name "*.php" -type f | sort); do
        local filename=$(basename "$file")
        local filesize=$(ls -lh "$file" 2>/dev/null | awk '{print $5}')
        log_info "   - $filename (${filesize:-N/A})"
    done
    
    echo ""
    log_info "💡 Pour utiliser ces entités dans votre projet Symfony:"
    log_info "   1. Copiez les fichiers vers src/Entity/ de votre projet"
    log_info "   2. Ajustez le namespace selon votre configuration"
    log_info "   3. Exécutez 'php bin/console doctrine:schema:validate'"
    
    log_success "Opération terminée avec succès !"
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
    echo "Usage: $0 [command] [options]"
    echo ""
    echo "Commandes disponibles:"
    echo "  start                    Démarrer l'environnement Docker"
    echo "  stop                     Arrêter l'environnement Docker"
    echo "  clean                    Nettoyer complètement l'environnement"
    echo "  test-sakila              Exécuter les tests d'intégration Sakila"
    echo "  test-all                 Exécuter tous les tests"
    echo "  generate                 Générer des entités depuis Sakila (dans le conteneur)"
    echo "  generate-and-copy [dir] [namespace]  Générer et copier les entités vers l'hôte local"
    echo "  logs [service]           Afficher les logs (défaut: mysql)"
    echo "  shell-php                Ouvrir une session dans le conteneur PHP"
    echo "  shell-mysql              Ouvrir une session MySQL"
    echo "  status                   Afficher le statut des conteneurs"
    echo "  help                     Afficher cette aide"
    echo ""
    echo "Options pour generate-and-copy:"
    echo "  [dir]        Répertoire de destination local (défaut: ./generated-entities)"
    echo "  [namespace]  Namespace pour les entités (défaut: Sakila\\Entity)"
    echo ""
    echo "Exemples:"
    echo "  $0 start && $0 test-sakila"
    echo "  $0 generate"
    echo "  $0 generate-and-copy"
    echo "  $0 generate-and-copy ./my-entities"
    echo "  $0 generate-and-copy ./my-entities \"MyApp\\\\Entity\""
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

# Exécuter le script principal
main "$@"