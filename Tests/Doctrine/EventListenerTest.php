<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
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

    public function setUp()
    {
        $this->entityServiceHydrator = $this->createMock(EntityHydratorInterface::class);

        $this->eventListener = new EventListener(
            $this->entityServiceHydrator
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

        $this->entityServiceHydrator->expects($this->once())->method('assertHydrationOnEntity')->with($entity);

        $this->eventListener->prePersist($arguments);
    }

}
