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
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Doctrine\Common\Persistence\Mapping\Driver\FileLocator;
use Addiks\RDMBundle\Mapping\EntityMappingInterface;
use Addiks\RDMBundle\Mapping\EntityMapping;
use Addiks\RDMBundle\Mapping\ServiceMapping;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Addiks\RDMBundle\Mapping\ChoiceMapping;
use DOMAttr;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Addiks\RDMBundle\Mapping\ObjectMapping;
use Addiks\RDMBundle\Mapping\ObjectMappingInterface;
use Addiks\RDMBundle\Mapping\ChoiceMappingInterface;
use Addiks\RDMBundle\Mapping\ServiceMappingInterface;
use DOMNamedNodeMap;
use Addiks\RDMBundle\Mapping\CallDefinitionInterface;
use Addiks\RDMBundle\Mapping\CallDefinition;
use Addiks\RDMBundle\Mapping\FieldMapping;

final class MappingXmlDriver implements MappingDriverInterface
{

    const RDM_SCHEMA_URI = "http://github.com/addiks/symfony_rdm/tree/master/Resources/mapping-schema.v1.xsd";
    const DOCTRINE_SCHEMA_URI = "http://doctrine-project.org/schemas/orm/doctrine-mapping";

    /**
     * @var FileLocator
     */
    private $fileLocator;

    /**
     * @var string
     */
    private $schemaFilePath;

    public function __construct(
        FileLocator $fileLocator,
        string $schemaFilePath
    ) {
        $this->fileLocator = $fileLocator;
        $this->schemaFilePath = $schemaFilePath;
    }

    public function loadRDMMetadataForClass(string $className): ?EntityMappingInterface
    {
        /** @var ?EntityMappingInterface $mapping */
        $mapping = null;

        /** @var array<MappingInterface> $fieldMappings */
        $fieldMappings = array();

        if ($this->fileLocator->fileExists($className)) {
            /** @var string $mappingFile */
            $mappingFile = $this->fileLocator->findMappingFile($className);

            $dom = new DOMDocument();
            $dom->loadXML(file_get_contents($mappingFile));

            /** @var DOMXPath $xpath */
            $xpath = $this->createXPath($dom->documentElement);

            foreach ($xpath->query("//orm:entity/rdm:service", $dom) as $serviceNode) {
                /** @var DOMNode $serviceNode */

                /** @var string $fieldName */
                $fieldName = (string)$serviceNode->attributes->getNamedItem("field")->nodeValue;

                $fieldMappings[$fieldName] = $this->readService($serviceNode, $mappingFile);
            }

            foreach ($xpath->query("//orm:entity/rdm:choice", $dom) as $choiceNode) {
                /** @var DOMNode $choiceNode */

                /** @var string $fieldName */
                $fieldName = (string)$choiceNode->attributes->getNamedItem("field")->nodeValue;

                $fieldMappings[$fieldName] = $this->readChoice($choiceNode, $mappingFile, $fieldName);
            }

            foreach ($xpath->query("//orm:entity/rdm:object", $dom) as $objectNode) {
                /** @var DOMNode $objectNode */

                /** @var string $fieldName */
                $fieldName = (string)$objectNode->attributes->getNamedItem("field")->nodeValue;

                $fieldMappings[$fieldName] = $this->readObject($objectNode, $mappingFile);
            }
        }

        if (!empty($fieldMappings)) {
            $mapping = new EntityMapping($className, $fieldMappings);
        }

        return $mapping;
    }

    private function createXPath(DOMNode $node): DOMXPath
    {
        $xpath = new DOMXPath($node->ownerDocument);
        $xpath->registerNamespace('rdm', self::RDM_SCHEMA_URI);
        $xpath->registerNamespace('orm', self::DOCTRINE_SCHEMA_URI);

        return $xpath;
    }

