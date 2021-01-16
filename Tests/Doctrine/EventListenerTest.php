<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Tests\Doctrine;

use PHPUnit\Framework\TestCase;
use Addiks\RDMBundle\Doctrine\EventListener;
use Addiks\RDMBundle\Hydration\EntityHydratorInterface;
use Addiks\RDMBundle\Tests\Hydration\EntityExample;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Addiks\RDMBundle\DataLoader\DataLoaderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Addiks\RDMBundle\Mapping\EntityMappingInterface;

final class EventListenerTest extends TestCase
{

    /**
     * @var EventListener
     */
    private $eventListener;

    /**
     * @var EntityHydratorInterface
     */
    private $entityServiceHydrator;

    /**
     * @var MappingDriverInterface
     */
    private $mappingDriver;

    /**
     * @var DataLoaderInterface
     */
    private $dbalDataLoader;

    public function setUp(): void
    {
        $this->entityServiceHydrator = $this->createMock(EntityHydratorInterface::class);
        $this->mappingDriver = $this->createMock(MappingDriverInterface::class);
        $this->dbalDataLoader = $this->createMock(DataLoaderInterface::class);

        $this->eventListener = new EventListener(
            $this->entityServiceHydrator,
            $this->mappingDriver,
            $this->dbalDataLoader
        );
    }

    /**
     * @test
     */
    public function shouldHydrateEntityOnPostLoad()
    {
        /** @var LifecycleEventArgs $arguments */
        $arguments = $this->createMock(LifecycleEventArgs::class);

        $entity = new EntityExample();

        $arguments->method('getEntity')->willReturn($entity);
        $arguments->method('getEntityManager')->willReturn($this->createMock(EntityManagerInterface::class));

        $this->entityServiceHydrator->expects($this->once())->method('hydrateEntity')->with($entity);

        $this->eventListener->postLoad($arguments);
    }

    /**
     * @test
     */
    public function shouldAssertHydrationOnPrePersist()
    {
        /** @var LifecycleEventArgs $arguments */
        $arguments = $this->createMock(LifecycleEventArgs::class);

        $entity = new EntityExample();

        $arguments->method('getEntity')->willReturn($entity);
        $arguments->method('getEntityManager')->willReturn($this->createMock(EntityManagerInterface::class));

        $this->entityServiceHydrator->expects($this->once())->method('assertHydrationOnEntity')->with($entity);

        $this->eventListener->prePersist($arguments);
    }

    /**
     * @test
     */
    public function shouldRemoveEntityProxiesOnlyWhenInitialized()
    {
        $this->mappingDriver->method('loadRDMMetadataForClass')->willReturn(
            $this->createMock(EntityMappingInterface::class)
        );

        /** @var Proxy $proxiedEntity */
        $proxiedEntity = $this->createMock(Proxy::class);

        $proxiedEntity->method('__isInitialized')->willReturn(true);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);

        /** @var UnitOfWork $unitOfWork */
        $unitOfWork = $this->createMock(UnitOfWork::class);

        $entityManager->method('getUnitOfWork')->willReturn($unitOfWork);

        $unitOfWork->method('getIdentityMap')->willReturn([
            EntityExample::class => [$proxiedEntity]
        ]);

        $unitOfWork->method('isScheduledForDelete')->willReturn(false);

        $this->dbalDataLoader->expects($this->once())->method('storeDBALDataForEntity')->with(
            $this->equalTo($proxiedEntity),
            $this->equalTo($entityManager)
        );

        $this->eventListener->postFlush(new PostFlushEventArgs($entityManager));
    }

    /**
     * @test
     */
    public function shouldIgnoreUninitializedProxies()
    {
        /** @var Proxy $proxiedEntity */
        $proxiedEntity = $this->createMock(Proxy::class);

        $proxiedEntity->method('__isInitialized')->willReturn(false);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);

        /** @var UnitOfWork $unitOfWork */
        $unitOfWork = $this->createMock(UnitOfWork::class);

        $entityManager->method('getUnitOfWork')->willReturn($unitOfWork);

        $unitOfWork->method('getIdentityMap')->willReturn([
            EntityExample::class => [$proxiedEntity]
        ]);

        $unitOfWork->method('isScheduledForDelete')->willReturn(false);

        $this->dbalDataLoader->expects($this->never())->method('storeDBALDataForEntity');

        $this->eventListener->postFlush(new PostFlushEventArgs($entityManager));
    }

}
