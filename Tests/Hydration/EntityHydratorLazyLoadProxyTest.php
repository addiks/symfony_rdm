<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Tests\Hydration;

use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Addiks\RDMBundle\Hydration\EntityHydratorLazyLoadProxy;
use Addiks\RDMBundle\Tests\Hydration\EntityExample;
use Addiks\RDMBundle\Hydration\EntityHydratorInterface;

final class EntityHydratorLazyLoadProxyTest extends TestCase
{

    /**
     * @var EntityHydratorLazyLoadProxy
     */
    private $hydratorProxy;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var EntityHydratorInterface
     */
    private $innerHydrator;

    public function setUp(): void
    {
        $this->innerHydrator = $this->createMock(EntityHydratorInterface::class);

        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->method("get")->will($this->returnValueMap([
            ["some_service_id", ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->innerHydrator]
        ]));

        $this->hydratorProxy = new EntityHydratorLazyLoadProxy($this->container, "some_service_id");
    }

    /**
     * @test
     */
    public function shouldForwardEntityHydration()
    {
        /** @var EntityExample $entity */
        $entity = $this->createMock(EntityExample::class);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $this->innerHydrator->expects($this->once())->method("hydrateEntity")->with(
            $this->equalTo($entity),
            $this->equalTo($entityManager)
        );

        $this->hydratorProxy->hydrateEntity($entity, $entityManager);
    }

    /**
     * @test
     */
    public function shouldForwardEntityHydrationAssertion()
    {
        /** @var EntityExample $entity */
        $entity = $this->createMock(EntityExample::class);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $this->innerHydrator->expects($this->once())->method("assertHydrationOnEntity")->with(
            $this->equalTo($entity),
            $this->equalTo($entityManager)
        );

        $this->hydratorProxy->assertHydrationOnEntity($entity, $entityManager);
    }

}
