# Advanced Usage Guide - ReverseEngineeringBundle

This comprehensive guide covers advanced scenarios, customization techniques, and enterprise-level usage patterns for the ReverseEngineeringBundle. Learn how to leverage the full power of the bundle for complex database reverse engineering tasks.

## 📋 Table of Contents

- [Enterprise Integration Patterns](#enterprise-integration-patterns)
- [Custom Template Development](#custom-template-development)
- [Advanced Configuration](#advanced-configuration)
- [Performance Optimization](#performance-optimization)
- [CI/CD Integration](#cicd-integration)
- [Multi-Database Scenarios](#multi-database-scenarios)
- [Custom Type Mapping](#custom-type-mapping)
- [Event-Driven Architecture](#event-driven-architecture)

## 🏢 Enterprise Integration Patterns

### Legacy System Migration Strategy

#### Phased Migration Approach

```bash
#!/bin/bash
# scripts/enterprise-migration.sh

echo "🏢 Enterprise Legacy Migration - Phase-by-Phase Approach"

# Phase 1: Core Business Entities
echo "📊 Phase 1: Core Business Entities"
php bin/console reverse:generate \
    --tables=customers --tables=products --tables=orders \
    --namespace="Enterprise\\Core\\Entity" \
    --output-dir="src/Enterprise/Core/Entity" \
    --force

# Phase 2: Financial Entities
echo "💰 Phase 2: Financial Entities"
php bin/console reverse:generate \
    --tables=invoices --tables=payments --tables=transactions \
    --namespace="Enterprise\\Finance\\Entity" \
    --output-dir="src/Enterprise/Finance/Entity" \
    --force

# Phase 3: Inventory Management
echo "📦 Phase 3: Inventory Management"
php bin/console reverse:generate \
    --tables=inventory --tables=warehouses --tables=stock_movements \
    --namespace="Enterprise\\Inventory\\Entity" \
    --output-dir="src/Enterprise/Inventory/Entity" \
    --force

# Phase 4: Human Resources
echo "👥 Phase 4: Human Resources"
php bin/console reverse:generate \
    --tables=employees --tables=departments --tables=payroll \
    --namespace="Enterprise\\HR\\Entity" \
    --output-dir="src/Enterprise/HR/Entity" \
    --force

# Phase 5: Reporting and Analytics
echo "📈 Phase 5: Reporting and Analytics"
php bin/console reverse:generate \
    --tables=reports --tables=analytics --tables=metrics \
    --namespace="Enterprise\\Analytics\\Entity" \
    --output-dir="src/Enterprise/Analytics/Entity" \
    --force

echo "✅ Enterprise migration completed successfully!"
```

#### Domain-Driven Design Integration

```php
// src/Enterprise/Core/Service/CustomerDomainService.php
namespace Enterprise\Core\Service;

use Enterprise\Core\Entity\Customer;
use Enterprise\Core\Repository\CustomerRepository;
use Enterprise\Core\ValueObject\CustomerId;
use Enterprise\Core\Event\CustomerCreatedEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CustomerDomainService
{
    public function __construct(
        private CustomerRepository $customerRepository,
        private EventDispatcherInterface $eventDispatcher
    ) {}
    
    public function createCustomer(array $customerData): Customer
    {
        // Apply business rules
        $this->validateCustomerData($customerData);
        
        // Create domain entity
        $customer = new Customer();
        $customer->setCustomerId(CustomerId::generate());
        $customer->setEmail($customerData['email']);
        $customer->setCompanyName($customerData['company_name']);
        
        // Apply domain logic
        $customer->assignDefaultCreditLimit();
        $customer->setCustomerStatus('active');
        
        // Persist entity
        $this->customerRepository->save($customer);
        
        // Dispatch domain event
        $this->eventDispatcher->dispatch(
            new CustomerCreatedEvent($customer),
            CustomerCreatedEvent::NAME
        );
        
        return $customer;
    }
    
    private function validateCustomerData(array $data): void
    {
        // Implement complex business validation rules
        if (empty($data['email'])) {
            throw new \InvalidArgumentException('Customer email is required');
        }
        
        if ($this->customerRepository->findByEmail($data['email'])) {
            throw new \DomainException('Customer with this email already exists');
        }
        
        // Additional business rules...
    }
}
```

### Microservices Architecture Integration

#### Service-Specific Entity Generation

```php
// src/Microservice/EntityGenerationOrchestrator.php
namespace App\Microservice;

use App\Service\ReverseEngineeringService;

class EntityGenerationOrchestrator
{
    private array $serviceConfigurations = [
        'user-service' => [
            'tables' => ['users', 'user_profiles', 'user_preferences'],
            'namespace' => 'UserService\\Entity',
            'output_dir' => 'microservices/user-service/src/Entity'
        ],
        'product-service' => [
            'tables' => ['products', 'categories', 'brands', 'product_images'],
            'namespace' => 'ProductService\\Entity',
            'output_dir' => 'microservices/product-service/src/Entity'
        ],
        'order-service' => [
            'tables' => ['orders', 'order_items', 'shipping_addresses'],
            'namespace' => 'OrderService\\Entity',
            'output_dir' => 'microservices/order-service/src/Entity'
        ],
        'payment-service' => [
            'tables' => ['payments', 'payment_methods', 'transactions'],
            'namespace' => 'PaymentService\\Entity',
            'output_dir' => 'microservices/payment-service/src/Entity'
        ]
    ];
    
    public function __construct(
        private ReverseEngineeringService $reverseService
    ) {}
    
    public function generateForAllServices(): array
    {
        $results = [];
        
        foreach ($this->serviceConfigurations as $serviceName => $config) {
            echo "🔄 Generating entities for {$serviceName}...\n";
            
            try {
                $result = $this->reverseService->generateEntities($config);
                $results[$serviceName] = [
                    'status' => 'success',
                    'entities_generated' => count($result['entities']),
                    'files_created' => count($result['files'])
                ];
                
                // Generate service-specific configurations
                $this->generateServiceConfiguration($serviceName, $config);
                
                echo "✅ {$serviceName}: Generated {$results[$serviceName]['entities_generated']} entities\n";
                
            } catch (\Exception $e) {
                $results[$serviceName] = [
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
                
                echo "❌ {$serviceName}: Failed - {$e->getMessage()}\n";
            }
        }
        
        return $results;
    }
    
    private function generateServiceConfiguration(string $serviceName, array $config): void
    {
        $configDir = dirname($config['output_dir']) . '/config';
        
        if (!is_dir($configDir)) {
            mkdir($configDir, 0755, true);
        }
        
        // Generate Doctrine configuration
        $doctrineConfig = [
            'doctrine' => [
                'orm' => [
                    'mappings' => [
                        $serviceName => [
                            'is_bundle' => false,
                            'type' => 'attribute',
                            'dir' => '%kernel.project_dir%/src/Entity',
                            'prefix' => $config['namespace'],
                            'alias' => ucfirst(str_replace('-service', '', $serviceName))
                        ]
                    ]
                ]
            ]
        ];
        
        file_put_contents(
            $configDir . '/doctrine.yaml',
            yaml_emit($doctrineConfig)
        );
        
        // Generate API Platform configuration if needed
        $this->generateApiPlatformConfig($serviceName, $configDir, $config);
    }
    
    private function generateApiPlatformConfig(string $serviceName, string $configDir, array $config): void
    {
        $apiConfig = [
            'api_platform' => [
                'mapping' => [
                    'paths' => ['%kernel.project_dir%/src/Entity']
                ],
                'patch_formats' => ['json' => ['application/merge-patch+json']],
                'swagger' => [
                    'versions' => [3]
                ]
            ]
        ];
        
        file_put_contents(
            $configDir . '/api_platform.yaml',
            yaml_emit($apiConfig)
        );
    }
}
```

## 🎨 Custom Template Development

### Advanced Template Customization

#### Enterprise Entity Template

```twig
{# templates/enterprise_entity.php.twig #}
<?php

declare(strict_types=1);

namespace {{ namespace }};

use Doctrine\ORM\Mapping as ORM;
{% if use_carbon %}
use Carbon\Carbon;
{% endif %}
{% if use_uuid %}
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
{% endif %}
{% if use_validation %}
use Symfony\Component\Validator\Constraints as Assert;
{% endif %}
{% if use_serialization %}
use Symfony\Component\Serializer\Annotation\Groups;
{% endif %}
{% if use_api_platform %}
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
{% endif %}
{% for use_statement in additional_use_statements %}
use {{ use_statement }};
{% endfor %}

/**
 * {{ class_name }} Entity
 * 
 * Generated by ReverseEngineeringBundle
 * 
 * @author {{ author|default('ReverseEngineeringBundle') }}
 * @version {{ version|default('1.0.0') }}
 * @since {{ since|default('1.0.0') }}
 * @generated {{ "now"|date('Y-m-d H:i:s') }}
 * 
 * Business Domain: {{ business_domain|default('General') }}
 * Data Classification: {{ data_classification|default('Internal') }}
 * 
 * Table: {{ table_name }}
 * {% if table_comment %}
 * Description: {{ table_comment }}
 * {% endif %}
 * 
 * Relationships:
 * {% for relation in relations %}
 * - {{ relation.type }}: {{ relation.target_entity }}
 * {% endfor %}
 */
{% if use_api_platform %}
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['{{ class_name|lower }}:read', '{{ class_name|lower }}:list']]
        ),
        new Get(
            normalizationContext: ['groups' => ['{{ class_name|lower }}:read', '{{ class_name|lower }}:detail']]
        ),
        new Post(
            denormalizationContext: ['groups' => ['{{ class_name|lower }}:write']]
        ),
        new Put(
            denormalizationContext: ['groups' => ['{{ class_name|lower }}:write']]
        ),
        new Delete()
    ],
    normalizationContext: ['groups' => ['{{ class_name|lower }}:read']],
    denormalizationContext: ['groups' => ['{{ class_name|lower }}:write']]
)]
{% endif %}
#[ORM\Entity(repositoryClass: {{ repository_class }}::class)]
#[ORM\Table(name: '{{ table_name }}')]
{% if has_lifecycle_callbacks %}
#[ORM\HasLifecycleCallbacks]
{% endif %}
{% if use_cache %}
#[ORM\Cache(usage: 'READ_WRITE', region: '{{ cache_region|default('default') }}')]
{% endif %}
class {{ class_name }}{% if extends_class %} extends {{ extends_class }}{% endif %}{% if implements_interfaces %} implements {{ implements_interfaces|join(', ') }}{% endif %}

{
{% if use_traits %}
{% for trait in traits %}
    use {{ trait }};
{% endfor %}

{% endif %}
{% for column in columns %}
    {% if column.comment %}
    /**
     * {{ column.comment }}
     * 
     * Database: {{ column.name }} ({{ column.db_type }})
     * {% if column.validation_rules %}
     * Validation: {{ column.validation_rules|join(', ') }}
     * {% endif %}
     * {% if column.business_rules %}
     * Business Rules: {{ column.business_rules|join(', ') }}
     * {% endif %}
     */
    {% endif %}
    {% if use_serialization %}
    {% if column.serialization_groups %}
    #[Groups([{{ column.serialization_groups|map(g => "'" ~ g ~ "'")|join(', ') }}])]
    {% endif %}
    {% endif %}
    {% if use_validation and column.validation_constraints %}
    {% for constraint in column.validation_constraints %}
    #[{{ constraint }}]
    {% endfor %}
    {% endif %}
    {% for attribute in column.attributes %}
    {{ attribute }}
    {% endfor %}
    private {{ column.php_type }}{{ column.nullable ? '?' : '' }} ${{ column.property_name }}{% if column.default_value is not null %} = {{ column.default_value }}{% endif %};

{% endfor %}
{% for relation in relations %}
    /**
     * {{ relation.comment|default('Relationship: ' ~ relation.type ~ ' to ' ~ relation.target_entity) }}
     * 
     * Type: {{ relation.type }}
     * Target: {{ relation.target_entity }}
     * {% if relation.business_description %}
     * Business: {{ relation.business_description }}
     * {% endif %}
     */
    {% if use_serialization and relation.serialization_groups %}
    #[Groups([{{ relation.serialization_groups|map(g => "'" ~ g ~ "'")|join(', ') }}])]
    {% endif %}
    {% for attribute in relation.attributes %}
    {{ attribute }}
    {% endfor %}
    private {{ relation.php_type }} ${{ relation.property_name }};

{% endfor %}
    /**
     * Constructor
     */
    public function __construct()
    {
{% if use_uuid and has_uuid_field %}
        $this->{{ uuid_field }} = Uuid::uuid4();
{% endif %}
{% for relation in relations %}
{% if relation.type in ['OneToMany', 'ManyToMany'] %}
        $this->{{ relation.property_name }} = new ArrayCollection();
{% endif %}
{% endfor %}
{% if has_timestamps %}
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
{% endif %}
    }

{% for column in columns %}
    /**
     * Get {{ column.property_name }}
     * 
     * {% if column.getter_description %}
     * {{ column.getter_description }}
     * {% endif %}
     */
    public function get{{ column.property_name|title }}(): {{ column.php_type }}{{ column.nullable ? '?' : '' }}
    {
        return $this->{{ column.property_name }};
    }

    /**
     * Set {{ column.property_name }}
     * 
     * {% if column.setter_description %}
     * {{ column.setter_description }}
     * {% endif %}
     */
    public function set{{ column.property_name|title }}({{ column.php_type }}{{ column.nullable ? '?' : '' }} ${{ column.property_name }}): self
    {
{% if column.business_validation %}
        {{ column.business_validation }}
{% endif %}
        $this->{{ column.property_name }} = ${{ column.property_name }};
        
        return $this;
    }

{% endfor %}
{% for relation in relations %}
    /**
     * Get {{ relation.property_name }}
     */
    public function get{{ relation.property_name|title }}(): {{ relation.php_type }}
    {
        return $this->{{ relation.property_name }};
    }

    {% if relation.type == 'ManyToOne' %}
    /**
     * Set {{ relation.property_name }}
     */
    public function set{{ relation.property_name|title }}({{ relation.target_entity }}{{ relation.nullable ? '?' : '' }} ${{ relation.property_name }}): self
    {
        $this->{{ relation.property_name }} = ${{ relation.property_name }};
        
        return $this;
    }
    {% elseif relation.type in ['OneToMany', 'ManyToMany'] %}
    
    /**
     * Add {{ relation.singular_name }}
     */
    public function add{{ relation.singular_name|title }}({{ relation.target_entity }} ${{ relation.singular_name }}): self
    {
        if (!$this->{{ relation.property_name }}->contains(${{ relation.singular_name }})) {
            $this->{{ relation.property_name }}[] = ${{ relation.singular_name }};
            {% if relation.inverse_property %}
            ${{ relation.singular_name }}->set{{ relation.inverse_property|title }}($this);
            {% endif %}
        }
        
        return $this;
    }

    /**
     * Remove {{ relation.singular_name }}
     */
    public function remove{{ relation.singular_name|title }}({{ relation.target_entity }} ${{ relation.singular_name }}): self
    {
        if ($this->{{ relation.property_name }}->removeElement(${{ relation.singular_name }})) {
            {% if relation.inverse_property %}
            if (${{ relation.singular_name }}->get{{ relation.inverse_property|title }}() === $this) {
                ${{ relation.singular_name }}->set{{ relation.inverse_property|title }}(null);
            }
            {% endif %}
        }
        
        return $this;
    }
    {% endif %}

{% endfor %}
{% if business_methods %}
    // Business Logic Methods
    
{% for method in business_methods %}
    /**
     * {{ method.description }}
     * 
     * {% if method.business_rules %}
     * Business Rules:
     * {% for rule in method.business_rules %}
     * - {{ rule }}
     * {% endfor %}
     * {% endif %}
     */
    public function {{ method.name }}({% if method.parameters %}{{ method.parameters|join(', ') }}{% endif %}): {{ method.return_type }}
    {
        {{ method.implementation }}
    }

{% endfor %}
{% endif %}
{% if has_lifecycle_callbacks %}
    /**
     * Pre-persist lifecycle callback
     */
    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        {% if has_timestamps %}
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        {% endif %}
        {% if custom_pre_persist %}
        {{ custom_pre_persist }}
        {% endif %}
    }
    
    /**
     * Pre-update lifecycle callback
     */
    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        {% if has_timestamps %}
        $this->updatedAt = new \DateTime();
        {% endif %}
        {% if custom_pre_update %}
        {{ custom_pre_update }}
        {% endif %}
    }
{% endif %}

{% if to_string_method %}
    /**
     * String representation of the entity
     */
    public function __toString(): string
    {
        return {{ to_string_method }};
    }
{% endif %}

{% if serialization_methods %}
    /**
     * Serialize entity for JSON response
     */
    public function jsonSerialize(): array
    {
        return [
            {% for field in serializable_fields %}
            '{{ field.name }}' => $this->{{ field.property }},
            {% endfor %}
        ];
    }
{% endif %}
}
```

#### Custom Template Variables Service

```php
// src/Service/CustomTemplateVariablesService.php
namespace App\Service;

class CustomTemplateVariablesService
{
    public function generateEnterpriseVariables(string $tableName, array $metadata): array
    {
        return [
            // Basic information
            'author' => 'Enterprise Development Team',
            'version' => '2.0.0',
            'since' => '2.0.0',
            'business_domain' => $this->determinBusinessDomain($tableName),
            'data_classification' => $this->classifyData($tableName, $metadata),
            
            // Feature flags
            'use_carbon' => true,
            'use_uuid' => $this->shouldUseUuid($metadata),
            'use_validation' => true,
            'use_serialization' => true,
            'use_api_platform' => $this->shouldUseApiPlatform($tableName),
            'use_cache' => $this->shouldUseCache($tableName),
            'use_traits' => true,
            
            // Advanced features
            'extends_class' => $this->getBaseClass($tableName),
            'implements_interfaces' => $this->getInterfaces($tableName),
            'traits' => $this->getTraits($tableName),
            'has_lifecycle_callbacks' => true,
            'has_timestamps' => $this->hasTimestamps($metadata),
            'has_uuid_field' => $this->hasUuidField($metadata),
            'uuid_field' => $this->getUuidField($metadata),
            
            // Business logic
            'business_methods' => $this->generateBusinessMethods($tableName),
            'to_string_method' => $this->generateToStringMethod($metadata),
            'serialization_methods' => true,
            'serializable_fields' => $this->getSerializableFields($metadata),
            
            // Enhanced column information
            'columns' => $this->enhanceColumnMetadata($metadata['columns']),
            'relations' => $this->enhanceRelationMetadata($metadata['relations'] ?? []),
            
            // Configuration
            'cache_region' => $this->getCacheRegion($tableName),
            'additional_use_statements' => $this->getAdditionalUseStatements($tableName)
        ];
    }
    
    private function determinBusinessDomain(string $tableName): string
    {
        $domainMapping = [
            'user' => 'User Management',
            'customer' => 'Customer Relations',
            'product' => 'Product Catalog',
            'order' => 'Order Management',
            'payment' => 'Financial',
            'invoice' => 'Financial',
            'inventory' => 'Inventory Management',
            'employee' => 'Human Resources',
            'department' => 'Human Resources',
            'report' => 'Analytics',
            'audit' => 'Compliance'
        ];
        
        foreach ($domainMapping as $keyword => $domain) {
            if (strpos(strtolower($tableName), $keyword) !== false) {
                return $domain;
            }
        }
        
        return 'General';
    }
    
    private function classifyData(string $tableName, array $metadata): string
    {
        $sensitiveKeywords = ['password', 'secret', 'token', 'key', 'ssn', 'credit'];
        $confidentialKeywords = ['email', 'phone', 'address', 'salary'];
        
        $tableLower = strtolower($tableName);
        
        // Check table name
        foreach ($sensitiveKeywords as $keyword) {
            if (strpos($tableLower, $keyword) !== false) {
                return 'Restricted';
            }
        }
        
        foreach ($confidentialKeywords as $keyword) {
            if (strpos($tableLower, $keyword) !== false) {
                return 'Confidential';
            }
        }
        
        // Check column names
        foreach ($metadata['columns'] ?? [] as $column) {
            $columnLower = strtolower($column['name']);
            
            foreach ($sensitiveKeywords as $keyword) {
                if (strpos($columnLower, $keyword) !== false) {
                    return 'Restricted';
                }
            }
            
            foreach ($confidentialKeywords as $keyword) {
                if (strpos($columnLower, $keyword) !== false) {
                    return 'Confidential';
                }
            }
        }
        
        return 'Internal';
    }
    
    private function generateBusinessMethods(string $tableName): array
    {
        $methods = [];
        
        // Generate common business methods based on table type
        switch (true) {
            case strpos($tableName, 'user') !== false:
                $methods[] = [
                    'name' => 'getFullName',
                    'description' => 'Get the full name of the user',
                    'parameters' => [],
                    'return_type' => 'string',
                    'implementation' => 'return trim($this->firstName . \' \' . $this->lastName);',
                    'business_rules' => ['Concatenates first and last name with proper spacing']
                ];
                
                $methods[] = [
                    'name' => 'isActive',
                    'description' => 'Check if the user is active',
                    'parameters' => [],
                    'return_type' => 'bool',
                    'implementation' => 'return $this->status === \'active\';',
                    'business_rules' => ['User is active when status equals "active"']
                ];
                break;
                
            case strpos($tableName, 'order') !== false:
                $methods[] = [
                    'name' => 'getTotalAmount',
                    'description' => 'Calculate the total amount of the order',
                    'parameters' => [],
                    'return_type' => 'float',
                    'implementation' => 'return array_sum(array_map(fn($item) => $item->getTotal(), $this->orderItems->toArray()));',
                    'business_rules' => ['Sum of all order item totals']
                ];
                
                $methods[] = [
                    'name' => 'isCompleted',
                    'description' => 'Check if the order is completed',
                    'parameters' => [],
                    'return_type' => 'bool',
                    'implementation' => 'return in_array($this->status, [\'completed\', \'delivered\']);',
                    'business_rules' => ['Order is completed when status is "completed" or "delivered"']
                ];
                break;
                
            case strpos($tableName, 'product') !== false:
                $methods[] = [
                    'name' => 'isInStock',
                    'description' => 'Check if the product is in stock',
                    'parameters' => [],
                    'return_type' => 'bool',
                    'implementation' => 'return $this->stockQuantity > 0;',
                    'business_rules' => ['Product is in stock when quantity is greater than 0']
                ];
                
                $methods[] = [
                    'name' => 'getFormattedPrice',
                    'description' => 'Get the formatted price with currency',
                    'parameters' => ['string $currency = \'USD\''],
                    'return_type' => 'string',
                    'implementation' => 'return $currency . \' \' . number_format($this->price, 2);',
                    'business_rules' => ['Format price with currency symbol and 2 decimal places']
                ];
                break;
        }
        
        return $methods;
    }
    
    private function enhanceColumnMetadata(array $columns): array
    {
        return array_map(function ($column) {
            // Add validation constraints
            $column['validation_constraints'] = $this->generateValidationConstraints($column);
            
            // Add serialization groups
            $column['serialization_groups'] = $this->generateSerializationGroups($column);
            
            // Add business validation
            $column['business_validation'] = $this->generateBusinessValidation($column);
            
            // Add descriptions
            $column['getter_description'] = $this->generateGetterDescription($column);
            $column['setter_description'] = $this->generateSetterDescription($column);
            
            return $column;
        }, $columns);
    }
    
    private function generateValidationConstraints(array $column): array
    {
        $constraints = [];
        
        if (!$column['nullable']) {
            $constraints[] = 'Assert\NotBlank';
        }
        
        if ($column['php_type'] === 'string' && isset($column['length'])) {
            $constraints[] = "Assert\Length(max: {$column['length']})";
        }
        
        if (strpos($column['name'], 'email') !== false) {
            $constraints[] = 'Assert\Email';
        }
        
        if (strpos($column['name'], 'url') !== false) {
            $constraints[] = 'Assert\Url';
        }
        
        if ($column['php_type'] === 'int' && strpos($column['name'], 'age') !== false) {
            $constraints[] = 'Assert\Range(min: 0, max: 150)';
        }
        
        return $constraints;
    }
    
    private function generateSerializationGroups(array $column): array
    {
        $groups = ['read'];
        
        if (!in_array($column['name'], ['password', 'secret', 'token'])) {
            $groups[] = 'list';
        }
        
        if (!$column['primary']) {
            $groups[] = 'write';
        }
        
        if (in_array($column['name'], ['id', 'created_at', 'updated_at'])) {
            $groups[] = 'detail';
        }
        
        return $groups;
    }
    
    private function generateBusinessValidation(array $column): string
    {
        $validations = [];
        
        if (strpos($column['name'], 'email') !== false) {
            $validations[] = 'if (!filter_var($' . $column['property_name'] . ', FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(\'Invalid email format\');
        }';
        }
        
        if ($column['php_type'] === 'string' && strpos($column['name'], 'password') !== false) {
            $validations[] = 'if (strlen($' . $column['property_name'] . ') < 8) {
            throw new \InvalidArgumentException(\'Password must be at least 8 characters long\');
        }';
        }
        
        return implode("\n        ", $validations);
    }
}
```

## ⚙️ Advanced Configuration

### Environment-Specific Configurations

#### Multi-Environment Setup

```yaml
# config/packages/dev/reverse_engineering.yaml
reverse_engineering:
    database:
        driver: pdo_mysql
        host: localhost
        port: 3306
        dbname: myapp_dev
        user: dev_user
        password: dev_password
        options:
            1002: "SET SESSION sql_mode=''"
    
    generation:
        namespace: App\Entity\Dev
        output_dir: src/Entity/Dev
        generate_repository: true
        use_annotations: false
        
    templates:
        entity: 'dev_entity.php.twig'
        repository: 'dev_repository.php.twig'
        
    performance:
        batch_size: 10
        memory_limit: '256M'
        
    security:
        validate_ssl: false
        audit_logging: true
```

```yaml
# config/packages/prod/reverse_engineering.yaml
reverse_engineering:
    database:
        driver: pdo_