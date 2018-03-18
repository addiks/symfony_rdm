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

use Addiks\RDMBundle\ValueResolver\CallDefinitionExecuterInterface;
use Addiks\RDMBundle\Mapping\CallDefinitionInterface;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Addiks\RDMBundle\ValueResolver\ValueResolverInterface;

final class CallDefinitionExecuter implements CallDefinitionExecuterInterface
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ValueResolverInterface
     */
    private $argumentResolver;

    public function __construct(
        ContainerInterface $container,
        ValueResolverInterface $argumentResolver
    ) {
        $this->container = $container;
        $this->argumentResolver = $argumentResolver;
    }

    public function executeCallDefinition(
        CallDefinitionInterface $callDefinition,
        $entity,
        array $dataFromAdditionalColumns
    ) {
        /** @var mixed $result */
        $result = null;

        /** @var string $objectReference */
        $objectReference = $callDefinition->getObjectReference();

        /** @var string $routineName */
        $routineName = $callDefinition->getRoutineName();

        /** @var array<MappingInterface> $argumentMappings */
        $argumentMappings = $callDefinition->getArgumentMappings();

        /** @var null|object|string $callee */
        $callee = $this->resolveCallee($objectReference, $entity);

        /** @var array<mixed> $arguments */
        $arguments = $this->resolveArguments(
            $argumentMappings,
            $entity,
            $dataFromAdditionalColumns
        );

        if (is_null($callee)) {
            $result = call_user_func_array($routineName, $arguments);

        } elseif (is_string($callee)) {
            $result = call_user_func_array("{$callee}::{$routineName}", $arguments);

        } else {
            $result = call_user_func_array([$callee, $routineName], $arguments);
        }

        return $result;
    }

    /**
     * @param object $entity
     *
     * (This return type should be nullable, but there seems to be a bug in current version psalm preventing it.)
     *
     * @return object|string
     */
    private function resolveCallee(string $objectReference, $entity) {
        /** @var object|string $callee */
        $callee = null;

        if (in_array($objectReference, ['self', 'this', '$this'])) {
            $callee = $entity;

        } elseif ($objectReference[0] === '@') {
            /** @var string $serviceId */
            $serviceId = substr($objectReference, 1);

            $callee = $this->container->get($serviceId);

        } elseif (class_exists($objectReference)) {
            $callee = $objectReference;
        }

        return $callee;
    }

    /**
     * @param array<MappingInterface> $argumentMappings
     * @param object                  $entity
     * @param array<scalar>           $dataFromAdditionalColumns
     *
     * @return array<mixed>
     */
    private function resolveArguments(
        array $argumentMappings,
        $entity,
        array $dataFromAdditionalColumns
    ): array {
        /** @var array<mixed> $arguments */
        $arguments = array();

        foreach ($argumentMappings as $argumentMapping) {
            /** @var MappingInterface $argumentMapping */

            $arguments[] = $this->argumentResolver->resolveValue(
                $argumentMapping,
                $entity,
                $dataFromAdditionalColumns
            );
        }

        return $arguments;
    }

}
