# Security Guide - ReverseEngineeringBundle

This comprehensive security guide covers best practices, security considerations, and protective measures when using the ReverseEngineeringBundle in production environments. Learn how to secure your database connections, protect sensitive data, and implement security controls.

## 📋 Table of Contents

- [Security Overview](#security-overview)
- [Database Security](#database-security)
- [Configuration Security](#configuration-security)
- [Access Control](#access-control)
- [Data Protection](#data-protection)
- [Production Security](#production-security)
- [Security Auditing](#security-auditing)
- [Incident Response](#incident-response)

## 🔒 Security Overview

### Security Principles

The ReverseEngineeringBundle follows security-by-design principles:

- **Principle of Least Privilege**: Minimal database permissions required
- **Defense in Depth**: Multiple layers of security controls
- **Secure by Default**: Safe default configurations
- **Data Minimization**: Only necessary data is accessed
- **Audit Trail**: Comprehensive logging of operations

### Security Scope

#### What the Bundle Protects
- ✅ Database connection credentials
- ✅ Generated entity code integrity
- ✅ File system access controls
- ✅ Configuration data security
- ✅ Process isolation in Docker environments

#### What Requires Additional Protection
- ⚠️ Network communication encryption
- ⚠️ Application-level authentication
- ⚠️ Business logic authorization
- ⚠️ Generated entity usage in applications
- ⚠️ Long-term credential management

### Threat Model

#### Potential Threats
1. **Database Credential Exposure**: Unauthorized access to database credentials
2. **SQL Injection**: Malicious SQL injection through table/column names
3. **File System Access**: Unauthorized file system access during generation
4. **Information Disclosure**: Sensitive schema information exposure
5. **Code Injection**: Malicious code injection in generated entities
6. **Privilege Escalation**: Unauthorized database privilege escalation

#### Risk Assessment Matrix

| Threat | Likelihood | Impact | Risk Level | Mitigation Priority |
|--------|------------|--------|------------|-------------------|
| Credential Exposure | Medium | High | High | Critical |
| SQL Injection | Low | Medium | Medium | High |
| File System Access | Low | Medium | Medium | Medium |
| Information Disclosure | Medium | Low | Low | Low |
| Code Injection | Very Low | High | Medium | High |
| Privilege Escalation | Low | High | Medium | High |

## 🗄️ Database Security

### Connection Security

#### Secure Connection Configuration

```yaml
# config/packages/reverse_engineering.yaml
reverse_engineering:
    database:
        driver: pdo_mysql
        host: '%env(DB_HOST)%'
        port: '%env(int:DB_PORT)%'
        dbname: '%env(DB_NAME)%'
        user: '%env(DB_USER)%'
        password: '%env(DB_PASSWORD)%'
        charset: utf8mb4
        options:
            # Enable SSL/TLS encryption
            1009: true  # PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT
            1010: '/path/to/ca-cert.pem'  # PDO::MYSQL_ATTR_SSL_CA
            1011: '/path/to/client-cert.pem'  # PDO::MYSQL_ATTR_SSL_CERT
            1012: '/path/to/client-key.pem'  # PDO::MYSQL_ATTR_SSL_KEY
            # Disable local file loading
            1013: false  # PDO::MYSQL_ATTR_LOCAL_INFILE
```

#### Database User Privileges

Create a dedicated database user with minimal privileges:

```sql
-- Create dedicated user for reverse engineering
CREATE USER 'reverse_eng_user'@'%' IDENTIFIED BY 'strong_random_password';

-- Grant only necessary SELECT privileges
GRANT SELECT ON information_schema.tables TO 'reverse_eng_user'@'%';
GRANT SELECT ON information_schema.columns TO 'reverse_eng_user'@'%';
GRANT SELECT ON information_schema.key_column_usage TO 'reverse_eng_user'@'%';
GRANT SELECT ON information_schema.table_constraints TO 'reverse_eng_user'@'%';
GRANT SELECT ON information_schema.referential_constraints TO 'reverse_eng_user'@'%';

-- Grant SELECT on application tables only
GRANT SELECT ON myapp.* TO 'reverse_eng_user'@'%';

-- Explicitly deny dangerous privileges
REVOKE ALL PRIVILEGES ON mysql.* FROM 'reverse_eng_user'@'%';
REVOKE ALL PRIVILEGES ON performance_schema.* FROM 'reverse_eng_user'@'%';
REVOKE ALL PRIVILEGES ON sys.* FROM 'reverse_eng_user'@'%';

-- Apply changes
FLUSH PRIVILEGES;
```

#### Connection Validation

```php
// src/Security/DatabaseConnectionValidator.php
namespace App\Security;

use App\Exception\DatabaseConnectionException;
use Doctrine\DBAL\Connection;

class DatabaseConnectionValidator
{
    public function __construct(
        private Connection $connection
    ) {}
    
    public function validateSecureConnection(): void
    {
        // Verify SSL/TLS encryption is enabled
        $sslStatus = $this->connection->executeQuery("SHOW STATUS LIKE 'Ssl_cipher'")->fetchAssociative();
        
        if (empty($sslStatus['Value'])) {
            throw new DatabaseConnectionException(
                'Database connection is not encrypted. SSL/TLS is required for security.'
            );
        }
        
        // Verify user privileges are minimal
        $this->validateUserPrivileges();
        
        // Check for dangerous configurations
        $this->checkDangerousSettings();
    }
    
    private function validateUserPrivileges(): void
    {
        $grants = $this->connection->executeQuery("SHOW GRANTS")->fetchAllAssociative();
        
        foreach ($grants as $grant) {
            $grantText = $grant['Grants for reverse_eng_user@%'] ?? '';
            
            // Check for dangerous privileges
            $dangerousPrivileges = ['INSERT', 'UPDATE', 'DELETE', 'CREATE', 'DROP', 'ALTER', 'SUPER'];
            
            foreach ($dangerousPrivileges as $privilege) {
                if (stripos($grantText, $privilege) !== false && stripos($grantText, 'information_schema') === false) {
                    throw new DatabaseConnectionException(
                        "Database user has dangerous privilege: {$privilege}. Only SELECT privileges should be granted."
                    );
                }
            }
        }
    }
    
    private function checkDangerousSettings(): void
    {
        // Check for local file loading capability
        $localInfile = $this->connection->executeQuery("SHOW VARIABLES LIKE 'local_infile'")->fetchAssociative();
        
        if ($localInfile && $localInfile['Value'] === 'ON') {
            throw new DatabaseConnectionException(
                'local_infile is enabled. This poses a security risk and should be disabled.'
            );
        }
    }
}
```

### SQL Injection Prevention

#### Input Sanitization

```php
// src/Security/SqlInjectionPrevention.php
namespace App\Security;

class SqlInjectionPrevention
{
    private const ALLOWED_IDENTIFIER_PATTERN = '/^[a-zA-Z_][a-zA-Z0-9_]*$/';
    private const MAX_IDENTIFIER_LENGTH = 64;
    
    public function validateTableName(string $tableName): void
    {
        $this->validateIdentifier($tableName, 'table name');
    }
    
    public function validateColumnName(string $columnName): void
    {
        $this->validateIdentifier($columnName, 'column name');
    }
    
    public function validateSchemaName(string $schemaName): void
    {
        $this->validateIdentifier($schemaName, 'schema name');
    }
    
    private function validateIdentifier(string $identifier, string $type): void
    {
        // Check length
        if (strlen($identifier) > self::MAX_IDENTIFIER_LENGTH) {
            throw new \InvalidArgumentException(
                "Invalid {$type}: exceeds maximum length of " . self::MAX_IDENTIFIER_LENGTH
            );
        }
        
        // Check pattern
        if (!preg_match(self::ALLOWED_IDENTIFIER_PATTERN, $identifier)) {
            throw new \InvalidArgumentException(
                "Invalid {$type}: contains illegal characters. Only alphanumeric characters and underscores are allowed."
            );
        }
        
        // Check for SQL keywords
        $this->checkSqlKeywords($identifier, $type);
        
        // Check for common injection patterns
        $this->checkInjectionPatterns($identifier, $type);
    }
    
    private function checkSqlKeywords(string $identifier, string $type): void
    {
        $sqlKeywords = [
            'SELECT', 'INSERT', 'UPDATE', 'DELETE', 'DROP', 'CREATE', 'ALTER',
            'UNION', 'WHERE', 'ORDER', 'GROUP', 'HAVING', 'LIMIT', 'OFFSET',
            'FROM', 'INTO', 'VALUES', 'SET', 'JOIN', 'ON', 'AS', 'AND', 'OR'
        ];
        
        if (in_array(strtoupper($identifier), $sqlKeywords)) {
            throw new \InvalidArgumentException(
                "Invalid {$type}: '{$identifier}' is a reserved SQL keyword."
            );
        }
    }
    
    private function checkInjectionPatterns(string $identifier, string $type): void
    {
        $injectionPatterns = [
            '/[\'";]/',           // Quotes and semicolons
            '/--/',               // SQL comments
            '/\/\*/',             // Block comments
            '/\*\//',             // Block comment end
            '/\bunion\b/i',       // UNION keyword
            '/\bselect\b/i',      // SELECT keyword
            '/\bdrop\b/i',        // DROP keyword
            '/\bexec\b/i',        // EXEC keyword
            '/\bxp_\w+/i',        // Extended procedures
        ];
        
        foreach ($injectionPatterns as $pattern) {
            if (preg_match($pattern, $identifier)) {
                throw new \InvalidArgumentException(
                    "Invalid {$type}: contains potential SQL injection pattern."
                );
            }
        }
    }
    
    public function sanitizeForQuery(string $identifier): string
    {
        // Validate first
        $this->validateIdentifier($identifier, 'identifier');
        
        // Escape identifier for safe use in queries
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}
```

### Database Audit Logging

```php
// src/Security/DatabaseAuditLogger.php
namespace App\Security;

use Psr\Log\LoggerInterface;

class DatabaseAuditLogger
{
    public function __construct(
        private LoggerInterface $auditLogger
    ) {}
    
    public function logDatabaseAccess(string $operation, array $context = []): void
    {
        $this->auditLogger->info('Database access', [
            'operation' => $operation,
            'timestamp' => date('Y-m-d H:i:s'),
            'user' => $this->getCurrentUser(),
            'ip_address' => $this->getClientIpAddress(),
            'context' => $context
        ]);
    }
    
    public function logTableAccess(string $tableName, string $operation): void
    {
        $this->auditLogger->info('Table access', [
            'table' => $tableName,
            'operation' => $operation,
            'timestamp' => date('Y-m-d H:i:s'),
            'user' => $this->getCurrentUser(),
            'ip_address' => $this->getClientIpAddress()
        ]);
    }
    
    public function logSecurityEvent(string $event, array $details = []): void
    {
        $this->auditLogger->warning('Security event', [
            'event' => $event,
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s'),
            'user' => $this->getCurrentUser(),
            'ip_address' => $this->getClientIpAddress()
        ]);
    }
    
    private function getCurrentUser(): string
    {
        // Get current user from security context
        return $_SERVER['USER'] ?? 'unknown';
    }
    
    private function getClientIpAddress(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] 
            ?? $_SERVER['HTTP_CLIENT_IP'] 
            ?? $_SERVER['REMOTE_ADDR'] 
            ?? 'unknown';
    }
}
```

## ⚙️ Configuration Security

### Environment Variable Security

#### Secure Environment Configuration

```bash
# .env.local (never commit to version control)
# Use strong, unique passwords
DB_PASSWORD=SecureRandomPassword123!@#

# Use specific database users
DB_USER=reverse_eng_user

# Use encrypted connections
DB_SSL_MODE=REQUIRED
DB_SSL_CA=/path/to/ca-cert.pem
DB_SSL_CERT=/path/to/client-cert.pem
DB_SSL_KEY=/path/to/client-key.pem

# Restrict file permissions
ENTITY_OUTPUT_DIR=/secure/path/entities
ENTITY_FILE_PERMISSIONS=0644
ENTITY_DIR_PERMISSIONS=0755
```

#### Configuration Validation

```php
// src/Security/ConfigurationValidator.php
namespace App\Security;

class ConfigurationValidator
{
    public function validateConfiguration(array $config): void
    {
        $this->validateDatabaseConfig($config['database'] ?? []);
        $this->validateGenerationConfig($config['generation'] ?? []);
        $this->validateSecuritySettings($config);
    }
    
    private function validateDatabaseConfig(array $dbConfig): void
    {
        // Validate required fields
        $requiredFields = ['driver', 'host', 'dbname', 'user', 'password'];
        foreach ($requiredFields as $field) {
            if (empty($dbConfig[$field])) {
                throw new \InvalidArgumentException("Database configuration missing required field: {$field}");
            }
        }
        
        // Validate password strength
        $this->validatePasswordStrength($dbConfig['password']);
        
        // Validate SSL configuration
        if (isset($dbConfig['options'])) {
            $this->validateSslConfiguration($dbConfig['options']);
        }
    }
    
    private function validatePasswordStrength(string $password): void
    {
        if (strlen($password) < 12) {
            throw new \InvalidArgumentException('Database password must be at least 12 characters long');
        }
        
        $hasUpper = preg_match('/[A-Z]/', $password);
        $hasLower = preg_match('/[a-z]/', $password);
        $hasDigit = preg_match('/\d/', $password);
        $hasSpecial = preg_match('/[^A-Za-z0-9]/', $password);
        
        if (!($hasUpper && $hasLower && $hasDigit && $hasSpecial)) {
            throw new \InvalidArgumentException(
                'Database password must contain uppercase, lowercase, digit, and special characters'
            );
        }
    }
    
    private function validateSslConfiguration(array $options): void
    {
        // Check if SSL is enabled
        if (!isset($options[1009]) || !$options[1009]) {
            throw new \InvalidArgumentException('SSL/TLS encryption must be enabled for database connections');
        }
        
        // Validate certificate paths
        $certPaths = [1010, 1011, 1012]; // CA, cert, key
        foreach ($certPaths as $option) {
            if (isset($options[$option]) && !file_exists($options[$option])) {
                throw new \InvalidArgumentException("SSL certificate file not found: {$options[$option]}");
            }
        }
    }
    
    private function validateGenerationConfig(array $genConfig): void
    {
        // Validate output directory security
        if (isset($genConfig['output_dir'])) {
            $this->validateOutputDirectory($genConfig['output_dir']);
        }
        
        // Validate namespace security
        if (isset($genConfig['namespace'])) {
            $this->validateNamespace($genConfig['namespace']);
        }
    }
    
    private function validateOutputDirectory(string $outputDir): void
    {
        // Check if directory is within project bounds
        $realPath = realpath($outputDir);
        $projectRoot = realpath(__DIR__ . '/../../');
        
        if ($realPath && strpos($realPath, $projectRoot) !== 0) {
            throw new \InvalidArgumentException('Output directory must be within project root for security');
        }
        
        // Check directory permissions
        if (is_dir($outputDir) && !is_writable($outputDir)) {
            throw new \InvalidArgumentException('Output directory is not writable');
        }
    }
    
    private function validateNamespace(string $namespace): void
    {
        // Validate namespace format
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_\\\\]*$/', $namespace)) {
            throw new \InvalidArgumentException('Invalid namespace format');
        }
        
        // Prevent dangerous namespaces
        $dangerousNamespaces = ['System', 'Windows', 'Microsoft', 'PHP'];
        foreach ($dangerousNamespaces as $dangerous) {
            if (stripos($namespace, $dangerous) === 0) {
                throw new \InvalidArgumentException("Namespace cannot start with: {$dangerous}");
            }
        }
    }
}
```

### Secrets Management

#### Secure Credential Storage

```php
// src/Security/CredentialManager.php
namespace App\Security;

class CredentialManager
{
    private const ENCRYPTION_METHOD = 'AES-256-GCM';
    
    public function __construct(
        private string $encryptionKey
    ) {}
    
    public function encryptCredential(string $credential): string
    {
        $iv = random_bytes(16);
        $tag = '';
        
        $encrypted = openssl_encrypt(
            $credential,
            self::ENCRYPTION_METHOD,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        if ($encrypted === false) {
            throw new \RuntimeException('Failed to encrypt credential');
        }
        
        return base64_encode($iv . $tag . $encrypted);
    }
    
    public function decryptCredential(string $encryptedCredential): string
    {
        $data = base64_decode($encryptedCredential);
        
        if ($data === false || strlen($data) < 32) {
            throw new \InvalidArgumentException('Invalid encrypted credential format');
        }
        
        $iv = substr($data, 0, 16);
        $tag = substr($data, 16, 16);
        $encrypted = substr($data, 32);
        
        $decrypted = openssl_decrypt(
            $encrypted,
            self::ENCRYPTION_METHOD,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        if ($decrypted === false) {
            throw new \RuntimeException('Failed to decrypt credential');
        }
        
        return $decrypted;
    }
    
    public function rotateEncryptionKey(string $newKey): void
    {
        // Implementation for key rotation
        // This would involve re-encrypting all stored credentials
    }
}
```

## 🔐 Access Control

### Role-Based Access Control

#### Security Roles Definition

```php
// src/Security/ReverseEngineeringRoles.php
namespace App\Security;

class ReverseEngineeringRoles
{
    public const ROLE_REVERSE_ENGINEER = 'ROLE_REVERSE_ENGINEER';
    public const ROLE_SCHEMA_VIEWER = 'ROLE_SCHEMA_VIEWER';
    public const ROLE_ENTITY_GENERATOR = 'ROLE_ENTITY_GENERATOR';
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    
    public static function getHierarchy(): array
    {
        return [
            self::ROLE_ADMIN => [
                self::ROLE_REVERSE_ENGINEER,
                self::ROLE_SCHEMA_VIEWER,
                self::ROLE_ENTITY_GENERATOR
            ],
            self::ROLE_REVERSE_ENGINEER => [
                self::ROLE_SCHEMA_VIEWER,
                self::ROLE_ENTITY_GENERATOR
            ],
            self::ROLE_ENTITY_GENERATOR => [
                self::ROLE_SCHEMA_VIEWER
            ]
        ];
    }
    
    public static function getPermissions(): array
    {
        return [
            self::ROLE_SCHEMA_VIEWER => [
                'view_schema',
                'list_tables',
                'view_table_structure'
            ],
            self::ROLE_ENTITY_GENERATOR => [
                'generate_entities',
                'write_entity_files',
                'create_repositories'
            ],
            self::ROLE_REVERSE_ENGINEER => [
                'analyze_database',
                'extract_metadata',
                'configure_generation'
            ],
            self::ROLE_ADMIN => [
                'manage_configuration',
                'view_audit_logs',
                'manage_users'
            ]
        ];
    }
}
```

#### Access Control Service

```php
// src/Security/AccessControlService.php
namespace App\Security;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AccessControlService
{
    public function __construct(
        private Security $security
    ) {}
    
    public function checkSchemaAccess(string $tableName): void
    {
        if (!$this->security->isGranted(ReverseEngineeringRoles::ROLE_SCHEMA_VIEWER)) {
            throw new AccessDeniedException('Insufficient privileges to view schema');
        }
        
        // Additional table-specific access control
        $this->checkTableAccess($tableName);
    }
    
    public function checkGenerationAccess(array $options): void
    {
        if (!$this->security->isGranted(ReverseEngineeringRoles::ROLE_ENTITY_GENERATOR)) {
            throw new AccessDeniedException('Insufficient privileges to generate entities');
        }
        
        // Check specific generation options
        $this->validateGenerationOptions($options);
    }
    
    public function checkConfigurationAccess(): void
    {
        if (!$this->security->isGranted(ReverseEngineeringRoles::ROLE_ADMIN)) {
            throw new AccessDeniedException('Insufficient privileges to modify configuration');
        }
    }
    
    private function checkTableAccess(string $tableName): void
    {
        // Implement table-specific access control
        $restrictedTables = ['user_passwords', 'api_keys', 'audit_logs'];
        
        if (in_array($tableName, $restrictedTables)) {
            if (!$this->security->isGranted(ReverseEngineeringRoles::ROLE_ADMIN)) {
                throw new AccessDeniedException("Access denied to restricted table: {$tableName}");
            }
        }
    }
    
    private function validateGenerationOptions(array $options): void
    {
        // Validate output directory access
        if (isset($options['output_dir'])) {
            $this->validateOutputDirectoryAccess($options['output_dir']);
        }
        
        // Validate namespace permissions
        if (isset($options['namespace'])) {
            $this->validateNamespaceAccess($options['namespace']);
        }
    }
    
    private function validateOutputDirectoryAccess(string $outputDir): void
    {
        // Restrict output to specific directories
        $allowedDirectories = [
            'src/Entity',
            'src/Generated',
            'var/generated'
        ];
        
        $isAllowed = false;
        foreach ($allowedDirectories as $allowed) {
            if (strpos($outputDir, $allowed) === 0) {
                $isAllowed = true;
                break;
            }
        }
        
        if (!$isAllowed) {
            throw new AccessDeniedException("Output directory not allowed: {$outputDir}");
        }
    }
    
    private function validateNamespaceAccess(string $namespace): void
    {
        // Restrict certain namespaces
        $restrictedNamespaces = ['System', 'Security', 'Admin'];
        
        foreach ($restrictedNamespaces as $restricted) {
            if (stripos($namespace, $restricted) !== false) {
                if (!$this->security->isGranted(ReverseEngineeringRoles::ROLE_ADMIN)) {
                    throw new AccessDeniedException("Namespace not allowed: {$namespace}");
                }
            }
        }
    }
}
```

### Command Security

#### Secured Command Implementation

```php
// src/Command/SecureReverseGenerateCommand.php
namespace App\Command;

use App\Security\AccessControlService;
use App\Security\DatabaseAuditLogger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'reverse:generate-secure',
    description: 'Generate entities with security controls'
)]
class SecureReverseGenerateCommand extends Command
{
    public function __construct(
        private AccessControlService $accessControl,
        private DatabaseAuditLogger $auditLogger,
        private ReverseEngineeringService $reverseService
    ) {
        parent::__construct();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            // Log command execution
            $this->auditLogger->logDatabaseAccess('reverse_generate_command', [
                'user' => get_current_user(),
                'arguments' => $input->getArguments(),
                'options' => $input->getOptions()
            ]);
            
            // Check access permissions
            $this->accessControl->checkGenerationAccess($input->getOptions());
            
            // Validate input parameters
            $this->validateInputSecurity($input);
            
            // Execute generation with security monitoring
            $result = $this->executeSecureGeneration($input, $output);
            
            // Log successful completion
            $this->auditLogger->logDatabaseAccess('reverse_generate_completed', [
                'entities_generated' => count($result['entities'] ?? []),
                'files_created' => count($result['files'] ?? [])
            ]);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            // Log security events
            $this->auditLogger->logSecurityEvent('reverse_generate_failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $output->writeln('<error>Generation failed: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
    
    private function validateInputSecurity(InputInterface $input): void
    {
        // Validate table names for SQL injection
        $tables = $input->getOption('tables') ?? [];
        foreach ($tables as $table) {
            $this->sqlInjectionPrevention->validateTableName($table);
        }
        
        // Validate exclude patterns
        $excludes = $input->getOption('exclude') ?? [];
        foreach ($excludes as $exclude) {
            $this->sqlInjectionPrevention->validateTableName($exclude);
        }
        
        // Validate namespace
        $namespace = $input->getOption('namespace');
        if ($namespace) {
            $this->validateNamespaceSecurity($namespace);
        }
        
        // Validate output directory
        $outputDir = $input->getOption('output-dir');
        if ($outputDir) {
            $this->validateOutputDirectorySecurity($outputDir);
        }
    }
    
    private function executeSecureGeneration(InputInterface $input, OutputInterface $output): array
    {
        // Monitor resource usage during generation
        $startMemory = memory_get_usage(true);
        $startTime = microtime(true);
        
        try {
            $result = $this->reverseService->generateEntities([
                'tables' => $input->getOption('tables') ?? [],
                'exclude' => $input->getOption('exclude') ?? [],
                'namespace' => $input->getOption('namespace'),
                'output_dir' => $input->getOption('output-dir'),
                'force' => $input->getOption('force'),
                'dry_run' => $input->getOption('dry-run')
            ]);
            
            // Log resource usage
            $endMemory = memory_get_usage(true);
            $endTime = microtime(true);
            
            $this->auditLogger->logDatabaseAccess('generation_metrics', [
                'memory_used' => $endMemory - $startMemory,
                'execution_time' => $endTime - $startTime,
                'peak_memory' => memory_get_peak_usage(true)
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            // Log detailed error information
            $this->auditLogger->logSecurityEvent('generation_error', [
                'error_type' => get_class($e),
                'error_message' => $e->getMessage(),
                'memory_usage' => memory_get_usage(true),
                'execution_time' => microtime(true) - $startTime
            ]);
            
            throw $e;
        }
    }
}
```

## 🛡️ Data Protection

### Sensitive Data Handling

#### Data Classification

```php
// src/Security/DataClassifier.php
namespace App\Security;

class DataClassifier
{
    public const CLASSIFICATION_PUBLIC = 'public';
    public const CLASSIFICATION_INTERNAL = 'internal';
    public const CLASSIFICATION_CONFIDENTIAL = 'confidential';
    public const CLASSIFICATION_RESTRICTED = 'restricted';
    
    private array $sensitivePatterns = [
        'password' => self::CLASSIFICATION_RESTRICTED,
        'secret' => self::CLASSIFICATION_RESTRICTED,
        'token' => self::CLASSIFICATION_RESTRICTED,
        'key' => self::CLASSIFICATION_RESTRICTED,
        'ssn' => self::CLASSIFICATION_RESTRICTED,
        'social_security' => self::CLASSIFICATION_RESTRICTED,
        'credit_card' => self::CLASSIFICATION_RESTRICTED,
        'bank_account' => self::CLASSIFICATION_RESTRICTED,
        'email' => self::CLASSIFICATION_CONFIDENTIAL,
        'phone' => self::CLASSIFICATION_CONFIDENTIAL,
        'address' => self::CLASSIFICATION_CONFIDENTIAL,
        'salary' => self::CLASSIFICATION_CONFIDENTIAL,
        'birth_date' => self::CLASSIFICATION_CONFIDENTIAL
    ];
    
    public function classifyColumn(string $columnName, string $dataType): string
    {
        $lowerColumnName = strtolower($columnName);
        
        // Check for sensitive patterns
        foreach ($this->sensitivePatterns as $pattern => $classification) {
            if (strpos($lowerColumnName, $pattern) !== false) {
                return $classification;
            }
        }
        
        // Classify by data type
        return $this->classifyByDataType($dataType);
    }
    
    private function classifyByDataType(string $dataType): string
    {
        $sensitiveTypes = ['blob', 'text', 'longtext'];
        
        if (in_array(strtolower($dataType), $sensitiveTypes)) {
            return self::CLASSIFICATION_INTERNAL;
        }
        
        return self::CLASSIFICATION_PUBLIC;
    }
    
    public