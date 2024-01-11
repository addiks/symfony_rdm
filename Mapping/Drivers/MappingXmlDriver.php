<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Mapping\Drivers;

use DOMDocument;
use DOMXPath;
use DOMNode;
use DOMAttr;
use DOMNamedNodeMap;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Doctrine\Persistence\Mapping\Driver\FileLocator;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Addiks\RDMBundle\Mapping\EntityMappingInterface;
use Addiks\RDMBundle\Mapping\EntityMapping;
use Addiks\RDMBundle\Mapping\ServiceMapping;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Addiks\RDMBundle\Mapping\ChoiceMapping;
use Addiks\RDMBundle\Mapping\ObjectMapping;
use Addiks\RDMBundle\Mapping\CallDefinitionInterface;
use Addiks\RDMBundle\Mapping\CallDefinition;
use Addiks\RDMBundle\Mapping\FieldMapping;
use Addiks\RDMBundle\Mapping\ArrayMapping;
use Addiks\RDMBundle\Mapping\ListMapping;
use Addiks\RDMBundle\Mapping\NullMapping;
use Addiks\RDMBundle\Mapping\NullableMapping;
use Addiks\RDMBundle\Mapping\MappingProxy;
use Addiks\RDMBundle\Exception\InvalidMappingException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use ErrorException;
use DOMElement;
use Webmozart\Assert\Assert;
use Addiks\RDMBundle\Mapping\FixNativeMapping;

final class MappingXmlDriver implements MappingDriverInterface
{

    const RDM_SCHEMA_URI = "http://github.com/addiks/symfony_rdm/tree/master/Resources/mapping-schema.v1.xsd";
    const DOCTRINE_SCHEMA_URI = "http://doctrine-project.org/schemas/orm/doctrine-mapping";

    /**
     * @var FileLocator
     */
    private $doctrineFileLocator;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var ContainerInterface
     */
    private $serviceContainer;

    /**
     * @var string
     */
    private $schemaFilePath;

    public function __construct(
        FileLocator $doctrineFileLocator,
        KernelInterface $kernel,
        string $schemaFilePath
    ) {
        /** @var ContainerInterface|null $serviceContainer */
        $serviceContainer = $kernel->getContainer();

        if (is_null($serviceContainer)) {
            throw new ErrorException("Kernel does not have a container!");
        }

        $this->doctrineFileLocator = $doctrineFileLocator;
        $this->kernel = $kernel;
        $this->schemaFilePath = $schemaFilePath;
        $this->serviceContainer = $serviceContainer;
    }

    public function loadRDMMetadataForClass(string $className): ?EntityMappingInterface
    {
        /** @var ?EntityMappingInterface $mapping */
        $mapping = null;

        /** @var array<MappingInterface> $fieldMappings */
        $fieldMappings = array();

        if ($this->doctrineFileLocator->fileExists($className)) {
            /** @var string $mappingFile */
            $mappingFile = $this->doctrineFileLocator->findMappingFile($className);

            $fieldMappings = $this->readFieldMappingsFromFile($mappingFile);
        }

        if (!empty($fieldMappings)) {
            $mapping = new EntityMapping($className, $fieldMappings);
        }

        return $mapping;
    }

    /**
     * @return array<MappingInterface>
     */
    private function readFieldMappingsFromFile(string $mappingFile, string $parentMappingFile = null): array
    {
        if ($mappingFile[0] === '@') {
            /** @var string $mappingFile */
            $mappingFile = $this->kernel->locateResource($mappingFile);
        }

        if ($mappingFile[0] !== DIRECTORY_SEPARATOR && !empty($parentMappingFile)) {
            $mappingFile = dirname($parentMappingFile) . DIRECTORY_SEPARATOR . $mappingFile;
        }

        if (!file_exists($mappingFile)) {
            throw new InvalidMappingException(sprintf(
                "Missing referenced orm file '%s'%s!",
                $mappingFile,
                is_string($parentMappingFile) ?sprintf(", referenced in file '%s'", $parentMappingFile) :''
            ));
        }
        
        /** @var string|null $mappingXml */
        $mappingXml = file_get_contents($mappingFile);
        
        Assert::notEmpty($mappingXml, sprintf('ORM-Mapping file "%s" is empty!', $mappingFile));

        $dom = new DOMDocument();
        $dom->loadXML($mappingXml);

        /** @var DOMXPath $xpath */
        $xpath = $this->createXPath($dom->documentElement);

        /** @var array<MappingInterface> $fieldMappings */
        $fieldMappings = $this->readFieldMappings(
            $dom,
            $mappingFile,
            null,
            false
        );

        foreach ($xpath->query("//orm:entity", $dom) as $entityNode) {
            /** @var DOMNode $entityNode */

            $fieldMappings = array_merge($fieldMappings, $this->readFieldMappings(
                $entityNode,
                $mappingFile,
                null,
                false
            ));
        }

        return $fieldMappings;
    }

