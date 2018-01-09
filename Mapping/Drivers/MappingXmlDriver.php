<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Mapping\Drivers;

use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Addiks\RDMBundle\Mapping\Annotation\Service;
use Doctrine\Common\Persistence\Mapping\Driver\FileLocator;
use DOMDocument;
use DOMXPath;
use DOMNode;

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

    public function loadRDMMetadataForClass($className): array
    {
        /** @var array<Service> $services */
        $services = array();

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

                    /** @var bool $lax */
                    $lax = $serviceNode->attributes->getNamedItem("lax");

                    /** @var string $field */
                    $field = (string)$serviceNode->attributes->getNamedItem("field")->value;

                    /** @var string $serviceId */
                    $serviceId = (string)$serviceNode->attributes->getNamedItem("id")->value;

                    if (is_null($lax)) {
                        $lax = false;

                    } else {
                        $lax = (strtolower($lax) === 'true');
                    }

                    $service = new Service();
                    $service->field = $field;
                    $service->id = $serviceId;
                    $service->lax = $lax;

                    $services[] = $service;
                }
            }

            libxml_use_internal_errors($previousUseLibxmlInternalErrors);
        }

        return $services;
    }

}
