<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\DataLoader;

use Addiks\RDMBundle\DataLoader\DataLoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use ErrorException;

final class DataLoaderLazyLoadProxy implements DataLoaderInterface
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
     * @var ?DataLoaderInterface
     */
    private $loadedDataLoader;

    public function __construct(ContainerInterface $container, string $serviceId)
    {
        $this->container = $container;
        $this->serviceId = $serviceId;
    }

    public function boot(EntityManagerInterface $entityManager): void
    {
        $this->loadDataLoader()->boot($entityManager);
    }

    /**
     * @param object $entity
     */
    public function loadDBALDataForEntity($entity, EntityManagerInterface $entityManager): array
    {
        return $this->loadDataLoader()->loadDBALDataForEntity($entity, $entityManager);
    }

    /**
     * @param object $entity
     */
    public function storeDBALDataForEntity($entity, EntityManagerInterface $entityManager): void
    {
        $this->loadDataLoader()->storeDBALDataForEntity($entity, $entityManager);
    }

    /**
     * @param object $entity
     */
    public function removeDBALDataForEntity($entity, EntityManagerInterface $entityManager): void
    {
        $this->loadDataLoader()->removeDBALDataForEntity($entity, $entityManager);
    }

    public function prepareOnMetadataLoad(EntityManagerInterface $entityManager, ClassMetadata $classMetadata): void
    {
        $this->loadDataLoader()->prepareOnMetadataLoad($entityManager, $classMetadata);
    }

    private function loadDataLoader(): DataLoaderInterface
    {
        if (is_null($this->loadedDataLoader)) {
            /** @var object $loadedDataLoader */
            $loadedDataLoader = $this->container->get($this->serviceId);

            if ($loadedDataLoader instanceof DataLoaderInterface) {
                $this->loadedDataLoader = $loadedDataLoader;

            } else {
                throw new ErrorException(sprintf(
                    "Service with id '%s' must implement %s",
                    $this->serviceId,
                    DataLoaderInterface::class
                ));
            }
        }

        return $this->loadedDataLoader;
    }
}
