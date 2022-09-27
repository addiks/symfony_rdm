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

use Doctrine\Persistence\Mapping\Driver\FileLocator;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Addiks\RDMBundle\Mapping\EntityMappingInterface;

final class MappingPHPDriver implements MappingDriverInterface
{

    /**
     * @var FileLocator
     */
    private $fileLocator;

    public function __construct(FileLocator $fileLocator)
    {
        $this->fileLocator = $fileLocator;
    }

    public function loadRDMMetadataForClass(string $className): ?EntityMappingInterface
    {
        /** @var ?EntityMappingInterface $mapping */
        $mapping = null;

        if ($this->fileLocator->fileExists($className)) {
            /** @var string $mappingFile */
            $mappingFile = $this->fileLocator->findMappingFile($className);

            if (file_exists($mappingFile)) {
                /** @var mixed $mappingCandidate */
                $mappingCandidate = null;

                (
                    /**
                     * @param mixed $rdmMapping
                     */
                    function (&$rdmMapping) use ($mappingFile): void {
                        /** @psalm-suppress UnresolvableInclude */
                        include $mappingFile;
                    }
                )($mappingCandidate);

                if ($mappingCandidate instanceof EntityMappingInterface) {
                    $mapping = $mappingCandidate;
                }
            }
        }

        return $mapping;
    }

}