    private function createXPath(DOMNode $node): DOMXPath
    {
        /** @var DOMNode $ownerDocument */
        $ownerDocument = $node;

        if (!$ownerDocument instanceof DOMDocument) {
            $ownerDocument = $node->ownerDocument;
            Assert::object($ownerDocument);
        }

        $xpath = new DOMXPath($ownerDocument);
        $xpath->registerNamespace('rdm', self::RDM_SCHEMA_URI);
        $xpath->registerNamespace('orm', self::DOCTRINE_SCHEMA_URI);

        return $xpath;
    }

    private function readObject(DOMNode $objectNode, string $mappingFile): ObjectMapping
    {
        /** @var DOMNamedNodeMap|null $attributes */
        $objectNodeAttributes = $objectNode->attributes;

        if (!$this->hasAttributeValue($objectNode, "class")) {
            throw new InvalidMappingException(sprintf(
                "Missing 'class' attribute on 'object' mapping in %s in line %d",
                $mappingFile,
                $objectNode->getLineNo()
            ));
        }

        /** @var class-string $className */
        $className = (string)$this->readAttributeValue($objectNode, "class");

        Assert::true(class_exists($className) || interface_exists($className), sprintf(
            'Class or interface "%s" does not exist!',
            $className
        ));

        /** @var CallDefinitionInterface|null $factory */
        $factory = null;

        /** @var CallDefinitionInterface|null $factory */
        $serializer = null;

        /** @var DOMXPath $xpath */
        $xpath = $this->createXPath($objectNode);

        foreach ($xpath->query('./rdm:factory', $objectNode) as $factoryNode) {
            /** @var DOMNode $factoryNode */

            /** @var array<MappingInterface> $argumentMappings */
            $argumentMappings = $this->readFieldMappings($factoryNode, $mappingFile);

            /** @var string $routineName */
            $routineName = (string)$this->readAttributeValue($factoryNode, "method");

            /** @var string $objectReference */
            $objectReference = (string)$this->readAttributeValue($factoryNode, "object");

            $factory = new CallDefinition(
                $this->serviceContainer,
                $routineName,
                $objectReference,
                $argumentMappings,
                false,
                $mappingFile . " in line " . $objectNode->getLineNo()
            );
        }

        if ($this->hasAttributeValue($objectNode, "factory") && is_null($factory)) {
            $factory = $this->readCallDefinition(
                (string)$this->readAttributeValue($objectNode, "factory"),
                $mappingFile . " in line " . $objectNode->getLineNo()
            );
        }

        if ($this->hasAttributeValue($objectNode, "serialize")) {
            $serializer = $this->readCallDefinition(
                (string)$this->readAttributeValue($objectNode, "serialize"),
                $mappingFile . " in line " . $objectNode->getLineNo()
            );
        }

        /** @var array<MappingInterface> $fieldMappings */
        $fieldMappings = $this->readFieldMappings($objectNode, $mappingFile);

        /** @var Column|null $dbalColumn */
        $dbalColumn = null;

        if ($this->hasAttributeValue($objectNode, "column")) {
            /** @var bool $notnull */
            $notnull = true;

            /** @var string $type */
            $type = "string";

            /** @var int $length */
            $length = 255;

            /** @var string|null $default */
            $default = null;

            if ($this->hasAttributeValue($objectNode, "nullable")) {
                $notnull = (strtolower((string)$this->readAttributeValue($objectNode, "nullable")) !== 'true');
            }

            if ($this->hasAttributeValue($objectNode, "column-type")) {
                $type = (string)$this->readAttributeValue($objectNode, "column-type");
            }

            if ($this->hasAttributeValue($objectNode, "column-length")) {
                $length = (string)$this->readAttributeValue($objectNode, "column-length");
            }

            if ($this->hasAttributeValue($objectNode, "column-default")) {
                $default = (string)$this->readAttributeValue($objectNode, "column-default");
            }

            $dbalColumn = new Column(
                (string)$this->readAttributeValue($objectNode, "column"),
                Type::getType($type),
                [
                    'notnull' => $notnull,
                    'length' => $length,
                    'default' => $default,
                ]
            );
        }

        /** @var string|null $id */
        $id = null;

        /** @var string|null $referencedId */
        $referencedId = null;

        if ($this->hasAttributeValue($objectNode, "id")) {
            $id = (string)$this->readAttributeValue($objectNode, "id");
        }

        if ($this->hasAttributeValue($objectNode, "references-id")) {
            $referencedId = (string)$this->readAttributeValue($objectNode, "references-id");
        }

        return new ObjectMapping(
            $className,
            $fieldMappings,
            $dbalColumn,
            sprintf(
                "in file '%s' in line %d",
                $mappingFile,
                $objectNode->getLineNo()
            ),
            $factory,
            $serializer,
            $id,
            $referencedId
        );
    }

