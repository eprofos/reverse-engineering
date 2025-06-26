# Contributing Guide - ReverseEngineeringBundle

Thank you for your interest in contributing to the ReverseEngineeringBundle! This guide will help you understand how to participate effectively in the development of this Symfony 7+ / PHP 8+ database reverse engineering bundle.

## 🎯 How to Contribute

### 🐛 Reporting Bugs

1. **Check existing issues** to avoid duplicates
2. **Use the bug report template** available on GitHub
3. **Provide detailed information**:
   - Bundle version
   - PHP and Symfony versions
   - Database system and version
   - Steps to reproduce the issue
   - Expected vs observed behavior
   - Complete error logs

### 💡 Proposing Features

1. **Open a discussion** before starting development
2. **Use the feature request template**
3. **Clearly describe**:
   - The problem it solves
   - The proposed solution
   - Alternatives considered
   - Impact on existing API

### 🔧 Contributing Code

1. **Fork** the repository
2. **Create a branch** for your feature:
   ```bash
   git checkout -b feature/my-new-feature
   ```
3. **Develop** following our standards
4. **Test** your code thoroughly
5. **Submit** a Pull Request

## 🏗️ Project Architecture

### Directory Structure

```
src/
├── Bundle/                 # Main Symfony bundle
├── Command/               # CLI commands
├── DependencyInjection/   # Container configuration
├── Exception/             # Custom exceptions
├── Resources/             # Templates and configuration
│   ├── config/           # Service configuration
│   └── templates/        # Twig templates
└── Service/              # Business services
    ├── DatabaseAnalyzer.php      # Database analysis
    ├── MetadataExtractor.php     # Metadata extraction
    ├── EntityGenerator.php       # Entity generation
    ├── FileWriter.php           # File writing
    └── ReverseEngineeringService.php # Orchestration

tests/
├── Unit/                 # Unit tests
├── Integration/          # Integration tests
├── Performance/          # Performance tests
└── Command/             # CLI command tests
```

### Core Services

1. **`DatabaseAnalyzer`**: Analyzes database structure
   - Connection and validation
   - Table listing
   - Schema metadata extraction

2. **`MetadataExtractor`**: Extracts and maps metadata
   - Data type mapping
   - Relationship detection
   - Name normalization

3. **`EntityGenerator`**: Generates PHP entity code
   - Twig template usage
   - Property and method generation
   - PHP 8+ attributes and annotation support

4. **`FileWriter`**: Writes files to disk
   - Conflict management
   - Permission validation
   - Directory creation

5. **`ReverseEngineeringService`**: Orchestrates the entire process
   - Service coordination
   - Option management
   - Global error handling

## 📋 Development Standards

### Code Standards

- **PSR-12**: PHP code style standard
- **PHPStan level 9**: Strict static analysis
- **PHP 8.1+**: Modern PHP features usage
- **Strict types**: `declare(strict_types=1)` mandatory
- **Documentation**: Complete PHPDoc for all public methods

### Naming Conventions

#### Classes
```php
// Services
class DatabaseAnalyzer
class MetadataExtractor

// Exceptions
class DatabaseConnectionException extends ReverseEngineeringException

// Commands
class ReverseGenerateCommand extends Command
```

#### Methods
```php
// Main actions
public function generateEntities(array $options = []): array
public function extractTableMetadata(string $tableName): array

// Validation/Testing
public function validateConnection(): bool
public function testDatabaseConnection(): bool

// Getters/Setters
public function getTableName(): string
public function setOutputDirectory(string $dir): void
```

#### Variables
```php
// CamelCase for variables
$tableName = 'users';
$entityMetadata = [];
$outputDirectory = '/path/to/entities';

// Snake_case for configuration keys
$config = [
    'output_dir' => '/path',
    'generate_repository' => true,
    'use_annotations' => false,
];
```

### Error Handling

#### Exception Hierarchy
```php
ReverseEngineeringException (base)
├── DatabaseConnectionException
├── MetadataExtractionException
├── EntityGenerationException
└── FileWriteException
```

#### Best Practices
```php
// ✅ Good: Specific exception with context
throw new EntityGenerationException(
    "Unable to generate entity for table '{$tableName}': {$reason}",
    0,
    $previousException
);

// ❌ Bad: Generic exception
throw new Exception('Error');
```

## 🧪 Testing and Quality

### Test Types

1. **Unit Tests** (`tests/Unit/`)
   - One test per service/class
   - Dependency mocking
   - Coverage of all execution paths

