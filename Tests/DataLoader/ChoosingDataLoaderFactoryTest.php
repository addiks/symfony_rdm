<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Tests\DataLoader;

use PHPUnit\Framework\TestCase;
use Addiks\RDMBundle\DataLoader\ChoosingDataLoaderFactory;
use Addiks\RDMBundle\Tests\Hydration\ServiceExample;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Addiks\RDMBundle\DataLoader\DataLoaderInterface;

final class ChoosingDataLoaderFactoryTest extends TestCase
{

    /**
     * @test
     */
    public function shouldCreateCorrectLoaderWithParameter()
    {
        /** @var ContainerInterface $container */
        $container = $this->createMock(ContainerInterface::class);

        $serviceFoo = $this->createMock(DataLoaderInterface::class);
        $serviceBar = $this->createMock(DataLoaderInterface::class);
        $serviceDefault = $this->createMock(DataLoaderInterface::class);

        $container->method("hasParameter")->willReturn(true);
        $container->method("getParameter")->willReturn("bar");

        $container->method("has")->will($this->returnValueMap([
            ['foo_service', true],
            ['bar_service', true],
            ['the_default_service', true]
        ]));

        $container->method("get")->will($this->returnValueMap([
            ['foo_service', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $serviceFoo],
            ['bar_service', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $serviceBar],
            ['the_default_service', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $serviceDefault]
        ]));

        $dataLoader = new ChoosingDataLoaderFactory(
            $container,
            [
                'foo' => 'foo_service',
                'bar' => 'bar_service'
            ],
            "some_parameter_name",
            "the_default_service"
        );

        /** @var DataLoaderInterface $actualDataLoader */
        $actualDataLoader = $dataLoader->createDataLoader();

        $this->assertSame($serviceBar, $actualDataLoader);
    }

}
