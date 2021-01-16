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
use Addiks\RDMBundle\Hydration\HydrationContext;
use Addiks\RDMBundle\Tests\Hydration\EntityExample;
use Doctrine\ORM\EntityManagerInterface;
use Addiks\RDMBundle\Exception\InvalidMappingException;
use Addiks\RDMBundle\Tests\ValueObjectExample;
use InvalidArgumentException;

final class HydrationContextTest extends TestCase
{

    /**
     * @var HydrationContext
     */
    private $context;

    /**
     * @var EntityExample
     */
    private $entity;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function setUp(): void
    {
        $this->entity = $this->createMock(EntityExample::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->context = new HydrationContext(
            $this->entity,
            $this->entityManager
        );
    }

    /**
     * @test
     */
    public function shouldStoreEntity()
    {
        $this->assertSame($this->entity, $this->context->getEntity());
        $this->assertTrue(is_subclass_of($this->context->getEntityClass(), EntityExample::class));
    }

    /**
     * @test
     */
    public function shouldStoreEntityManager()
    {
        $this->assertSame($this->entityManager, $this->context->getEntityManager());
    }

    /**
     * @test
     */
    public function shouldHaveWorkingRegistry()
    {
        $this->assertFalse($this->context->hasRegisteredValue("some_registry_id"));

        $this->context->registerValue("some_registry_id", "Lorem ipsum");

        $this->assertTrue($this->context->hasRegisteredValue("some_registry_id"));
        $this->assertEquals("Lorem ipsum", $this->context->getRegisteredValue("some_registry_id"));

        $this->context->registerValue("some_registry_id", "dolor sit amet");

        $this->assertTrue($this->context->hasRegisteredValue("some_registry_id"));
        $this->assertEquals("dolor sit amet", $this->context->getRegisteredValue("some_registry_id"));
    }

    /**
     * @test
     */
    public function shouldMaintainObjectHydrationStack()
    {
        /** @var ValueObjectExample $object */
        $object = $this->createMock(ValueObjectExample::class);

        $this->assertEquals([$this->entity], $this->context->getObjectHydrationStack());

        $this->context->pushOnObjectHydrationStack($object);

        $this->assertEquals([$this->entity, $object], $this->context->getObjectHydrationStack());

        $this->assertSame($object, $this->context->popFromObjectHydrationStack());

        $this->assertEquals([$this->entity], $this->context->getObjectHydrationStack());
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenPopFromEmptyObjectHydrationStack()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->context->popFromObjectHydrationStack();
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenAccessUnknownRegistry()
    {
        $this->expectException(InvalidMappingException::class);
        $this->context->getRegisteredValue("some_registry_id");
    }

}
