<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Tests\DataLoader;

use PHPUnit\Framework\TestCase;
use Addiks\RDMBundle\DataLoader\DataLoaderLazyLoadProxy;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Addiks\RDMBundle\Tests\Hydration\EntityExample;
use Addiks\RDMBundle\DataLoader\DataLoaderInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

final class DataLoaderLazyLoadProxyTest extends TestCase
{

    /**
     * @var DataLoaderLazyLoadProxy
     */
    private $dataLoaderProxy;

    /**
     * @var DataLoaderInterface
     */
    private $innerDataLoader;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->innerDataLoader = $this->createMock(DataLoaderInterface::class);

        $this->container->method('get')->will($this->returnValueMap([
            ["some_service", ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->innerDataLoader],
        ]));

        $this->dataLoaderProxy = new DataLoaderLazyLoadProxy($this->container, "some_service");
    }

    /**
     * @test
     */
    public function shouldForwardLoadOperation()
    {
        /** @var EntityExample $entity */
        $entity = $this->createMock(EntityExample::class);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $this->innerDataLoader->expects($this->once())->method('loadDBALDataForEntity')->with(
            $this->equalTo($entity),
            $this->equalTo($entityManager)
        );

        $this->dataLoaderProxy->loadDBALDataForEntity($entity, $entityManager);
    }

    /**
     * @test
     */
    public function shouldForwardStoreOperation()
    {
        /** @var EntityExample $entity */
        $entity = $this->createMock(EntityExample::class);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $this->innerDataLoader->expects($this->once())->method('storeDBALDataForEntity')->with(
            $this->equalTo($entity),
            $this->equalTo($entityManager)
        );

        $this->dataLoaderProxy->storeDBALDataForEntity($entity, $entityManager);
    }

    /**
     * @test
     */
    public function shouldForwardRemoveOperation()
    {
        /** @var EntityExample $entity */
        $entity = $this->createMock(EntityExample::class);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $this->innerDataLoader->expects($this->once())->method('removeDBALDataForEntity')->with(
            $this->equalTo($entity),
            $this->equalTo($entityManager)
        );

        $this->dataLoaderProxy->removeDBALDataForEntity($entity, $entityManager);
    }

    /**
     * @test
     */
    public function shouldForwardPrepareOperation()
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);

        /** @var ClassMetadata $classMetadata */
        $classMetadata = $this->createMock(ClassMetadata::class);

        $this->innerDataLoader->expects($this->once())->method('prepareOnMetadataLoad')->with(
            $this->equalTo($entityManager),
            $this->equalTo($classMetadata)
        );

        $this->dataLoaderProxy->prepareOnMetadataLoad($entityManager, $classMetadata);
    }

}
