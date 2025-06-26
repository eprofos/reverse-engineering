# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned Features
- Automatic OneToMany relationship detection and generation
- ManyToMany relationship support with junction table detection
- Test fixture generation from existing database data
- Web administration interface for entity management
- Enhanced ENUM/SET type support with validation
- Custom type mapping configuration
- Performance optimizations for very large databases

## [0.1.0] - 2025-06-25

### Added

#### Core Features
- **Automatic Doctrine Entity Generation** from existing database schemas
- **Multi-Database Support**: MySQL, PostgreSQL, SQLite, MariaDB with comprehensive compatibility
- **Robust CLI Interface** with `reverse:generate` command and extensive options
- **Advanced Command Options**:
  - Table selection (`--tables`) and exclusion (`--exclude`)
  - Custom namespace configuration (`--namespace`)
  - Custom output directory (`--output-dir`)
  - Dry-run mode for preview (`--dry-run`)
  - Force overwrite existing files (`--force`)
  - Verbose output for debugging (`--verbose`)

#### Type Mapping and Data Handling
- **Intelligent Type Mapping** from database types to PHP/Doctrine types
- **Complete MySQL Type Support**: INT, VARCHAR, TEXT, DECIMAL, DATETIME, BOOLEAN, JSON, BLOB
- **PostgreSQL Type Support**: INTEGER, VARCHAR, TEXT, NUMERIC, TIMESTAMP, BOOLEAN, JSON, JSONB, UUID
- **SQLite Type Support**: INTEGER, REAL, TEXT, BLOB
- **Advanced Type Handling**:
  - ENUM types with value documentation
  - SET types with value documentation
  - DECIMAL types with precision and scale preservation
  - DateTime types with timezone awareness
  - JSON types with array mapping

#### Relationship Detection
- **Automatic ManyToOne Relationship Generation** from foreign key constraints
- **Foreign Key Constraint Analysis** with ON DELETE and ON UPDATE actions
- **Self-Referencing Relationship Support** for hierarchical data structures
- **Composite Key Support** for complex primary key scenarios

#### Entity and Repository Generation
- **PHP 8+ Attribute Support** as default with annotation fallback option
- **Automatic Repository Generation** with customizable templates
- **Intelligent Property Naming** with camelCase conversion from snake_case
- **Nullable Property Handling** with proper PHP type declarations
- **Default Value Preservation** from database schema
- **Getter/Setter Generation** with proper return types and fluent interfaces

#### Architecture and Design
- **Modular Service Architecture** with 5 core services:
  - `DatabaseAnalyzer` - Database structure analysis and connection management
  - `MetadataExtractor` - Schema metadata extraction and transformation
  - `EntityGenerator` - PHP entity code generation with Twig templates
  - `FileWriter` - Secure file writing with conflict management
  - `ReverseEngineeringService` - Process orchestration and workflow management
- **Comprehensive Exception Handling** with specialized exception types:
  - `DatabaseConnectionException` - Database connectivity issues
  - `MetadataExtractionException` - Schema analysis errors
  - `EntityGenerationException` - Code generation failures
  - `FileWriteException` - File system operation errors
- **Flexible Configuration System** via YAML configuration files
- **Customizable Twig Templates** for entity and repository generation

#### Testing and Quality Assurance
- **Comprehensive Test Suite** with 144+ tests across multiple categories:
  - Unit tests for all core services with extensive mocking
  - Integration tests with real database scenarios
  - Performance tests for large database handling
  - Command tests for CLI interface validation
  - Exception tests for error handling verification
- **Multi-Database Integration Testing** with SQLite, MySQL, and PostgreSQL
- **Code Coverage > 95%** with detailed HTML and Clover reports
- **Static Analysis** with PHPStan level 8 compliance
- **Code Style Enforcement** with PHP-CS-Fixer and PSR-12 standards

#### Docker Integration and Sakila Environment
- **Complete Docker Environment** with MySQL 8.0 and PHP 8.2
- **Sakila Database Integration** for realistic testing scenarios:
  - 16+ tables with complex relationships
  - Real-world data types including ENUM, SET, DECIMAL
  - Foreign key constraints and composite indexes
  - Performance testing with actual data
- **Automated Docker Workflows**:
  - `docker-test.sh` utility script for common operations
  - Automated entity generation and copying
  - Integration test execution
  - Environment management and cleanup
- **Generate-and-Copy Command** for seamless workflow:
  - Automatic entity generation in Docker container
  - File copying to host system with permission correction
  - Syntax validation and error reporting
  - Detailed operation statistics and reporting

#### Documentation and Developer Experience
- **Comprehensive Documentation** with 4,500+ lines across 14 files:
  - Complete API documentation with examples
  - Architecture guide with design patterns
  - Troubleshooting guide with common solutions
  - Advanced usage scenarios and customization
  - Docker setup and configuration guides
