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

use Addiks\RDMBundle\Hydration\HydrationContextInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

interface CallDefinitionInterface
{

    /**
     * @param array<array-key, mixed> $dataFromAdditionalColumns
     *
     * @return mixed
     */
    public function execute(
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns
    );

    public function getObjectReference(): ?string;

    public function getRoutineName(): string;

    /**
     * @return array<MappingInterface>
     */
    public function getArgumentMappings(): array;

    public function isStaticCall(): bool;

    public function wakeUpCall(ContainerInterface $container): void;

}
