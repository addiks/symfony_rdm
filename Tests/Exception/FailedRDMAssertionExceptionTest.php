<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks;

use PHPUnit\Framework\TestCase;
use Addiks\RDMBundle\Exception\FailedRDMAssertionException;
use ReflectionClass;
use Addiks\RDMBundle\Tests\Hydration\EntityExample;
use Addiks\RDMBundle\Tests\Hydration\ServiceExample;

final class FailedRDMAssertionExceptionTest extends TestCase
{

    /**
     * @var FailedRDMAssertionException
     */
    private $exception;

    public function setUp()
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
}
