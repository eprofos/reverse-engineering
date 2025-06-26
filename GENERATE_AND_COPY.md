# Commande `generate-and-copy` - Guide d'utilisation

## 🎯 Objectif

La commande `generate-and-copy` automatise complètement le processus de génération d'entités Doctrine depuis la base de données Sakila et leur récupération sur l'environnement local. Cette commande élimine le besoin de manipulations manuelles pour récupérer les fichiers générés depuis le conteneur Docker.

## 🚀 Utilisation

### Syntaxe de base
```bash
./docker-test.sh generate-and-copy [répertoire_destination] [namespace]
```

### Paramètres

| Paramètre | Type | Défaut | Description |
|-----------|------|--------|-------------|
| `répertoire_destination` | Optionnel | `./generated-entities` | Répertoire local où copier les entités |
| `namespace` | Optionnel | `Sakila\\Entity` | Namespace PHP pour les entités |

## 📋 Exemples d'utilisation

### 1. Utilisation basique (paramètres par défaut)
```bash
./docker-test.sh generate-and-copy
```
**Résultat :** Entités dans `./generated-entities/` avec namespace `Sakila\Entity`

### 2. Répertoire personnalisé
```bash
./docker-test.sh generate-and-copy ./my-entities
```
**Résultat :** Entités dans `./my-entities/` avec namespace `Sakila\Entity`

### 3. Répertoire et namespace personnalisés
```bash
./docker-test.sh generate-and-copy ./src/Entity "MyApp\\Entity"
```
**Résultat :** Entités dans `./src/Entity/` avec namespace `MyApp\Entity`

## 🔄 Processus automatisé

La commande exécute automatiquement les étapes suivantes :

1. **Vérification de l'environnement**
   - ✅ Contrôle que Docker et Docker Compose sont installés
   - ✅ Vérification que l'environnement MySQL est démarré

2. **Préparation**
   - 📁 Création du répertoire de destination local
   - 🧹 Nettoyage du répertoire de génération dans le conteneur

3. **Génération des entités**
   - ⚙️ Exécution du script de génération dans le conteneur PHP
   - ⏱️ Mesure du temps d'exécution
   - ✅ Validation de la génération

4. **Copie des fichiers**
   - 📋 Utilisation de `docker cp` pour copier les fichiers
   - 🏗️ Préservation de la structure des répertoires
   - 🔐 Correction automatique des permissions