    private function readObject(DOMNode $objectNode, string $mappingFile): ObjectMappingInterface
    {
        /** @var DOMNamedNodeMap $attributes */
        $objectNodeAttributes = $objectNode->attributes;

        $className = (string)$objectNodeAttributes->getNamedItem("class")->nodeValue;

        /** @var CallDefinitionInterface|null $factory */
        $factory = null;

        /** @var CallDefinitionInterface|null $factory */
        $serializer = null;

        /** @var DOMXPath $xpath */
        $xpath = $this->createXPath($objectNode);

        foreach ($xpath->query('rdm:factory', $objectNode) as $factoryNode) {
            /** @var DOMNode $factoryNode */

            /** @var array<MappingInterface> $argumentMappings */
            $argumentMappings = $this->readFieldMappings($factoryNode, $mappingFile);

            /** @var string $routineName */
            $routineName = (string)$factoryNode->attributes->getNamedItem('method')->nodeValue;

            /** @var string $objectReference */
            $objectReference = (string)$factoryNode->attributes->getNamedItem('object')->nodeValue;

            $factory = new CallDefinition($routineName, $objectReference, $argumentMappings);
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

        return new ObjectMapping(
            $className,
            $fieldMappings,
            sprintf(
                "in file '%s'",
                $mappingFile
            ),
            $factory,
            $serializer
        );
    }

    private function readCallDefinition(string $callDefinition): CallDefinitionInterface
    {
        /** @var string $routineName */
        $routineName = $callDefinition;

        /** @var string|null $objectReference */
        $objectReference = null;

        $callDefinition = str_replace('->', '::', $callDefinition);

        if (strpos($callDefinition, '::') !== false) {
            [$objectReference, $routineName] = explode('::', $callDefinition);
        }

        return new CallDefinition($routineName, $objectReference);
    }

    private function readChoice(
        DOMNode $choiceNode,
        string $mappingFile,
        string $defaultColumnName
    ): ChoiceMappingInterface {
        /** @var string|Column $columnName */
        $column = $defaultColumnName;

        if (!is_null($choiceNode->attributes->getNamedItem("column"))) {
            $column = (string)$choiceNode->attributes->getNamedItem("column")->nodeValue;
        }

        /** @var array<MappingInterface> $choiceMappings */
        $choiceMappings = array();

        /** @var DOMXPath $xpath */
        $xpath = $this->createXPath($choiceNode);

        foreach ($xpath->query('rdm:option', $choiceNode) as $optionNode) {
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

        foreach ($xpath->query('orm:field', $choiceNode) as $fieldNode) {
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
        DOMNode $objectNode,
        string $mappingFile,
        string $choiceDefaultColumnName = null
    ): array {
        /** @var DOMXPath $xpath */
        $xpath = $this->createXPath($objectNode);

        /** @var array<MappingInterface> $fieldMappings */
        $fieldMappings = array();

        foreach ($xpath->query('rdm:service', $objectNode) as $serviceNode) {
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

        foreach ($xpath->query('rdm:choice', $objectNode) as $choiceNode) {
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

        foreach ($xpath->query('rdm:object', $objectNode) as $objectNode) {
            /** @var DOMNode $objectNode */

            /** @var ObjectMappingInterface $innerObjectMapping */
            $objectMapping = $this->readObject($objectNode, $mappingFile);

            if (!is_null($objectNode->attributes->getNamedItem("field"))) {
                /** @var string $fieldName */
                $fieldName = (string)$objectNode->attributes->getNamedItem("field")->nodeValue;

                $fieldMappings[$fieldName] = $objectMapping;

            } else {
                $fieldMappings[] = $objectMapping;
            }
        }

        foreach ($xpath->query('orm:field', $objectNode) as $fieldNode) {
            /** @var DOMNode $fieldNode */

            /** @var Column $column */
            $column = $this->readDoctrineField($fieldNode);

            $fieldName = $column->getName();

            $fieldMappings[$fieldName] = new FieldMapping(
                $column,
                sprintf("in file '%s'", $mappingFile)
            );
        }

        return $fieldMappings;
    }

    private function readService(DOMNode $serviceNode, string $mappingFile): ServiceMappingInterface
    {
        /** @var bool $lax */
        $lax = false;

        if ($serviceNode->attributes->getNamedItem("lax") instanceof DOMNode) {
            $lax = strtolower($serviceNode->attributes->getNamedItem("lax")->nodeValue) === 'true';
        }

        /** @var string $serviceId */
        $serviceId = (string)$serviceNode->attributes->getNamedItem("id")->nodeValue;

        return new ServiceMapping($serviceId, $lax, sprintf(
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
            'name'              => 'name',
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
        $type = null;

        foreach ($fieldNode->attributes as $key => $attribute) {
            /** @var DOMAttr $attribute */

            $attributeValue = (string)$attribute->nodeValue;

            if ($key === 'name') {
                $columnName = $attributeValue;

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