2. **Integration Tests** (`tests/Integration/`)
   - End-to-end tests
   - Real database (SQLite in-memory)
   - Complete user scenarios

3. **Performance Tests** (`tests/Performance/`)
   - Benchmarks with large tables
   - Memory usage measurement
   - Performance limit validation

4. **Command Tests** (`tests/Command/`)
   - CLI tests with `CommandTester`
   - Option and argument validation
   - Return code testing

### Running Tests

```bash
# All tests
./run-tests.sh

# Tests by category
vendor/bin/phpunit --testsuite=Unit
vendor/bin/phpunit --testsuite=Integration
vendor/bin/phpunit --testsuite=Performance

# With coverage
vendor/bin/phpunit --coverage-html=coverage/html

# Specific test
vendor/bin/phpunit tests/Unit/Service/DatabaseAnalyzerTest.php
```

### Quality Objectives

- **Code coverage**: > 95%
- **PHPStan**: Level 9 without errors
- **Tests**: All tests must pass
- **Performance**: Meet defined benchmarks

### Quality Tools

```bash
# Static analysis
composer phpstan

# Code style fixing
composer cs-fix

# Complete validation
./scripts/validate.sh
```

## 🔄 Pull Request Process

### Pre-submission Checklist

- [ ] **Code**: Follows PSR-12 standards
- [ ] **Tests**: All tests pass
- [ ] **Coverage**: New features are tested
- [ ] **PHPStan**: Level 9 without errors
- [ ] **Documentation**: PHPDoc up to date
- [ ] **CHANGELOG**: Entry added if necessary
- [ ] **Commits**: Clear and descriptive messages

### Commit Message Format

```bash
# Format: type(scope): description

# Types:
feat(generator): add OneToMany relationship support
fix(analyzer): fix PostgreSQL foreign key detection
docs(readme): update usage examples
test(unit): add tests for MetadataExtractor
refactor(service): simplify architecture
perf(analyzer): optimize queries for large tables
```

### Review Process

1. **Automatic validation**: CI/CD checks quality
2. **Maintainer review**: Code and architecture
3. **Manual testing**: Functional validation
4. **Merge**: After approval

## 🎯 Contribution Areas

### High Priority
- **OneToMany/ManyToMany Relations**: Automatic detection and generation
- **Oracle/SQL Server Support**: New database drivers
- **Performance**: Optimization for very large databases
- **Templates**: Advanced customization of generated entities

### Medium Priority
- **Web Interface**: Browser-based administration
- **Doctrine Migrations**: Automatic generation
- **REST API**: Integration with other tools
- **Fixtures**: Test data generation

### Low Priority
- **IDE Plugin**: PHPStorm/VSCode integration
- **View Support**: Read-only entity generation
- **Stored Procedures**: Mapping to services

## 📚 Resources

### Documentation
- [Detailed Architecture](./docs/ARCHITECTURE.md)
- [API Documentation](./docs/API.md)
- [Troubleshooting Guide](./docs/TROUBLESHOOTING.md)
- [Advanced Usage Cases](./docs/ADVANCED_USAGE.md)

### Development Tools
- [PHPUnit](https://phpunit.de/) - Testing framework
- [PHPStan](https://phpstan.org/) - Static analysis
- [PHP CS Fixer](https://cs.symfony.com/) - Code style
- [Doctrine DBAL](https://www.doctrine-project.org/projects/dbal.html) - Database abstraction

### Community
- [GitHub Issues](https://github.com/eprofos/reverse-engineering-bundle/issues)
- [Discussions](https://github.com/eprofos/reverse-engineering-bundle/discussions)
- [Pull Requests](https://github.com/eprofos/reverse-engineering-bundle/pulls)

## 🤝 Code of Conduct

This project adheres to the [Contributor Covenant Code of Conduct](https://www.contributor-covenant.org/version/2/1/code_of_conduct/).
By participating, you agree to uphold this code.

### Our Commitments

- **Respect**: Treat all contributors with respect
- **Inclusion**: Welcome all perspectives and experiences
- **Collaboration**: Work together toward common goals
- **Professionalism**: Maintain a professional environment

## 📞 Contact

- **Main Maintainer**: Eprofos Team
- **Issues**: [GitHub Issues](https://github.com/eprofos/reverse-engineering-bundle/issues)
- **Discussions**: [GitHub Discussions](https://github.com/eprofos/reverse-engineering-bundle/discussions)

---

**Thank you for contributing to the ReverseEngineeringBundle! Together, we're building a powerful tool for the Symfony community.** 🚀