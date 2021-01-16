<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Addiks\RDMBundle\Exception\FailedRDMAssertionException;
use ReflectionClass;
use Addiks\RDMBundle\Tests\Hydration\EntityExample;
use Addiks\RDMBundle\Tests\Hydration\ServiceExample;
use Addiks\RDMBundle\Tests\ValueObjectExample;

final class FailedRDMAssertionExceptionTest extends TestCase
{

    /**
     * @var FailedRDMAssertionException
     */
    private $exception;

    public function setUp(): void
    {
        $this->exception = new FailedRDMAssertionException("Some Message!", "some_type", [
            'lorem' => 'ipsum',
            'dolor' => 'sit'
        ]);
    }

    /**
     * @test
     */
    public function shouldStoreType()
    {
        $this->assertEquals("some_type", $this->exception->getType());
    }

    /**
     * @test
     */
    public function shouldStoreParameters()
    {
        $this->assertEquals([
            'lorem' => 'ipsum',
            'dolor' => 'sit'
        ], $this->exception->getParameters());
    }

    /**
     * @test
     */
    public function shouldCreateExpectedDifferentServiceException()
    {
        $reflectionClass = new ReflectionClass(EntityExample::class);

        $serviceA = new ServiceExample("lorem", 123);
        $serviceB = new ServiceExample("ipsum", 456);

        /** @var FailedRDMAssertionException $actualException */
        $actualException = FailedRDMAssertionException::expectedDifferentService(
            "some_service",
            $reflectionClass,
            $serviceA,
            $serviceB
        );

        $this->assertEquals(
            sprintf(
                "Expected service 'some_service' (%s#%s) on entity %s, was %s#%s instead!",
                ServiceExample::class,
                spl_object_hash($serviceA),
                EntityExample::class,
                ServiceExample::class,
                spl_object_hash($serviceB)
            ),
            $actualException->getMessage()
        );
    }

    /**
     * @test
     */
    public function shouldCreateExpectedInstanceOfException()
    {
        /** @var FailedRDMAssertionException $actualException */
        $actualException = FailedRDMAssertionException::expectedInstanceOf(
            ValueObjectExample::class,
            EntityExample::class,
            "some origin"
        );

        $this->assertEquals(
            sprintf(
                "Expected instance of %s instead of %s as specified in some origin!",
                ValueObjectExample::class,
                EntityExample::class
            ),
            $actualException->getMessage()
        );
    }

    /**
     * @test
     */
    public function shouldCreateExpectedArrayException()
    {
        /** @var FailedRDMAssertionException $actualException */
        $actualException = FailedRDMAssertionException::expectedArray(
            ValueObjectExample::class,
            "some origin"
        );

        $this->assertEquals(
            sprintf(
                "Expected array, got string as specified in some origin!",
                ValueObjectExample::class
            ),
            $actualException->getMessage()
        );
    }

}
