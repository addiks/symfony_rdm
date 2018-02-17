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

use Addiks\RDMBundle\ValueResolver\ValueResolverInterface;
use Addiks\RDMBundle\Mapping\MappingInterface;

final class ValueResolverAggregate implements ValueResolverInterface
{

    /**
     * @var array<ValueResolverInterface>
     */
    private $innerValueResolvers = array();

    public function __construct(array $innerValueResolvers)
    {
        foreach ($innerValueResolvers as $mappingClassName => $innerValueResolver) {
            /** @var ValueResolverInterface $innerValueResolver */

            $this->addInnerValueResolver($mappingClassName, $innerValueResolver);
        }
    }

    public function revertValue(
        MappingInterface $fieldMapping,
        $entity,
        $valueFromEntityField
    ): array {
        /** @var string $mappingClassName */
        $mappingClassName = get_class($fieldMapping);

        if (isset($this->innerValueResolvers[$mappingClassName])) {
            return $this->innerValueResolvers[$mappingClassName]->revertValue(
                $fieldMapping,
                $entity,
                $valueFromEntityField
            );
        }

        return [];
    }

    public function resolveValue(
        MappingInterface $fieldMapping,
        $entity,
        array $dataFromAdditionalColumns
    ) {
        /** @var string $mappingClassName */
        $mappingClassName = get_class($fieldMapping);

        if (isset($this->innerValueResolvers[$mappingClassName])) {
            return $this->innerValueResolvers[$mappingClassName]->resolveValue(
                $fieldMapping,
                $entity,
                $dataFromAdditionalColumns
            );
        }
    }

    public function assertValue(
        MappingInterface $fieldMapping,
        $entity,
        array $dataFromAdditionalColumns,
        $actualValue
    ): void {
        /** @var string $mappingClassName */
        $mappingClassName = get_class($fieldMapping);

        if (isset($this->innerValueResolvers[$mappingClassName])) {
            $this->innerValueResolvers[$mappingClassName]->assertValue(
                $fieldMapping,
                $entity,
                $dataFromAdditionalColumns,
                $actualValue
            );
        }
    }

    private function addInnerValueResolver(
        string $mappingClassName,
        ValueResolverInterface $innerValueResolver
    ): void {
        $this->innerValueResolvers[$mappingClassName] = $innerValueResolver;
    }

}