    private function readCallDefinition(
        string $callDefinition,
        string $origin = "unknown"
    ): CallDefinitionInterface {
        /** @var string $routineName */
        $routineName = $callDefinition;

        /** @var string|null $objectReference */
        $objectReference = null;

        /** @var bool $isStaticCall */
        $isStaticCall = false;

        if (strpos($callDefinition, '::') !== false) {
            [$objectReference, $routineName] = explode('::', $callDefinition);
            $isStaticCall = true;
        }

        if (strpos($callDefinition, '->') !== false) {
            [$objectReference, $routineName] = explode('->', $callDefinition);
        }

        return new CallDefinition(
            $this->serviceContainer,
            $routineName,
            $objectReference,
            [],
            $isStaticCall,
            $origin
        );
    }

    private function readChoice(
        DOMNode $choiceNode,
        string $mappingFile,
        string $defaultColumnName
    ): ChoiceMapping {
        /** @var string|Column $columnName */
        $column = $defaultColumnName;

        if ($this->hasAttributeValue($choiceNode, "column")) {
            $column = (string)$this->readAttributeValue($choiceNode, "column");
        }

        /** @var array<MappingInterface> $choiceMappings */
        $choiceMappings = array();

        /** @var DOMXPath $xpath */
        $xpath = $this->createXPath($choiceNode);

        foreach ($xpath->query('./rdm:option', $choiceNode) as $optionNode) {
            /** @var DOMNode $optionNode */

            /** @var string $determinator */
            $determinator = (string)$this->readAttributeValue($optionNode, "name");

            /** @var string $optionDefaultColumnName */
            $optionDefaultColumnName = sprintf("%s_%s", $defaultColumnName, $determinator);

            foreach ($this->readFieldMappings($optionNode, $mappingFile, $optionDefaultColumnName) as $mapping) {
                /** @var MappingInterface $mapping */

                $choiceMappings[$determinator] = $mapping;
            }
        }

        foreach ($xpath->query('./orm:field', $choiceNode) as $fieldNode) {
            /** @var DOMNode $fieldNode */

            $column = $this->readDoctrineField($fieldNode);
        }

        return new ChoiceMapping($column, $choiceMappings, sprintf(
            "in file '%s' in line %d",
            $mappingFile,
            $choiceNode->getLineNo()
        ));
    }

