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

use ErrorException;
use Doctrine\Common\Persistence\Mapping\Driver\FileLocator;
use Symfony\Component\Yaml\Yaml;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Addiks\RDMBundle\Mapping\EntityMappingInterface;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Addiks\RDMBundle\Mapping\EntityMapping;
use Addiks\RDMBundle\Mapping\ServiceMapping;
use Addiks\RDMBundle\Mapping\ChoiceMapping;

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

    public function loadRDMMetadataForClass(string $className): ?EntityMappingInterface
    {
        /** @var ?EntityMappingInterface $mapping */
        $mapping = null;

        /** @var array<MappingInterface> $fieldMappings */
        $fieldMappings = array();

        if ($this->fileLocator->fileExists($className)) {
            /** @var string $mappingFile */
            $mappingFile = $this->fileLocator->findMappingFile($className);

            if (file_exists($mappingFile)) {
                /** @var mixed $yaml */
                $yaml = Yaml::parse(file_get_contents($mappingFile));

                if (is_array($yaml) && isset($yaml[$className])) {
                    $this->readMappings($fieldMappings, $yaml[$className], $mappingFile);
                }
            }
        }

        if (!empty($fieldMappings)) {
            $mapping = new EntityMapping($className, $fieldMappings);
        }

        return $mapping;
    }

    private function readMappings(array &$fieldMappings, array $yaml, string $mappingFile): void
    {
        if (isset($yaml['choices'])) {
            $this->readChoices($fieldMappings, $yaml['choices'], $mappingFile);
        }
        if (isset($yaml['services'])) {
            $this->readServices($fieldMappings, $yaml['services'], $mappingFile);
        }
    }

    private function readChoices(array &$fieldMappings, array $servicesYaml, string $mappingFile): void
    {
        foreach ($servicesYaml as $fieldName => $choiceYaml) {
            /** @var array $yamlService */

            $fieldMappings[$fieldName] = $this->readChoice($choiceYaml, $fieldName, $mappingFile);
        }
    }

    private function readServices(array &$fieldMappings, array $servicesYaml, string $mappingFile): void
    {
        foreach ($servicesYaml as $fieldName => $serviceYaml) {
            /** @var array $serviceYaml */

            $fieldMappings[$fieldName] = $this->readService($serviceYaml, $fieldName, $mappingFile);
        }
    }

    private function readOneMapping(array $yaml, string $fieldName, string $mappingFile): ?MappingInterface
    {
        if (isset($yaml['choice'])) {
            return $this->readChoice($yaml['choice'], $fieldName, $mappingFile);
        }
        if (isset($yaml['service'])) {
            return $this->readService($yaml['service'], $fieldName, $mappingFile);
        }

        return null;
    }

    private function readChoice(array $choiceYaml, string $fieldName, string $mappingFile): ChoiceMapping
    {
        /** @var array<MappingInterface> $choiceMappings */
        $choiceMappings = array();

        /** @var string $determinatorColumnName */
        $determinatorColumnName = $fieldName;

        if ($choiceYaml['column']) {
            $determinatorColumnName = (string)$choiceYaml['column'];
        }

        foreach ($choiceYaml['choices'] as $determinator => $choiceYaml) {
            /** @var ?MappingInterface $mapping */
            $mapping = $this->readOneMapping($choiceYaml, $fieldName, $mappingFile);

            if ($mapping instanceof MappingInterface) {
                $choiceMappings[$determinator] = $mapping;
            }
        }

        return new ChoiceMapping($determinatorColumnName, $choiceMappings, sprintf(
            "in file '%s'",
            $mappingFile
        ));
    }

    private function readService(array $serviceYaml, string $fieldName, string $mappingFile): ServiceMapping
    {
        if (!isset($serviceYaml['id'])) {
            throw new ErrorException(sprintf(
                "Missing key 'id' on service-reference in file %s on field %s!",
                $mappingFile,
                $fieldName
            ));
        }

        /** @var string $serviceId */
        $serviceId = $serviceYaml['id'];

        /** @var bool $lax */
        $lax = false;

        if (isset($serviceYaml["lax"])) {
            $lax = (bool)$serviceYaml["lax"];
        }

        return new ServiceMapping($serviceId, $lax, sprintf(
            "in file '%s'",
            $mappingFile
        ));
    }

}
