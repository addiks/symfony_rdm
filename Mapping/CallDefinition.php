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

use Addiks\RDMBundle\Mapping\CallDefinitionInterface;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Webmozart\Assert\Assert;
use Addiks\RDMBundle\Hydration\HydrationContextInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\ORMException;
use Doctrine\Common\Util\ClassUtils;
use ArgumentCountError;

final class CallDefinition implements CallDefinitionInterface
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string|null
     */
    private $objectReference;

    /**
     * @var string
     */
    private $routineName;

    /**
     * @var array<MappingInterface>
     */
    private $argumentMappings = array();

    /**
     * @var bool
     */
    private $isStaticCall;

    /**
     * @var string
     */
    private $origin;

    public function __construct(
        ContainerInterface $container,
        string $routineName,
        string $objectReference = null,
        array $argumentMappings = array(),
        bool $isStaticCall = false,
        string $origin = "unknown"
    ) {
        $this->routineName = $routineName;
        $this->objectReference = $objectReference;
        $this->isStaticCall = $isStaticCall;
        $this->container = $container;
        $this->origin = $origin;

        foreach ($argumentMappings as $argumentMapping) {
            /** @var MappingInterface $argumentMapping */

            Assert::isInstanceOf($argumentMapping, MappingInterface::class);

            $this->argumentMappings[] = $argumentMapping;
        }
    }

    public function __sleep(): array
    {
        return [
            'objectReference',
            'routineName',
            'argumentMappings',
            'isStaticCall',
            'origin',
        ];
    }

    public function execute(
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns
    ) {
        /** @var mixed $result */
        $result = null;

        /** @var null|object|string $callee */
        $callee = $this->resolveCallee((string)$this->objectReference, $context);

        if ($this->isStaticCall && is_object($callee)) {
            $callee = get_class($callee);
        }

        /** @var array<mixed> $arguments */
        $arguments = $this->resolveArguments(
            $context,
            $dataFromAdditionalColumns
        );

        try {
            if (is_null($callee) && !empty($this->objectReference)) {
                $result = null;

            } elseif (is_null($callee)) {
                $result = call_user_func_array($this->routineName, $arguments);

            } elseif (is_string($callee)) {
                $result = call_user_func_array("{$callee}::{$this->routineName}", $arguments);

            } else {
                $result = call_user_func_array([$callee, $this->routineName], $arguments);
            }

        } catch (ArgumentCountError $exception) {
            /** @var string $calleeDescription */
            $calleeDescription = "";

            if (is_object($callee)) {
                $calleeDescription = get_class($callee);

                if (class_exists(ClassUtils::class)) {
                    $calleeDescription = ClassUtils::getRealClass($calleeDescription);
                }

            } elseif (is_string($callee)) {
                $calleeDescription = $callee;
            }

            if (!empty($calleeDescription)) {
                $calleeDescription .= $this->isStaticCall ?'::' :'->';
            }

            throw new ORMException(sprintf(
                "Wrong number of arguments passed to routine '%s%s' in %s: %s",
                $calleeDescription,
                $this->routineName,
                $this->origin,
                $exception->getMessage()
            ), 0, $exception);
        }

        return $result;
    }

    public function getObjectReference(): ?string
    {
        return $this->objectReference;
    }

    public function getRoutineName(): string
    {
        return $this->routineName;
    }

    public function getArgumentMappings(): array
    {
        return $this->argumentMappings;
    }

    public function isStaticCall(): bool
    {
        return $this->isStaticCall;
    }

    /**
     * (This return type should be nullable, but there seems to be a bug in current version psalm preventing it.)
     *
     * @return object|string|null
     */
    private function resolveCallee(
        string $objectReference,
        HydrationContextInterface $context
    ) {
        /** @var object|string $callee */
        $callee = null;

        if (!empty($objectReference)) {
            /** @var array<mixed> $hydrationStack */
            $hydrationStack = $context->getObjectHydrationStack();

            if ($objectReference[0] === '$') {
                $objectReference = substr($objectReference, 1);
            }

            if (in_array($objectReference, ['root', 'entity'])) {
                $callee = $context->getEntity();

            } elseif (in_array($objectReference, ['self', 'this'])) {
                $callee = $hydrationStack[count($hydrationStack) - 1];

            } elseif (in_array($objectReference, ['parent'])) {
                $callee = $hydrationStack[count($hydrationStack) - 2];

            } elseif ($objectReference[0] === '@') {
                /** @var string $serviceId */
                $serviceId = substr($objectReference, 1);

                $callee = $this->container->get($serviceId);

            } elseif (class_exists($objectReference)) {
                $callee = $objectReference;

            } elseif ($context->hasRegisteredValue($objectReference)) {
                $callee = $context->getRegisteredValue($objectReference);
            }
        }

        return $callee;
    }

    /**
     * @param array<scalar> $dataFromAdditionalColumns
     *
     * @return array<mixed>
     */
    private function resolveArguments(
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns
    ): array {
        /** @var array<mixed> $arguments */
        $arguments = array();

        if (array_key_exists('', $dataFromAdditionalColumns)) {
            $arguments[] = $dataFromAdditionalColumns[''];
            unset($dataFromAdditionalColumns['']);
        }

        foreach ($this->argumentMappings as $argumentMapping) {
            /** @var MappingInterface $argumentMapping */

            $arguments[] = $argumentMapping->resolveValue(
                $context,
                $dataFromAdditionalColumns
            );
        }

        return $arguments;
    }

    public function wakeUpCall(ContainerInterface $container): void {
        $this->container = $container;
    }
}