    /**
     * @return array<MappingInterface>
     */
    private function readFieldMappings(
        DOMNode $parentNode,
        string $mappingFile,
        string $choiceDefaultColumnName = null,
        bool $readFields = true
    ): array {
        /** @var DOMXPath $xpath */
        $xpath = $this->createXPath($parentNode);

        /** @var array<MappingInterface> $fieldMappings */
        $fieldMappings = array();

        /** @var DOMNode $serviceNode */
        foreach ($xpath->query('./rdm:service', $parentNode) as $serviceNode) {

            $serviceMapping = $this->readService($serviceNode, $mappingFile);

            if ($this->hasAttributeValue($serviceNode, "field")) {
                /** @var string $fieldName */
                $fieldName = (string)$this->readAttributeValue($serviceNode, "field");

                $fieldMappings[$fieldName] = $serviceMapping;

            } else {
                $fieldMappings[] = $serviceMapping;
            }
        }

        /** @var DOMNode $choiceNode */
        foreach ($xpath->query('./rdm:choice', $parentNode) as $choiceNode) {

            /** @var string $defaultColumnName */
            $defaultColumnName = "";

            if (!is_null($choiceDefaultColumnName)) {
                $defaultColumnName = $choiceDefaultColumnName;

            } elseif ($this->hasAttributeValue($choiceNode, "field")) {
                $defaultColumnName = (string)$this->readAttributeValue($choiceNode, "field");
            }

            $choiceMapping = $this->readChoice($choiceNode, $mappingFile, $defaultColumnName);

            if ($this->hasAttributeValue($choiceNode, "field")) {
                /** @var string $fieldName */
                $fieldName = (string)$this->readAttributeValue($choiceNode, "field");

                $fieldMappings[$fieldName] = $choiceMapping;

            } else {
                $fieldMappings[] = $choiceMapping;
            }
        }

        /** @var DOMNode $objectNode */
        foreach ($xpath->query('./rdm:object', $parentNode) as $objectNode) {

            /** @var ObjectMapping $objectMapping */
            $objectMapping = $this->readObject($objectNode, $mappingFile);

            if ($this->hasAttributeValue($objectNode, "field")) {
                /** @var string $fieldName */
                $fieldName = (string)$this->readAttributeValue($objectNode, "field");

                $fieldMappings[$fieldName] = $objectMapping;

            } else {
                $fieldMappings[] = $objectMapping;
            }
        }

        if ($readFields) {
            /** @var DOMNode $fieldNode */
            foreach ($xpath->query('./orm:field', $parentNode) as $fieldNode) {

                /** @var Column $column */
                $column = $this->readDoctrineField($fieldNode);

                $fieldName = (string)$this->readAttributeValue($fieldNode, "name");

                $fieldMappings[$fieldName] = new FieldMapping(
                    $column,
                    sprintf("in file '%s' in line %d", $mappingFile, $fieldNode->getLineNo())
                );
            }
        }

        /** @var DOMNode $arrayNode */
        foreach ($xpath->query('./rdm:array', $parentNode) as $arrayNode) {

            /** @var ArrayMapping $arrayMapping */
            $arrayMapping = $this->readArray($arrayNode, $mappingFile);

            if ($this->hasAttributeValue($arrayNode, "field")) {
                /** @var string $fieldName */
                $fieldName = (string)$this->readAttributeValue($arrayNode, "field");

                $fieldMappings[$fieldName] = $arrayMapping;

            } else {
                $fieldMappings[] = $arrayMapping;
            }
        }

        /** @var DOMNode $listNode */
        foreach ($xpath->query('./rdm:list', $parentNode) as $listNode) {

            /** @var string $defaultColumnName */
            $defaultColumnName = "";

            if (!is_null($choiceDefaultColumnName)) {
                $defaultColumnName = $choiceDefaultColumnName;

            } elseif ($this->hasAttributeValue($listNode, "field")) {
                $defaultColumnName = (string)$this->readAttributeValue($listNode, "field");
            }

            /** @var ListMapping $listMapping */
            $listMapping = $this->readList($listNode, $mappingFile, $defaultColumnName);

            if ($this->hasAttributeValue($listNode, "field")) {
                /** @var string $fieldName */
                $fieldName = (string)$this->readAttributeValue($listNode, "field");

                $fieldMappings[$fieldName] = $listMapping;

            } else {
                $fieldMappings[] = $listMapping;
            }
        }

        /** @var DOMNode $nullNode */
        foreach ($xpath->query('./rdm:null', $parentNode) as $nullNode) {

            if ($this->hasAttributeValue($nullNode, "field")) {
                /** @var string $fieldName */
                $fieldName = (string)$this->readAttributeValue($nullNode, "field");

                $fieldMappings[$fieldName] = new NullMapping(sprintf(
                    "in file '%s' in line %d",
                    $mappingFile,
                    $nullNode->getLineNo()
                ));

            } else {
                $fieldMappings[] = new NullMapping(sprintf(
                    "in file '%s' in line %d",
                    $mappingFile,
                    $nullNode->getLineNo()
                ));
            }
        }

        /** @var DOMNode $nullableNode */
        foreach ($xpath->query('./rdm:nullable', $parentNode) as $nullableNode) {

            /** @var NullableMapping $nullableMapping */
            $nullableMapping = $this->readNullable($nullableNode, $mappingFile);

            if ($this->hasAttributeValue($nullableNode, "field")) {
                /** @var string $fieldName */
                $fieldName = (string)$this->readAttributeValue($nullableNode, "field");

                $fieldMappings[$fieldName] = $nullableMapping;

            } else {
                $fieldMappings[] = $nullableMapping;
            }
        }

        /** @var DOMNode $importNode */
        foreach ($xpath->query('./rdm:import', $parentNode) as $importNode) {

            /** @var string $path */
            $path = (string)$this->readAttributeValue($importNode, "path");

            /** @var string|null $forcedFieldName */
            $forcedFieldName = $this->readAttributeValue($importNode, "field");

            /** @var string $columnPrefix */
            $columnPrefix = (string)$this->readAttributeValue($importNode, "column-prefix");

            foreach ($this->readFieldMappingsFromFile($path, $mappingFile) as $fieldName => $fieldMapping) {
                /** @var MappingInterface $fieldMapping */

                $fieldMappingProxy = new MappingProxy(
                    $fieldMapping,
                    $columnPrefix
                );

                if (!empty($forcedFieldName)) {
                    $fieldMappings[$forcedFieldName] = $fieldMapping;
                } else {

                    $fieldMappings[$fieldName] = $fieldMapping;
                }
            }
        }
        
        foreach ($xpath->query('./rdm:fix', $parentNode) as $fixNode) {
            
            /** @var FixNativeMapping $fixMapping */
            $fixMapping = $this->readFixMapping($fixNode, $mappingFile);
            
            if ($this->hasAttributeValue($fixNode, "field")) {
                /** @var string $fieldName */
                $fieldName = (string)$this->readAttributeValue($fixNode, "field");

                $fieldMappings[$fieldName] = $fixMapping;

            } else {
                $fieldMappings[] = $fixMapping;
            }
        }

        return $fieldMappings;
    }
    
