<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\ValueResolver;

use Addiks\RDMBundle\Mapping\MappingInterface;
use Addiks\RDMBundle\Exception\FailedRDMAssertionExceptionInterface;

interface ValueResolverInterface
{

    /**
     * Resolves a mapping to a value.
     *
     * @param MappingInterface $fieldMapping
     * @param object           $entity
     * @param array<scalar>    $dataFromAdditionalColumns
     *
     * @return mixed
     */
    public function resolveValue(
        MappingInterface $fieldMapping,
        $entity,
        array $dataFromAdditionalColumns
    );

    /**
     * Reverts a resolved value back to it's originated data.
     * The data in the returned array will be the same format as
     * in $dataFromAdditionalColumns from "self::resolveValue()".
     *
     * @param MappingInterface $fieldMapping
     * @param object           $entity
     * @param mixed            $valueFromEntityField
     *
     * @return array<scalar>
     */
    public function revertValue(
        MappingInterface $fieldMapping,
        $entity,
        $valueFromEntityField
    ): array;

    /**
     * Checks if the given value as a valid value for given mapping.
     *
     * @param MappingInterface $fieldMapping
     * @param object           $entity
     * @param array<mixed>     $dataFromAdditionalColumns
     * @param mixed            $actualValue
     *
     * @throws FailedRDMAssertionExceptionInterface
     */
    public function assertValue(
        MappingInterface $fieldMapping,
        $entity,
        array $dataFromAdditionalColumns,
        $actualValue
    ): void;

}