5. **Validation et nettoyage**
   - 🔍 Validation de la syntaxe PHP (si PHP disponible sur l'hôte)
   - 🧹 Nettoyage des fichiers temporaires dans le conteneur
   - 📊 Génération du rapport final

## 📁 Structure des fichiers générés

```
répertoire_destination/
├── Actor.php               # Entité Actor
├── ActorRepository.php     # Repository Actor
├── Address.php             # Entité Address
├── AddressRepository.php   # Repository Address
├── Category.php            # Entité Category
├── CategoryRepository.php  # Repository Category
├── City.php                # Entité City
├── CityRepository.php      # Repository City
├── Country.php             # Entité Country
├── CountryRepository.php   # Repository Country
├── Customer.php            # Entité Customer
├── CustomerRepository.php  # Repository Customer
├── Film.php                # Entité Film (complexe avec relations)
├── FilmRepository.php      # Repository Film
├── FilmActor.php           # Entité de liaison Film-Actor
├── FilmActorRepository.php # Repository FilmActor
├── FilmCategory.php        # Entité de liaison Film-Category
├── FilmCategoryRepository.php # Repository FilmCategory
├── FilmText.php            # Entité FilmText
├── FilmTextRepository.php  # Repository FilmText
├── Inventory.php           # Entité Inventory
├── InventoryRepository.php # Repository Inventory
├── Language.php            # Entité Language
├── LanguageRepository.php  # Repository Language
├── Payment.php             # Entité Payment
├── PaymentRepository.php   # Repository Payment
├── Rental.php              # Entité Rental
├── RentalRepository.php    # Repository Rental
├── Staff.php               # Entité Staff
├── StaffRepository.php     # Repository Staff
├── Store.php               # Entité Store
└── StoreRepository.php     # Repository Store
```

## 📊 Informations affichées

La commande fournit un rapport détaillé incluant :

- **⏱️ Temps de génération** : Durée de la génération des entités
- **📄 Nombre de fichiers** : Entités et repositories générés
- **💾 Taille totale** : Espace disque utilisé par les fichiers
- **✅ Validation syntaxe** : Résultat de la validation PHP
- **📋 Liste des fichiers** : Détail de chaque fichier généré avec sa taille

## 🎯 Exemple de sortie complète

```bash
$ ./docker-test.sh generate-and-copy ./my-entities

[INFO] Génération et copie automatique des entités...
[INFO] Répertoire de destination local: ./my-entities
[INFO] Namespace: Sakila\Entity
[INFO] Répertoire local créé: ./my-entities
[INFO] Nettoyage du répertoire de génération dans le conteneur...
[INFO] Génération des entités dans le conteneur Docker...
[SUCCESS] Entités générées avec succès en 12s
[INFO] Récupération de la liste des fichiers générés...
[INFO] Fichiers à copier: 32
[INFO] Copie des fichiers du conteneur vers l'hôte local...
[SUCCESS] Fichiers copiés avec succès vers ./my-entities
[INFO] Correction des permissions des fichiers...
[INFO] Validation de la syntaxe PHP des fichiers copiés...
[INFO] Nettoyage des fichiers temporaires dans le conteneur...

[SUCCESS] 🎉 Génération et copie terminées avec succès !

[INFO] 📊 Résumé des opérations:
[INFO]    - Temps de génération: 12s
[INFO]    - Fichiers générés: 32
[INFO]    - Fichiers copiés: 32
[INFO]    - Taille totale: 156K
[INFO]    - Répertoire de destination: ./my-entities
[INFO]    - Namespace utilisé: Sakila\Entity
[SUCCESS]    - Validation syntaxe: ✅ Tous les fichiers sont valides

[INFO] 📁 Fichiers générés:
[INFO]    - Actor.php (2.3K)
[INFO]    - ActorRepository.php (1.9K)
[INFO]    - Film.php (7.0K)
[INFO]    - FilmRepository.php (1.8K)
[INFO]    - ... (liste complète)

[INFO] 💡 Pour utiliser ces entités dans votre projet Symfony:
[INFO]    1. Copiez les fichiers vers src/Entity/ de votre projet
[INFO]    2. Ajustez le namespace selon votre configuration
[INFO]    3. Exécutez 'php bin/console doctrine:schema:validate'

[SUCCESS] Opération terminée avec succès !
```

## 🔧 Intégration dans un projet Symfony

### Étape 1 : Copier les fichiers
```bash
# Copier vers votre projet Symfony
cp ./generated-entities/*.php /path/to/your/symfony/project/src/Entity/
```

### Étape 2 : Ajuster le namespace (si nécessaire)
```bash
# Remplacer le namespace dans tous les fichiers
find /path/to/your/symfony/project/src/Entity/ -name "*.php" -exec sed -i 's/namespace Sakila\\Entity;/namespace App\\Entity;/g' {} \;
```

### Étape 3 : Valider avec Doctrine
```bash
cd /path/to/your/symfony/project
php bin/console doctrine:schema:validate
```

### Étape 4 : Générer les migrations (si nécessaire)
```bash
php bin/console doctrine:migrations:diff
```

## ✨ Avantages

- ✅ **Automatisation complète** : Une seule commande pour tout le processus
- ✅ **Copie automatique** : Plus besoin de manipuler Docker manuellement
- ✅ **Validation intégrée** : Vérification de la syntaxe PHP
- ✅ **Nettoyage automatique** : Suppression des fichiers temporaires
- ✅ **Statistiques détaillées** : Rapport complet des opérations
- ✅ **Gestion des permissions** : Correction automatique des droits de fichiers
- ✅ **Résumé informatif** : Instructions pour l'intégration Symfony

## 🚨 Prérequis

- Docker et Docker Compose installés
- Environnement MySQL démarré (`./docker-test.sh start`)
- Base de données Sakila initialisée (automatique au démarrage)

## 🔍 Dépannage

### Erreur : "L'environnement Docker n'est pas démarré"
```bash
./docker-test.sh start
```

### Erreur : "Aucun fichier PHP généré trouvé"
Vérifiez les logs de génération et la connexion à la base de données :
```bash
./docker-test.sh logs mysql
./docker-test.sh shell-php
```

### Problème de permissions
Les permissions sont automatiquement corrigées, mais vous pouvez les ajuster manuellement :
```bash
chmod 644 ./generated-entities/*.php
chmod 755 ./generated-entities/
```

## 📚 Voir aussi

- [Documentation Docker](docker/README.md)
- [Guide d'architecture](docs/ARCHITECTURE.md)
- [Dépannage](docs/TROUBLESHOOTING.md)