- **Practical Examples** with real-world database schemas
- **Step-by-Step Tutorials** for common use cases
- **Performance Benchmarks** and optimization guidelines

### Technical Specifications

#### Compatibility Matrix
- **PHP**: 8.1+ with required extensions (PDO, MySQL, PostgreSQL, SQLite)
- **Symfony**: 7.0+ with full framework integration
- **Doctrine DBAL**: 3.0+ for database abstraction
- **Doctrine ORM**: 2.15+ for entity management

#### Database Support Details
- **MySQL** 5.7+ with `pdo_mysql` driver - Full feature support
- **PostgreSQL** 12+ with `pdo_pgsql` driver - Full feature support
- **SQLite** 3.25+ with `pdo_sqlite` driver - Full feature support
- **MariaDB** 10.3+ with `pdo_mysql` driver - Full feature support

#### Performance Metrics
- **Table Analysis**: < 1 second for 100 tables
- **Entity Generation**: < 10 seconds for 50 entities
- **Large Table Handling**: < 2 seconds for tables with 50+ columns
- **Memory Usage**: < 50MB for 30 entities with relationships
- **Docker Environment**: < 30 seconds for complete Sakila generation

#### Code Quality Metrics
- **PHPStan**: Level 8 compliance with zero errors
- **Test Coverage**: 95%+ with comprehensive edge case testing
- **Code Style**: PSR-12 compliant with automated formatting
- **Documentation Coverage**: 100% of public APIs documented
- **Performance Tests**: Automated benchmarking for regression detection

### Configuration Options

#### Database Configuration
```yaml
reverse_engineering:
    database:
        driver: pdo_mysql|pdo_pgsql|pdo_sqlite
        host: string
        port: integer
        dbname: string
        user: string
        password: string
        charset: string
        options: array
```

#### Generation Configuration
```yaml
reverse_engineering:
    generation:
        namespace: string
        output_dir: string
        generate_repository: boolean
        use_annotations: boolean
        tables: array
        exclude_tables: array
```

#### Template Configuration
```yaml
reverse_engineering:
    templates:
        entity: string
        repository: string
```

### Known Limitations

#### Current Version Constraints
- **OneToMany Relations**: Limited automatic detection, manual configuration recommended
- **ManyToMany Relations**: Not automatically supported, requires manual setup after generation
- **Database Views**: Not supported in current version
- **Stored Procedures**: Not analyzed or considered during generation
- **CHECK Constraints**: Limited mapping to PHP validation constraints
- **Complex Indexes**: Basic index detection only, advanced index types not fully supported
- **Database Triggers**: Not analyzed or documented in generated entities

#### Workarounds and Recommendations
- **Manual Relationship Configuration**: Add OneToMany and ManyToMany relationships manually after initial generation
- **Custom Validation**: Implement Symfony validation constraints manually for complex business rules
- **View Handling**: Create dedicated entities manually for database views
- **Performance Optimization**: Use table filtering and batch processing for very large databases (100+ tables)
- **Complex Schema Handling**: Generate entities in modules using namespace and directory organization

### Migration and Upgrade Notes

#### From Legacy Systems
- **Backup Strategy**: Always backup existing entities before using `--force` option
- **Incremental Migration**: Use table selection to migrate database modules incrementally
- **Namespace Organization**: Organize generated entities using custom namespaces for better structure
- **Validation Process**: Use dry-run mode to preview changes before applying them

#### Best Practices for Production Use
- **Environment Configuration**: Use environment variables for database credentials
- **Exclusion Lists**: Configure system table exclusions to avoid unnecessary entity generation
- **Code Review**: Review generated entities for business logic requirements
- **Testing Integration**: Validate generated entities with Doctrine schema validation
- **Performance Monitoring**: Monitor generation performance for large database schemas

---

## Version History Format

This project uses [Semantic Versioning](https://semver.org/spec/v2.0.0.html):

- **MAJOR**: Incompatible API changes
- **MINOR**: Backward-compatible functionality additions
- **PATCH**: Backward-compatible bug fixes

## Change Categories

- **Added**: New features and functionality
- **Changed**: Changes to existing functionality
- **Deprecated**: Soon-to-be removed features
- **Removed**: Removed features
- **Fixed**: Bug fixes and corrections
- **Security**: Security vulnerability fixes

## Links and References

- [Unreleased]: https://github.com/eprofos/reverse-engineering-bundle/compare/v0.1.0...HEAD
- [0.1.0]: https://github.com/eprofos/reverse-engineering-bundle/releases/tag/v0.1.0
- [Keep a Changelog]: https://keepachangelog.com/en/1.0.0/
- [Semantic Versioning]: https://semver.org/spec/v2.0.0.html

---

**This changelog is maintained to provide transparency about the evolution of the ReverseEngineeringBundle and help users understand the impact of updates on their projects.**