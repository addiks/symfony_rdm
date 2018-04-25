<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Mapping\DriverFactories;

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\Driver\FileLocator;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Addiks\RDMBundle\Mapping\DriverFactories\MappingDriverFactoryInterface;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Addiks\RDMBundle\Mapping\Drivers\MappingXmlDriver;
use Symfony\Component\HttpKernel\KernelInterface;

final class MappingXMLDriverFactory implements MappingDriverFactoryInterface
{

    /**
     * @var string
     */
    private $schemaFilePath;

    /**
     * @var KernelInterface
     */
    private $kernel;

    public function __construct(
        KernelInterface $kernel,
        string $schemaFilePath
    ) {
        $this->kernel = $kernel;
        $this->schemaFilePath = $schemaFilePath;
    }

    public function createRDMMappingDriver(
        MappingDriver $mappingDriver
    ): ?MappingDriverInterface {
        /** @var ?MappingDriverInterface $rdmMappingDriver */
        $rdmMappingDriver = null;

        if ($mappingDriver instanceof XmlDriver) {
            /** @var FileLocator $fileLocator */
            $fileLocator = $mappingDriver->getLocator();

            $rdmMappingDriver = new MappingXmlDriver(
                $fileLocator,
                $this->kernel,
                $this->schemaFilePath
            );
        }

        return $rdmMappingDriver;
    }

}
