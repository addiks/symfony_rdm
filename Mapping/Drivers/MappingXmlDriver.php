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
use Doctrine\Common\Persistence\Mapping\Driver\FileLocator;
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
     * @var string
     */
    private $schemaFilePath;

    public function __construct(
        FileLocator $doctrineFileLocator,
        KernelInterface $kernel,
        string $schemaFilePath
    ) {
        $this->doctrineFileLocator = $doctrineFileLocator;
        $this->kernel = $kernel;
        $this->schemaFilePath = $schemaFilePath;
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
                "Missing referenced orm file '%s', referenced in file '%s'!",
                $mappingFile,
                $parentMappingFile
            ));
        }

        $dom = new DOMDocument();
        $dom->loadXML(file_get_contents($mappingFile));

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
        }

        $xpath = new DOMXPath($ownerDocument);
        $xpath->registerNamespace('rdm', self::RDM_SCHEMA_URI);
        $xpath->registerNamespace('orm', self::DOCTRINE_SCHEMA_URI);

        return $xpath;
    }

    private function readObject(DOMNode $objectNode, string $mappingFile): ObjectMapping
    {
        /** @var DOMNamedNodeMap $attributes */
        $objectNodeAttributes = $objectNode->attributes;

        if (is_null($objectNodeAttributes->getNamedItem("class"))) {
            throw new InvalidMappingException(sprintf(
                "Missing 'class' attribute on 'object' mapping in %s",
                $mappingFile
            ));
        }

        $className = (string)$objectNodeAttributes->getNamedItem("class")->nodeValue;

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
            $routineName = (string)$factoryNode->attributes->getNamedItem('method')->nodeValue;

            /** @var string $objectReference */
            $objectReference = (string)$factoryNode->attributes->getNamedItem('object')->nodeValue;

            $factory = new CallDefinition(
                $this->kernel->getContainer(),
                $routineName,
                $objectReference,
                $argumentMappings
            );
        }

        if ($objectNodeAttributes->getNamedItem("factory") !== null && is_null($factory)) {
            $factory = $this->readCallDefinition(
                (string)$objectNodeAttributes->getNamedItem("factory")->nodeValue
            );
        }

        if ($objectNodeAttributes->getNamedItem("serialize") !== null) {
            $serializer = $this->readCallDefinition(
                (string)$objectNodeAttributes->getNamedItem("serialize")->nodeValue
            );
        }

        /** @var array<MappingInterface> $fieldMappings */
        $fieldMappings = $this->readFieldMappings($objectNode, $mappingFile);

        /** @var Column|null $dbalColumn */
        $dbalColumn = null;

        if ($objectNodeAttributes->getNamedItem("column") !== null) {
            /** @var bool $notnull */
            $notnull = true;

            /** @var string $type */
            $type = "string";

            /** @var int $length */
            $length = 255;

            if ($objectNodeAttributes->getNamedItem("nullable")) {
                $notnull = (strtolower($objectNodeAttributes->getNamedItem("nullable")->nodeValue) !== 'true');
            }

            if ($objectNodeAttributes->getNamedItem("column-type")) {
                $type = (string)$objectNodeAttributes->getNamedItem("column-type")->nodeValue;
            }

            if ($objectNodeAttributes->getNamedItem("column-length")) {
                $length = (int)$objectNodeAttributes->getNamedItem("column-length")->nodeValue;
            }

            $dbalColumn = new Column(
                (string)$objectNodeAttributes->getNamedItem("column")->nodeValue,
                Type::getType($type),
                [
                    'notnull' => $notnull,
                    'length' => $length
                ]
            );
        }

        /** @var string|null $id */
        $id = null;

        /** @var string|null $referencedId */
        $referencedId = null;

        if ($objectNodeAttributes->getNamedItem("id") !== null) {
            $id = (string)$objectNodeAttributes->getNamedItem("id")->nodeValue;
        }

        if ($objectNodeAttributes->getNamedItem("references-id") !== null) {
            $referencedId = (string)$objectNodeAttributes->getNamedItem("references-id")->nodeValue;
        }

        return new ObjectMapping(
            $className,
            $fieldMappings,
            $dbalColumn,
            sprintf(
                "in file '%s'",
                $mappingFile
            ),
            $factory,
            $serializer,
            $id,
            $referencedId
        );
    }

    private function readCallDefinition(string $callDefinition): CallDefinitionInterface
    {
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
            $this->kernel->getContainer(),
            $routineName,
            $objectReference,
            [],
            $isStaticCall
        );
    }

    private function readChoice(
        DOMNode $choiceNode,
        string $mappingFile,
        string $defaultColumnName
    ): ChoiceMapping {
        /** @var string|Column $columnName */
        $column = $defaultColumnName;

        if (!is_null($choiceNode->attributes->getNamedItem("column"))) {
            $column = (string)$choiceNode->attributes->getNamedItem("column")->nodeValue;
        }

        /** @var array<MappingInterface> $choiceMappings */
        $choiceMappings = array();

        /** @var DOMXPath $xpath */
        $xpath = $this->createXPath($choiceNode);

        foreach ($xpath->query('./rdm:option', $choiceNode) as $optionNode) {
            /** @var DOMNode $optionNode */

            /** @var string $determinator */
            $determinator = (string)$optionNode->attributes->getNamedItem("name")->nodeValue;

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
            "in file '%s'",
            $mappingFile
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

        foreach ($xpath->query('./rdm:service', $parentNode) as $serviceNode) {
            /** @var DOMNode $serviceNode */

            $serviceMapping = $this->readService($serviceNode, $mappingFile);

            if (!is_null($serviceNode->attributes->getNamedItem("field"))) {
                /** @var string $fieldName */
                $fieldName = (string)$serviceNode->attributes->getNamedItem("field")->nodeValue;

                $fieldMappings[$fieldName] = $serviceMapping;

            } else {
                $fieldMappings[] = $serviceMapping;
            }
        }

        foreach ($xpath->query('./rdm:choice', $parentNode) as $choiceNode) {
            /** @var DOMNode $choiceNode */

            /** @var string $defaultColumnName */
            $defaultColumnName = "";

            if (!is_null($choiceDefaultColumnName)) {
                $defaultColumnName = $choiceDefaultColumnName;

            } elseif (!is_null($choiceNode->attributes->getNamedItem("field"))) {
                $defaultColumnName = (string)$choiceNode->attributes->getNamedItem("field")->nodeValue;
            }

            $choiceMapping = $this->readChoice($choiceNode, $mappingFile, $defaultColumnName);

            if (!is_null($choiceNode->attributes->getNamedItem("field"))) {
                /** @var string $fieldName */
                $fieldName = (string)$choiceNode->attributes->getNamedItem("field")->nodeValue;

                $fieldMappings[$fieldName] = $choiceMapping;

            } else {
                $fieldMappings[] = $choiceMapping;
            }
        }

        foreach ($xpath->query('./rdm:object', $parentNode) as $objectNode) {
            /** @var DOMNode $objectNode */

            /** @var ObjectMapping $objectMapping */
            $objectMapping = $this->readObject($objectNode, $mappingFile);

            if (!is_null($objectNode->attributes->getNamedItem("field"))) {
                /** @var string $fieldName */
                $fieldName = (string)$objectNode->attributes->getNamedItem("field")->nodeValue;

                $fieldMappings[$fieldName] = $objectMapping;

            } else {
                $fieldMappings[] = $objectMapping;
            }
        }

        if ($readFields) {
            foreach ($xpath->query('./orm:field', $parentNode) as $fieldNode) {
                /** @var DOMNode $fieldNode */

                /** @var Column $column */
                $column = $this->readDoctrineField($fieldNode);

                $fieldName = (string)$fieldNode->attributes->getNamedItem('name')->nodeValue;

                $fieldMappings[$fieldName] = new FieldMapping(
                    $column,
                    sprintf("in file '%s'", $mappingFile)
                );
            }
        }

        foreach ($xpath->query('./rdm:array', $parentNode) as $arrayNode) {
            /** @var DOMNode $arrayNode */

            /** @var ArrayMapping $arrayMapping */
            $arrayMapping = $this->readArray($arrayNode, $mappingFile);

            if (!is_null($arrayNode->attributes->getNamedItem("field"))) {
                /** @var string $fieldName */
                $fieldName = (string)$arrayNode->attributes->getNamedItem("field")->nodeValue;

                $fieldMappings[$fieldName] = $arrayMapping;

            } else {
                $fieldMappings[] = $arrayMapping;
            }
        }

        foreach ($xpath->query('./rdm:list', $parentNode) as $listNode) {
            /** @var DOMNode $listNode */

            /** @var string $defaultColumnName */
            $defaultColumnName = "";

            if (!is_null($choiceDefaultColumnName)) {
                $defaultColumnName = $choiceDefaultColumnName;

            } elseif (!is_null($listNode->attributes->getNamedItem("field"))) {
                $defaultColumnName = (string)$listNode->attributes->getNamedItem("field")->nodeValue;
            }

            /** @var ListMapping $listMapping */
            $listMapping = $this->readList($listNode, $mappingFile, $defaultColumnName);

            if (!is_null($listNode->attributes->getNamedItem("field"))) {
                /** @var string $fieldName */
                $fieldName = (string)$listNode->attributes->getNamedItem("field")->nodeValue;

                $fieldMappings[$fieldName] = $listMapping;

            } else {
                $fieldMappings[] = $listMapping;
            }
        }

        foreach ($xpath->query('./rdm:null', $parentNode) as $nullNode) {
            /** @var DOMNode $nullNode */

            if (!is_null($nullNode->attributes->getNamedItem("field"))) {
                /** @var string $fieldName */
                $fieldName = (string)$nullNode->attributes->getNamedItem("field")->nodeValue;

                $fieldMappings[$fieldName] = new NullMapping("in file '{$mappingFile}'");

            } else {
                $fieldMappings[] = new NullMapping("in file '{$mappingFile}'");
            }
        }

        foreach ($xpath->query('./rdm:nullable', $parentNode) as $nullableNode) {
            /** @var DOMNode $nullableNode */

            /** @var NullableMapping $nullableMapping */
            $nullableMapping = $this->readNullable($nullableNode, $mappingFile);

            if (!is_null($nullableNode->attributes->getNamedItem("field"))) {
                /** @var string $fieldName */
                $fieldName = (string)$nullableNode->attributes->getNamedItem("field")->nodeValue;

                $fieldMappings[$fieldName] = $nullableMapping;

            } else {
                $fieldMappings[] = $nullableMapping;
            }
        }

        foreach ($xpath->query('./rdm:import', $parentNode) as $importNode) {
            /** @var DOMNode $importNode */

            /** @var string $path */
            $path = (string)$importNode->attributes->getNamedItem("path")->nodeValue;

            /** @var string $forcedFieldName */
            $forcedFieldName = null;

            if (!is_null($importNode->attributes->getNamedItem("field"))) {
                $forcedFieldName = (string)$importNode->attributes->getNamedItem("field")->nodeValue;
            }

            /** @var string $columnPrefix */
            $columnPrefix = "";

            if (!is_null($importNode->attributes->getNamedItem("column-prefix"))) {
                $columnPrefix = (string)$importNode->attributes->getNamedItem("column-prefix")->nodeValue;
            }

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

        return $fieldMappings;
    }

    private function readService(DOMNode $serviceNode, string $mappingFile): ServiceMapping
    {
        /** @var bool $lax */
        $lax = false;

        if ($serviceNode->attributes->getNamedItem("lax") instanceof DOMNode) {
            $lax = strtolower($serviceNode->attributes->getNamedItem("lax")->nodeValue) === 'true';
        }

        /** @var string $serviceId */
        $serviceId = (string)$serviceNode->attributes->getNamedItem("id")->nodeValue;

        return new ServiceMapping(
            $this->kernel->getContainer(),
            $serviceId,
            $lax,
            sprintf(
                "in file '%s'",
                $mappingFile
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
            $key = null;

            if ($entryNode->attributes->getNamedItem("key") instanceof DOMNode) {
                $key = (string)$entryNode->attributes->getNamedItem("key")->nodeValue;
            }

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
            "in file '%s'",
            $mappingFile
        ));
    }

    private function readList(
        DOMNode $listNode,
        string $mappingFile,
        string $columnName
    ): ListMapping {
        if (!is_null($listNode->attributes->getNamedItem("column"))) {
            $columnName = (string)$listNode->attributes->getNamedItem("column")->nodeValue;
        }

        /** @var array<MappingInterface> $entryMappings */
        $entryMappings = $this->readFieldMappings($listNode, $mappingFile);

        $column = new Column(
            $columnName,
            Type::getType("string"),
            []
        );

        return new ListMapping($column, array_values($entryMappings)[0], sprintf(
            "in file '%s'",
            $mappingFile
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
                "A nullable mapping can only contain one inner mapping in '%s'!",
                $mappingFile
            ));
        }

        /** @var MappingInterface $innerMapping */
        $innerMapping = array_values($innerMappings)[0];

        /** @var Column|null $column */
        $column = null;

        if (!is_null($nullableNode->attributes->getNamedItem("column"))) {
            /** @var string $columnName */
            $columnName = (string)$nullableNode->attributes->getNamedItem("column")->nodeValue;

            $column = new Column(
                $columnName,
                Type::getType("boolean"),
                [
                    'notnull' => false
                ]
            );
        }

        return new NullableMapping($innerMapping, $column, sprintf(
            "in file '%s'",
            $mappingFile
        ));
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

        /** @var string $columnName */
        $columnName = null;

        /** @var Type $type */
        $type = Type::getType('string');

        foreach ($fieldNode->attributes as $key => $attribute) {
            /** @var DOMAttr $attribute */

            $attributeValue = (string)$attribute->nodeValue;

            if ($key === 'column') {
                $columnName = $attributeValue;

            } elseif ($key === 'name') {
                if (empty($columnName)) {
                    $columnName = $attributeValue;
                }

            } elseif ($key === 'type') {
                $type = Type::getType($attributeValue);

            } elseif (isset($keyMap[$key])) {
                if ($key === 'nullable') {
                    # target is 'notnull', so falue is reversed
                    $attributeValue = ($attributeValue === 'false');
                }

                $attributes[$keyMap[$key]] = $attributeValue;
            }
        }

        $column = new Column(
            $columnName,
            $type,
            $attributes
        );

        return $column;
    }

}
