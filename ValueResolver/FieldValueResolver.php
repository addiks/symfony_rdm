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

namespace Addiks\RDMBundle\ValueResolver;

use Addiks\RDMBundle\ValueResolver\ValueResolverInterface;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Addiks\RDMBundle\Mapping\FieldMappingInterface;
use Doctrine\DBAL\Schema\Column;
use Addiks\RDMBundle\Exception\FailedRDMAssertionException;
use Addiks\RDMBundle\Hydration\HydrationContextInterface;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Connection;

final class FieldValueResolver implements ValueResolverInterface
{

    public function resolveValue(
        MappingInterface $fieldMapping,
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns
    ) {
        /** @var mixed $value */
        $value = null;

        if ($fieldMapping instanceof FieldMappingInterface) {
            /** @var Column $dbalColumn */
            $dbalColumn = $fieldMapping->getDBALColumn();

            /** @var Type $type */
            $type = $dbalColumn->getType();

            /** @var Connection $connection */
            $connection = $context->getEntityManager()->getConnection();

            if (isset($dataFromAdditionalColumns[$dbalColumn->getName()])) {
                $value = $dataFromAdditionalColumns[$dbalColumn->getName()];

                $value = $type->convertToPHPValue(
                    $value,
                    $connection->getDatabasePlatform()
                );
            }
        }

        return $value;
    }

    public function revertValue(
        MappingInterface $fieldMapping,
        HydrationContextInterface $context,
        $valueFromEntityField
    ): array {
        /** @var mixed $data */
        $data = array();

        if ($fieldMapping instanceof FieldMappingInterface) {
            /** @var Column $dbalColumn */
            $dbalColumn = $fieldMapping->getDBALColumn();

            /** @var Type $type */
            $type = $dbalColumn->getType();

            /** @var Connection $connection */
            $connection = $context->getEntityManager()->getConnection();

            /** @var scalar $databaseValue */
            $databaseValue = $type->convertToDatabaseValue(
                $valueFromEntityField,
                $connection->getDatabasePlatform()
            );

            # TODO: use doctrine's field to value mapping system here (whatever that is)
            $data[$dbalColumn->getName()] = $databaseValue;
        }

        return $data;
    }

    public function assertValue(
        MappingInterface $fieldMapping,
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns,
        $actualValue
    ): void {
    }

}