    private function readFixMapping(DOMNode $fixNode, string $mappingFile): FixNativeMapping
    {
        /** @var string $jsonSerializedValue */
        $jsonSerializedValue = "";
        
        if ($this->hasAttributeValue($fixNode, "json")) {
            $jsonSerializedValue = (string) $this->readAttributeValue($fixNode, "json");
        }
        
        if ($this->hasAttributeValue($fixNode, "string")) {
            $jsonSerializedValue = sprintf(
                '"%s"',
                addslashes((string) $this->readAttributeValue($fixNode, "string"))
            );
        }
        
        return new FixNativeMapping(
            $jsonSerializedValue,
            sprintf(
                "in file '%s' in line %d",
                $mappingFile,
                $fixNode->getLineNo()
            )
        );
    }

    private function readService(DOMNode $serviceNode, string $mappingFile): ServiceMapping
    {
        /** @var bool $lax */
        $lax = strtolower((string)$this->readAttributeValue($serviceNode, "lax")) === 'true';

        /** @var string $serviceId */
        $serviceId = (string)$this->readAttributeValue($serviceNode, "id");

        return new ServiceMapping(
            $this->serviceContainer,
            $serviceId,
            $lax,
            sprintf(
                "in file '%s' in line %d",
                $mappingFile,
                $serviceNode->getLineNo()
            )
        );
    }

    private function readArray(DOMNode $arrayNode, string $mappingFile): ArrayMapping
    {
        /** @var array<MappingInterface> $entryMappings */
        $entryMappings = $this->readFieldMappings($arrayNode, $mappingFile);

        /** @var DOMXPath $xpath */
        $xpath = $this->createXPath($arrayNode);

        foreach ($xpath->query('./rdm:entry', $arrayNode) as $entryNode) {
            /** @var DOMNode $entryNode */

            /** @var string|null $key */
            $key = $this->readAttributeValue($entryNode, "key");

            foreach ($this->readFieldMappings($entryNode, $mappingFile) as $entryMapping) {
                /** @var MappingInterface $entryMapping */

                if (is_null($key)) {
                    $entryMappings[] = $entryMapping;

                } else {
                    $entryMappings[$key] = $entryMapping;
                }

                break;
            }
        }

        return new ArrayMapping($entryMappings, sprintf(
            "in file '%s' in line %d",
            $mappingFile,
            $arrayNode->getLineNo()
        ));
    }

