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

use Addiks\RDMBundle\Mapping\FieldMappingInterface;
use Doctrine\DBAL\Schema\Column;
use Addiks\RDMBundle\Hydration\HydrationContextInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Connection;

final class FieldMapping implements FieldMappingInterface
{

    /**
     * @var Column
     */
    private $dbalColumn;

    /**
     * @var string
     */
    private $origin;

    public function __construct(
        Column $dbalColumn,
        string $origin = "unknown"
    ) {
        $this->dbalColumn = $dbalColumn;
        $this->origin = $origin;
    }

    public function getDBALColumn(): Column
    {
        return $this->dbalColumn;
    }

    public function describeOrigin(): string
    {
        return $this->origin;
    }

    public function collectDBALColumns(): array
    {
        return [$this->dbalColumn];
    }

    public function resolveValue(
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns
    ) {
        /** @var mixed $value */
        $value = null;

        /** @var Type $type */
        $type = $this->dbalColumn->getType();

        /** @var Connection $connection */
        $connection = $context->getEntityManager()->getConnection();

        if (isset($dataFromAdditionalColumns[$this->dbalColumn->getName()])) {
            $value = $dataFromAdditionalColumns[$this->dbalColumn->getName()];

            $value = $type->convertToPHPValue(
                $value,
                $connection->getDatabasePlatform()
            );
        }

        return $value;
    }

    public function revertValue(
        HydrationContextInterface $context,
        $valueFromEntityField
    ): array {
        /** @var mixed $data */
        $data = array();

        /** @var Type $type */
        $type = $this->dbalColumn->getType();

        /** @var Connection $connection */
        $connection = $context->getEntityManager()->getConnection();

        /** @var scalar $databaseValue */
        $databaseValue = $type->convertToDatabaseValue(
            $valueFromEntityField,
            $connection->getDatabasePlatform()
        );

        $data[$this->dbalColumn->getName()] = $databaseValue;

        return $data;
    }

    public function assertValue(
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns,
        $actualValue
    ): void {
    }

    public function wakeUpMapping(ContainerInterface $container): void
    {
    }

}
