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

            /** @var boolean $previousUseLibxmlInternalErrors */
            $previousUseLibxmlInternalErrors = libxml_use_internal_errors(true);

            $dom = new DOMDocument();
            $dom->loadXML(file_get_contents($mappingFile));

            /** @var string $rdmPrefix */
            $rdmPrefix = $dom->lookupPrefix(self::RDM_SCHEMA_URI);

            if (!empty($rdmPrefix)) {
                $xpath = new DOMXPath($dom);

                $xpath->registerNamespace($rdmPrefix, self::RDM_SCHEMA_URI);
                $xpath->registerNamespace("d", self::DOCTRINE_SCHEMA_URI);

                foreach ($xpath->query("//d:entity/{$rdmPrefix}:service", $dom) as $serviceNode) {
                    /** @var DOMNode $serviceNode */

                    /** @var string $fieldName */
                    $fieldName = (string)$serviceNode->attributes->getNamedItem("field")->nodeValue;

                    $fieldMappings[$fieldName] = $this->readService($serviceNode, $mappingFile);
                }

                foreach ($xpath->query("//d:entity/{$rdmPrefix}:choice", $dom) as $choiceNode) {
                    /** @var DOMNode $choiceNode */

                    /** @var string $fieldName */
                    $fieldName = (string)$choiceNode->attributes->getNamedItem("field")->nodeValue;

                    $fieldMappings[$fieldName] = $this->readChoice($choiceNode, $mappingFile, $fieldName);
                }
            }

            libxml_use_internal_errors($previousUseLibxmlInternalErrors);
        }

        if (!empty($fieldMappings)) {
            $mapping = new EntityMapping($className, $fieldMappings);
        }

        return $mapping;
    }

    private function readChoice(DOMNode $choiceNode, string $mappingFile, string $defaultColumnName): ChoiceMapping
    {
        /** @var string|Colum $columnName */
        $column = $defaultColumnName;

        if (!is_null($choiceNode->attributes->getNamedItem("column"))) {
            $column = (string)$choiceNode->attributes->getNamedItem("column")->nodeValue;
        }

        /** @var array<MappingInterface> $choiceMappings */
        $choiceMappings = array();

        foreach ($choiceNode->childNodes as $optionNode) {
            /** @var DOMNode $optionNode */

            while ($optionNode instanceof DOMNode && in_array($optionNode->nodeType, [
                XML_TEXT_NODE,
                XML_COMMENT_NODE
            ])) {
                $optionNode = $optionNode->nextSibling;
            }

            if ($optionNode instanceof DOMNode) {
                /** @var mixed $nodeName */
                $nodeName = $optionNode->namespaceURI . ":" . $optionNode->localName;

                if ($nodeName === self::RDM_SCHEMA_URI . ":option") {
                    /** @var string $determinator */
                    $determinator = (string)$optionNode->attributes->getNamedItem("name")->nodeValue;

                    /** @var DOMNode $optionMappingNode */
                    $optionMappingNode = $optionNode->firstChild;

                    while (in_array($optionMappingNode->nodeType, [XML_TEXT_NODE, XML_COMMENT_NODE])) {
                        $optionMappingNode = $optionMappingNode->nextSibling;
                    }

                    if ($optionMappingNode->nodeName === $optionMappingNode->prefix . ":service") {
                        $choiceMappings[$determinator] = $this->readService($optionMappingNode, $mappingFile);

                    } elseif ($optionMappingNode->nodeName === $optionMappingNode->prefix . ":choice") {
                        $choiceMappings[$determinator] = $this->readChoice($optionMappingNode, $mappingFile, sprintf(
                            "%s_%s",
                            $defaultColumnName,
                            $determinator
                        ));
                    }

                } elseif ($nodeName === self::DOCTRINE_SCHEMA_URI . ":field") {
                    $column = $this->readDoctrineField($optionNode);
                }
            }
        }

        return new ChoiceMapping($column, $choiceMappings, sprintf(
            "in file '%s'",
            $mappingFile
        ));
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
