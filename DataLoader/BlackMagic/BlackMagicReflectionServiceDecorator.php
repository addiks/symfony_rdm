<?php
/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 *
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\DataLoader\BlackMagic;

use ReflectionProperty;
use Doctrine\DBAL\Schema\Column;
use Doctrine\ORM\EntityManagerInterface;
use Addiks\RDMBundle\DataLoader\BlackMagic\BlackMagicDataLoader;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use ReflectionException;
use Addiks\RDMBundle\Mapping\EntityMappingInterface;
use Addiks\RDMBundle\DataLoader\BlackMagic\BlackMagicColumnReflectionPropertyMock;
use Webmozart\Assert\Assert;
use Doctrine\Persistence\Mapping\ReflectionService;
use ReflectionClass;

final class BlackMagicReflectionServiceDecorator implements ReflectionService
{

    /** @var MappingDriverInterface */
    private $mappingDriver;

    /** @var ReflectionService */
    private $innerReflectionService;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var BlackMagicDataLoader */
    private $dataLoader;

    public function __construct(
        ReflectionService $innerReflectionService,
        MappingDriverInterface $mappingDriver,
        EntityManagerInterface $entityManager,
        BlackMagicDataLoader $dataLoader
    ) {
        $this->innerReflectionService = $innerReflectionService;
        $this->mappingDriver = $mappingDriver;
        $this->entityManager = $entityManager;
        $this->dataLoader = $dataLoader;
    }

    public function getParentClasses($class): array
    {
        return $this->innerReflectionService->getParentClasses($class);
    }

    public function getClassShortName($class): string
    {
        return $this->innerReflectionService->getClassShortName($class);
    }

    public function getClassNamespace($class): string
    {
        return $this->innerReflectionService->getClassNamespace($class);
    }

    public function getClass($class): ?ReflectionClass
    {
        return $this->innerReflectionService->getClass($class);
    }

    public function getAccessibleProperty($class, $property): ?ReflectionProperty
    {
        /** @var ReflectionProperty|null $propertyReflection */
        $propertyReflection = null;

        if ($this->dataLoader->isFakedFieldName($property)) {
            /** @var EntityMappingInterface|null $entityMapping */
            $entityMapping = $this->mappingDriver->loadRDMMetadataForClass($class);

            if ($entityMapping instanceof EntityMappingInterface) {
                /** @var array<Column> $columns */
                $columns = $entityMapping->collectDBALColumns();

                /** @var Column $column */
                foreach ($columns as $column) {
                    /** @var string $fieldName */
                    $fieldName = $this->dataLoader->columnToFieldName($column);

                    if ($property === $fieldName) {
                        $propertyReflection = new BlackMagicColumnReflectionPropertyMock(
                            $this->entityManager,
                            $this->entityManager->getClassMetadata($class),
                            $column,
                            $fieldName,
                            $this->dataLoader
                        );
                        break;
                    }
                }
            }
            
        } else {
            $propertyReflection = $this->innerReflectionService->getAccessibleProperty($class, $property);
        }

        return $propertyReflection;
    }

    public function hasPublicMethod($class, $method): bool
    {
        return $this->innerReflectionService->hasPublicMethod($class, $method);
    }
}
