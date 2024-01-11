<?php
/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 *
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Mapping;

use Addiks\RDMBundle\Mapping\MappingInterface;
use Webmozart\Assert\Assert;
use Addiks\RDMBundle\Hydration\HydrationContextInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FixNativeMapping implements MappingInterface
{
    public function __construct(
        public readonly string $jsonSerializedValue,
        public readonly string $origin = "unknown"
    ) {
    }
    
    public function describeOrigin(): string
    {
        return $this->origin;
    }

    public function collectDBALColumns(): array
    {
        return [];
    }

    public function resolveValue(
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns
    ) {
        return json_decode($this->jsonSerializedValue, true);
    }

    public function revertValue(
        HydrationContextInterface $context,
        $valueFromEntityField
    ): array {
        return [];
    }

    public function assertValue(
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns,
        $actualValue
    ): void {
        Assert::same($this->jsonSerializedValue, json_encode($actualValue));
    }

    public function wakeUpMapping(ContainerInterface $container): void
    {
    }
}
