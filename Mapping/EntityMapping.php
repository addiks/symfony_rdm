<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Mapping;

use Addiks\RDMBundle\Mapping\MappingInterface;
use Addiks\RDMBundle\Mapping\EntityMappingInterface;
use Doctrine\DBAL\Schema\Column;
use Addiks\RDMBundle\Mapping\ObjectMapping;
use Webmozart\Assert\Assert;

final class EntityMapping implements EntityMappingInterface
{

    /**
     * @var string
     */
    private $className;

    /**
     * @var array<MappingInterface>
     */
    private $fieldMappings = array();

    public function __construct(string $className, array $fieldMappings)
    {
        $this->className = $className;

        foreach ($fieldMappings as $fieldName => $fieldMapping) {
            /** @var MappingInterface $fieldMapping */

            Assert::isInstanceOf($fieldMapping, MappingInterface::class);

            $this->fieldMappings[$fieldName] = $fieldMapping;
        }
    }

    public function getEntityClassName(): string
    {
        return $this->className;
    }

    public function getDBALColumn(): ?Column
    {
        return null;
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
        return $this->className;
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
        return null;
    }

    public function getSerializer(): ?CallDefinitionInterface
    {
        return null;
    }

    public function getId(): ?string
    {
        return null;
    }

    public function getReferencedId(): ?string
    {
        return null;
    }

}
