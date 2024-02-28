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

namespace Addiks\RDMBundle\DataLoader\BlackMagic;

use Addiks\RDMBundle\Mapping\EntityMappingInterface;
use Composer\Autoload\ClassLoader;
use Webmozart\Assert\Assert;
use Addiks\RDMBundle\Mapping\MappingInterface;
use ErrorException;
use Doctrine\DBAL\Schema\Column;
use Addiks\RDMBundle\DataLoader\BlackMagic\BlackMagicDataLoader;

final class BlackMagicEntityCodeGenerator
{

    public function __construct(
        public readonly string $targetDirectory,
        private BlackMagicDataLoader $dataLoader,
        public readonly string $indenting = "    "
    ) {
    }
    
    public function processMapping(
        EntityMappingInterface $mapping,
        ClassLoader $loader
    ): string|null {
        /** @var array<array-key, Column> $columns */
        $columns = $mapping->collectDBALColumns();
        
        if (empty($columns)) {
            return null;
        }
        
        /** @var string $fullClassName */
        $fullClassName = $mapping->getEntityClassName();
        
        /** @var string|false $filePath */
        $filePath = $loader->findFile($fullClassName);
        
        if ($filePath === false) {
            return null;
        }

        /** @var array<string, bool> $walkedFiles */
        $walkedFiles = [$filePath => true];

        do {
            /** @var string $entityPHP */
            $entityPHP = file_get_contents($filePath);

            if (1 === preg_match("#\/\*\* \@addiks-original-file ([^\*]*) \*\/#is", $entityPHP, $matches)) {
                $filePath = trim($matches[1]);

                if (isset($walkedFiles[$filePath])) {
                    break; # Circular reference detected
                }
                $walkedFiles[$filePath] = true;
                continue;
            }
            break;
        } while (true);
        
        /** @var int $classStartPosition */
        $classStartPosition = self::findClassStartPosition($fullClassName, $entityPHP);
        
        /** @var array<string, Column> $writtenFieldNames */
        $writtenFieldNames = array();
        
        foreach ($columns as $column) {
            
            /** @var string $fieldName */
            $fieldName = $this->dataLoader->columnToFieldName($column);
            
            /** @var string $fieldPHP */
            $fieldPHP = sprintf(
                "\n%spublic $%s;\n",
                $this->indenting,
                $fieldName
            );
            
            if (isset($writtenFieldNames[$fieldName]) || str_contains($entityPHP, $fieldPHP)) {
                continue;
            }

            $writtenFieldNames[$fieldName] = $column;

            $entityPHP = sprintf(
                '%s%s%s',
                substr($entityPHP, 0, $classStartPosition),
                $fieldPHP,
                substr($entityPHP, $classStartPosition)
            );
        }
        
        $entityPHP .= sprintf(
            "\n\n/** @addiks-original-file %s */\n",
            $filePath
        );

        $targetFilePath = sprintf(
            '%s/%s.php',
            $this->targetDirectory,
            str_replace('\\', '_', $fullClassName)
        );
        
        if (!is_dir($this->targetDirectory)) {
            mkdir($this->targetDirectory, 0777, true);
        }
        
        file_put_contents($targetFilePath, $entityPHP);
        
        return $targetFilePath;
    }
    
    private static function findClassStartPosition(
        string $fullClassName, 
        string $entityPHP
    ): int {
        
        /** @var int|null|false $classStartPosition */
        $classStartPosition = null;
        
        /** @var int $position */
        $position = 0;
        
        /** @var array<int, string> $namespacePath */
        $namespacePath = explode("\\", $fullClassName);
        
        /** @var string $className */
        $className = array_pop($namespacePath);
        
        /** @var string $pattern */
        $pattern = sprintf(
            '/^(interface|trait|class)\s+%s\W*/is',
            $className
        );
        
        /** @var int $codeLength */
        $codeLength = strlen($entityPHP);
        
        do {
            if (preg_match($pattern, substr($entityPHP, $position, 128))) {
                $classStartPosition = strpos($entityPHP, '{', $position);
                
                if (is_int($classStartPosition)) {
                    return $classStartPosition + 1;

                } else {
                    $classStartPosition = null;
                }
            }
            
            self::skipIrrelevantCode($entityPHP, $position);
            
            $position++;
        } while ($position < $codeLength);
        
        throw new ErrorException(sprintf(
            'Could not find start position of class "%s" if file "%s"!',
            $fullClassName,
            $entityPHP
        ));
    }
    
    private static function skipIrrelevantCode(string $phpCode, int &$position): void
    {
        /** @var int $codeLength */
        $codeLength = strlen($phpCode);
        
        if (substr($phpCode, $position, 2) === '/*') {
            do {
                $position++;
            } while (substr($phpCode, $position, 2) !== '*/' && $position < $codeLength);
            
        } elseif (substr($phpCode, $position, 2) === '//') {
            do {
                $position++;
            } while ($phpCode[$position] !== "\n" && $position < $codeLength);
            
        } elseif (substr($phpCode, $position, 3) === '<<<') {
            $position += 3;
            $eofFlag = "";
            do {
                $eofFlag .= $phpCode[$position];
                $position++;
            } while ($phpCode[$position] !== "\n");
            
            do {
                $position++;
                $charsAtPosition = substr($phpCode, $position, strlen($eofFlag) + 1);
            } while ($charsAtPosition !== $eofFlag . ';' && $position < $codeLength);
            
        } elseif ($phpCode[$position] === '"') {
            do {
                $position++;
            } while ($phpCode[$position] !== '"' && $phpCode[$position - 1] !== '\\' && $position < $codeLength);

        } elseif ($phpCode[$position] === "'") {
            do {
                $position++;
            } while ($phpCode[$position] !== "'" && $phpCode[$position - 1] !== '\\' && $position < $codeLength);
        }
    }
    
}
