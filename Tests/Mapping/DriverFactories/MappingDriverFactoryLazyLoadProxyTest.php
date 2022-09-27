<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Tests\Mapping\DriverFactories;

use PHPUnit\Framework\TestCase;
use Addiks\RDMBundle\Mapping\DriverFactories\MappingDriverFactoryInterface;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Addiks\RDMBundle\Mapping\DriverFactories\MappingDriverFactoryLazyLoadProxy;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class MappingDriverFactoryLazyLoadProxyTest extends TestCase
{

    /**
     * @test
     */
    public function shouldCreatedMappingDriver()
    {
        /** @var MappingDriver $mappingDriver */
        $mappingDriver = $this->createMock(MappingDriver::class);

        /** @var ContainerInterface $container */
        $container = $this->createMock(ContainerInterface::class);

        /** @var MappingDriverFactoryInterface $service */
        $service = $this->createMock(MappingDriverFactoryInterface::class);

        $container->method('get')->will($this->returnValueMap([
            ['some_service', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $service],
        ]));

        $container->method('has')->will($this->returnValueMap([
            ['some_service', true],
        ]));

        /** @var MappingDriverInterface $expectedMappingDriver */
        $expectedMappingDriver = $this->createMock(MappingDriverInterface::class);

        $service->expects($this->once())->method('createRDMMappingDriver')->willReturn(
            $expectedMappingDriver
        );

        $driverFactory = new MappingDriverFactoryLazyLoadProxy($container, "some_service");

        /** @var MappingDriverInterface $actualMappingDriver */
        $actualMappingDriver = $driverFactory->createRDMMappingDriver($mappingDriver);

        $this->assertSame($expectedMappingDriver, $actualMappingDriver);
    }

}
