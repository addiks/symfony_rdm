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

use Doctrine\DBAL\Schema\Column;
use Addiks\RDMBundle\Hydration\HydrationContextInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Addiks\RDMBundle\Exception\FailedRDMAssertionExceptionInterface;

/**
 * A class that implements this interface indicates that it to be used as mapping-information for this RDM bundle.
 */
interface MappingInterface
{

    /**
     * Returns a human-readable string describing where this mapping was defined.
     *
     * Examples:
     *  - "in field 'foo' of entity 'Lorem\Ipsum'"
     *  - "in file 'foo/bar/baz.orm.xml' on line 123"
     *  - "in the table 'entity_mappings' in row with id 456"
     */
    public function describeOrigin(): string;

    /**
     * Collects metadata about additional columns from this mapping (or it's sub-mappings).
     *
     * @return array<Column>
     */
    public function collectDBALColumns(): array;

    /**
     * Resolves data from the database to a mapped value.
     *
     * @param array<scalar|array>    $dataFromAdditionalColumns
     *
     * @return mixed
     */
    public function resolveValue(
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns
    );

    /**
     * Reverts a resolved value back to it's originated data.
     * The data in the returned array will be the same format as
     * in $dataFromAdditionalColumns from "self::resolveValue()".
     *
     * @param mixed            $valueFromEntityField
     *
     * @return array<string, scalar|null>
     */
    public function revertValue(
        HydrationContextInterface $context,
        $valueFromEntityField
    ): array;

    /**
     * Checks if the given value as a valid value for this mapping.
     *
     * @param array<mixed>     $dataFromAdditionalColumns
     * @param mixed            $actualValue
     *
     * @throws FailedRDMAssertionExceptionInterface
     */
    public function assertValue(
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns,
        $actualValue
    ): void;

    /**
     * Mapping objects can be cached. When cached, mapping objects get serialized.
     * Sometimes part's of mapping objects cannot be serialized.
     *
     * This method gets called directly after unserializing a mapping-object when loading it from cache.
     * It allows the mapping-objects to re-fill themself with other objects that may not be able to get serialized.
     */
    public function wakeUpMapping(ContainerInterface $container): void;

}
