<?php
/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 *
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Symfony;

use Addiks\RDMBundle\DataLoader\BlackMagic\BlackMagicEntityCodeGenerator;
use Doctrine\Persistence\Mapping\Driver\MappingDriver as DoctrineMappingDriver;
use Composer\Autoload\ClassLoader;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface as RDMMappingDriver;
use Addiks\RDMBundle\Mapping\EntityMappingInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Addiks\RDMBundle\AddiksRDMBundle;
use Addiks\RDMBundle\DataLoader\BlackMagic\BlackMagicDataLoader;

final class RDMBlackMagickCacheWarmer implements CacheWarmerInterface
{

    public function __construct(
        private DoctrineMappingDriver $doctrineMappingDriver,
        private RDMMappingDriver $rdmMappingDriver,
        private BlackMagicDataLoader $dataLoader,
        public readonly string $folderNameInCache = 'symfony_rdm_entities'
    ) {
    }
    
    public function warmUp($cacheDirectory): array
    {
        /** @var mixed $entitiesFolder */
        $entitiesFolder = sprintf(
            '%s/%s',
            $cacheDirectory,
            $this->folderNameInCache
        );
        
        $codeGenerator = new BlackMagicEntityCodeGenerator(
            $entitiesFolder,
            $this->dataLoader
        );
        
        /** @var array<int, array{0:string, 1:string> $classmap */
        $classmap = array();
        
        foreach ($this->doctrineMappingDriver->getAllClassNames() as $entityClass) {
            
            /** @var EntityMappingInterface|null $mapping */
            $mapping = $this->rdmMappingDriver->loadRDMMetadataForClass($entityClass);
            
            if (is_object($mapping)) {
                /** @var string|null $processedEntityFilePath */
                $processedEntityFilePath = $codeGenerator->processMapping(
                    $mapping, 
                    AddiksRDMBundle::classLoader()
                );
                
                if (is_string($processedEntityFilePath)) {
                    $classmap[] = [$entityClass, $processedEntityFilePath];
                }
            }
        }
        
        if (!is_dir($entitiesFolder)) {
            mkdir($entitiesFolder, 0777, true);
        }
        
        file_put_contents(
            $entitiesFolder . '/classmap',
            implode("\n", array_map(
                fn ($l) => $l[0] . ':' . $l[1],
                $classmap
            ))
        );
        
        return []; # Files to preload
    }
    
    public function isOptional(): bool
    {
        return false;
    }

}