    private function readList(
        DOMNode $listNode,
        string $mappingFile,
        string $columnName
    ): ListMapping {
        if ($this->hasAttributeValue($listNode, "column")) {
            $columnName = (string)$this->readAttributeValue($listNode, "column");
        }

        /** @var array<MappingInterface> $entryMappings */
        $entryMappings = $this->readFieldMappings($listNode, $mappingFile);

        /** @var array<string, mixed> $columnOptions */
        $columnOptions = array();

        if ($this->hasAttributeValue($listNode, "column-length")) {
            $columnOptions['length'] = (int)$this->readAttributeValue($listNode, "column-length", "0");
        }

        /** @var Type $type */
        $type = Type::getType("string");

        if ($columnOptions['length'] ?? 0 >= 255) {
            $type = Type::getType("text");
        }

        $column = new Column($columnName, $type, $columnOptions);

        return new ListMapping($column, array_values($entryMappings)[0], sprintf(
            "in file '%s' in line %d",
            $mappingFile,
            $listNode->getLineNo()
        ));
    }

    private function readNullable(
        DOMNode $nullableNode,
        string $mappingFile
    ): NullableMapping {
        /** @var array<MappingInterface> $innerMappings */
        $innerMappings = $this->readFieldMappings($nullableNode, $mappingFile);

        if (count($innerMappings) !== 1) {
            throw new InvalidMappingException(sprintf(
                "A nullable mapping must contain exactly one inner mapping in '%s' at line %d!",
                $mappingFile,
                $nullableNode->getLineNo()
            ));
        }

        /** @var MappingInterface $innerMapping */
        $innerMapping = array_values($innerMappings)[0];

        /** @var Column|null $column */
        $column = null;

        if ($this->hasAttributeValue($nullableNode, "column")) {
            /** @var string $columnName */
            $columnName = $this->readAttributeValue($nullableNode, "column", "");

            $column = new Column(
                $columnName,
                Type::getType("boolean"),
                [
                    'notnull' => false
                ]
            );
        }

        $strict = $this->readAttributeValue($nullableNode, "strict", "false") === "true" ? true : false;

        return new NullableMapping($innerMapping, $column, sprintf(
            "in file '%s' at line %d",
            $mappingFile,
            $nullableNode->getLineNo()
        ), $strict);
    }

    private function readDoctrineField(DOMNode $fieldNode): Column
    {
        /** @var array<string> $attributes */
        $attributes = array();

        /** @var array<string> $keyMap */
        $keyMap = array(
            'column'            => 'name',
            'type'              => 'type',
            'nullable'          => 'notnull',
            'length'            => 'length',
            'precision'         => 'precision',
            'scale'             => 'scale',
            'column-definition' => 'columnDefinition',
        );

        /** @var string|null $columnName */
        $columnName = null;

        /** @var Type $type */
        $type = Type::getType('string');

        /** @var DOMNamedNodeMap|null $fieldNodeAttributes */
        $fieldNodeAttributes = $fieldNode->attributes;

        if (is_object($fieldNodeAttributes)) {
            foreach ($fieldNodeAttributes as $key => $attribute) {
                /** @var DOMAttr $attribute */

                $attributeValue = $attribute->nodeValue;

                if ($key === 'column') {
                    $columnName = $attributeValue;

                } elseif ($key === 'name') {
                    if (empty($columnName)) {
                        $columnName = $attributeValue;
                    }

                } elseif ($key === 'type') {
                    $type = Type::getType((string) $attributeValue);

                } elseif (isset($keyMap[$key])) {
                    if ($key === 'nullable') {
                        # target is 'notnull', so falue is reversed
                        $attributeValue = ($attributeValue === 'false');
                    }

                    $attributes[$keyMap[$key]] = $attributeValue;
                }
            }
        }
        
        Assert::notEmpty($columnName, 'Column name cannot be empty!');

        $column = new Column(
            $columnName,
            $type,
            $attributes
        );

        return $column;
    }

    private function hasAttributeValue(DOMNode $node, string $attributeName): bool
    {
        /** @var DOMNamedNodeMap $nodeAttributes */
        $nodeAttributes = $node->attributes;

        /** @var DOMNode|null $attributeNode */
        $attributeNode = $nodeAttributes->getNamedItem($attributeName);

        return is_object($attributeNode);
    }

    private function readAttributeValue(DOMNode $node, string $attributeName, ?string $default = null): ?string
    {
        /** @var DOMNamedNodeMap $nodeAttributes */
        $nodeAttributes = $node->attributes;

        /** @var DOMNode|null $attributeNode */
        $attributeNode = $nodeAttributes->getNamedItem($attributeName);

        /** @var string|null $value */
        $value = $default;

        if (is_object($attributeNode)) {
            $value = $attributeNode->nodeValue;
        }

        return $value;
    }

}
