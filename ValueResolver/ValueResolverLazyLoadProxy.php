<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\ValueResolver;

use Addiks\RDMBundle\ValueResolver\ValueResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Addiks\RDMBundle\Mapping\MappingInterface;

final class ValueResolverLazyLoadProxy implements ValueResolverInterface
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $serviceId;

    /**
     * @var ?ValueResolverInterface
     */
    private $innerValueResolver;

    public function __construct(ContainerInterface $container, string $serviceId)
    {
        $this->container = $container;
        $this->serviceId = $serviceId;
    }

    public function resolveValue(
        MappingInterface $fieldMapping,
        $entity,
        array $dataFromAdditionalColumns
    ) {
        return $this->getInnerValueResolver()->resolveValue(
            $fieldMapping,
            $entity,
            $dataFromAdditionalColumns
        );
    }

    public function revertValue(
        MappingInterface $fieldMapping,
        $entity,
        $valueFromEntityField
    ): array {
        return $this->getInnerValueResolver()->revertValue(
            $fieldMapping,
            $entity,
            $valueFromEntityField
        );
    }

    public function assertValue(
        MappingInterface $fieldMapping,
        $entity,
        array $dataFromAdditionalColumns,
        $actualValue
    ) {
        return $this->getInnerValueResolver()->assertValue(
            $fieldMapping,
            $entity,
            $dataFromAdditionalColumns,
            $actualValue
        );
    }

    private function getInnerValueResolver(): ValueResolverInterface
    {
        if (is_null($this->innerValueResolver)) {
            $this->innerValueResolver = $this->container->get($this->serviceId);
        }

        return $this->innerValueResolver;
    }

}
