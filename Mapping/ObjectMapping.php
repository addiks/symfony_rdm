<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 *
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Mapping;

use Addiks\RDMBundle\Mapping\ObjectMappingInterface;
use Doctrine\DBAL\Schema\Column;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Webmozart\Assert\Assert;

final class ObjectMapping implements ObjectMappingInterface
{

    /**
     * @var string
     */
    private $className;

    /**
     * @var array<MappingInterface>
     */
    private $fieldMappings = array();

    /**
     * @var CallDefinitionInterface|null
     */
    private $factory;

    /**
     * @var CallDefinitionInterface|null
     */
    private $serializer;

    /**
     * @var string
     */
    private $origin;

    public function __construct(
        string $className,
        array $fieldMappings,
        string $origin = "undefined",
        CallDefinitionInterface $factory = null,
        CallDefinitionInterface $serializer = null
    ) {
        $this->className = $className;
        $this->factory = $factory;
        $this->serializer = $serializer;
        $this->origin = $origin;

        foreach ($fieldMappings as $fieldName => $fieldMapping) {
            /** @var MappingInterface $fieldMapping */

            Assert::isInstanceOf($fieldMapping, MappingInterface::class);

            $this->fieldMappings[$fieldName] = $fieldMapping;
        }
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getFieldMappings(): array
    {
        return $this->fieldMappings;
    }

    public function describeOrigin(): string
    {
        return $this->origin;
    }

    public function collectDBALColumns(): array
    {
        /** @var array<Column> $additionalColumns */
        $additionalColumns = array();

        foreach ($this->fieldMappings as $fieldMapping) {
            /** @var MappingInterface $fieldMapping */

            $additionalColumns = array_merge(
                $additionalColumns,
                $fieldMapping->collectDBALColumns()
            );
        }

        return $additionalColumns;
    }

    public function getFactory(): ?CallDefinitionInterface
    {
        return $this->factory;
    }

    public function getSerializer(): ?CallDefinitionInterface
    {
        return $this->serializer;
    }

}
