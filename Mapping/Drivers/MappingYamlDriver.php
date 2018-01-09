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
use Doctrine\Common\Persistence\Mapping\Driver\FileLocator;
use Symfony\Component\Yaml\Yaml;
use ErrorException;
use Addiks\RDMBundle\Mapping\Annotation\Service;

final class MappingYamlDriver implements MappingDriverInterface
{

    /**
     * @var FileLocator
     */
    private $fileLocator;

    public function __construct(
        FileLocator $fileLocator
    ) {
        $this->fileLocator = $fileLocator;
    }

    public function loadRDMMetadataForClass($className): array
    {
        /** @var array<Service> $services */
        $services = array();

        if ($this->fileLocator->fileExists($className)) {
            /** @var string $mappingFile */
            $mappingFile = $this->fileLocator->findMappingFile($className);

            if (file_exists($mappingFile)) {
                /** @var array $yaml */
                $yaml = Yaml::parse(file_get_contents($mappingFile));

                if (is_array($yaml) && isset($yaml[$className]) && isset($yaml[$className]['services'])) {
                    foreach ($yaml[$className]['services'] as $fieldName => $yamlService) {
                        /** @var array $yamlService */

                        if (!isset($yamlService['id'])) {
                            throw new ErrorException(sprintf(
                                "Missing key 'id' on service-reference in file %s on field services/%s!",
                                $mappingFile,
                                $fieldName
                            ));
                        }

                        /** @var string $serviceId */
                        $serviceId = $yamlService['id'];

                        /** @var bool $lax */
                        $lax = false;

                        if (isset($yamlService["lax"])) {
                            $lax = (strtolower($yamlService["lax"]) === 'true');
                        }

                        $service = new Service();
                        $service->field = $fieldName;
                        $service->id = $serviceId;
                        $service->lax = $lax;

                        $services[] = $service;
                    }
                }
            }
        }

        return $services;
    }

}
