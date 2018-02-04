<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\DataLoader;

use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Addiks\RDMBundle\DataLoader\DataLoaderInterface;
use Addiks\RDMBundle\DataLoader\DataLoaderFactoryInterface;

/**
 * A simple data-loader-factory that chooses which data-loader to create using a parameter from the container.
 */
final class ChoosingDataLoaderFactory implements DataLoaderFactoryInterface
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $serviceId;

    public function __construct(
        ContainerInterface $container,
        array $serviceIdMap,
        string $choosingParameterName,
        string $defaultServiceId
    ) {
        if ($container->hasParameter($choosingParameterName)) {
            /** @var string $determinator */
            $determinator = $container->getParameter($choosingParameterName);

            if (!isset($serviceIdMap[$determinator])) {
                throw new InvalidArgumentException(sprintf(
                    "Invalid value '%s' for parameter '%s', allowed values are: %s",
                    $determinator,
                    $choosingParameterName,
                    implode(", ", array_keys($serviceIdMap))
                ));
            }

            $serviceId = $serviceIdMap[$determinator];

        } else {
            /** @var string $serviceId */
            $serviceId = $defaultServiceId;
        }

        # Spot errors early (this should not actually load the service)
        if (!$container->has($serviceId)) {
            throw new InvalidArgumentException(sprintf(
                "The specified service '%s' does not exist!",
                $serviceId
            ));
        }

        $this->container = $container;
        $this->serviceId = $serviceId;
    }

    public function createDataLoader(): DataLoaderInterface
    {
        return $this->container->get($this->serviceId);
    }

}
