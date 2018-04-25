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

final class CallDefinition implements CallDefinitionInterface
{

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

    public function __construct(
        string $routineName,
        string $objectReference = null,
        array $argumentMappings = array(),
        bool $isStaticCall = false
    ) {
        $this->routineName = $routineName;
        $this->objectReference = $objectReference;
        $this->isStaticCall = $isStaticCall;

        foreach ($argumentMappings as $argumentMapping) {
            /** @var MappingInterface $argumentMapping */

            Assert::isInstanceOf($argumentMapping, MappingInterface::class);

            $this->argumentMappings[] = $argumentMapping;
        }
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

}
