<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Exception;

use ErrorException;
use ReflectionClass;
use Addiks\RDMBundle\Exception\FailedRDMAssertionExceptionInterface;

final class FailedRDMAssertionException extends ErrorException implements FailedRDMAssertionExceptionInterface
{

    /**
     * @var string
     */
    private $type;

    /**
     * @var array<mixed>
     */
    private $parameters;

    public function __construct(string $message, string $type, array $parameters)
    {
        parent::__construct($message);

        $this->type = $type;
        $this->parameters = $parameters;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param mixed $expectedService
     * @param mixed $actualService
     */
    public static function expectedDifferentService(
        string $serviceId,
        ReflectionClass $classReflection,
        $expectedService,
        $actualService
    ): FailedRDMAssertionException {
        /** @var string $actualDescription */
        $actualDescription = self::generateDescriptionForValue($actualService);

        /** @var string $expectedDescription */
        $expectedDescription = self::generateDescriptionForValue($expectedService);

        return new self(
            sprintf(
                "Expected service '%s' (%s) on entity %s, was %s instead!",
                $serviceId,
                $expectedDescription,
                $classReflection->getName(),
                $actualDescription
            ),
            "EXPECTED_DIFFERENT_SERVICE",
            [$serviceId, $expectedService, $actualService]
        );
    }

    public static function expectedInstanceOf(
        string $expectedClassName,
        string $actualClassName,
        string $declarationOrigin
    ): FailedRDMAssertionException {
        return new self(
            sprintf(
                "Expected instance of %s instead of %s as specified in %s!",
                $expectedClassName,
                $actualClassName,
                $declarationOrigin
            ),
            "EXPECTED_INSTNACE_OF",
            [$expectedClassName, $actualClassName, $declarationOrigin]
        );
    }

    /**
     * @param mixed $actualValue
     */
    public static function expectedArray(
        $actualValue,
        string $declarationOrigin
    ): FailedRDMAssertionException {

        /** @var string $description */
        $description = self::generateDescriptionForValue($actualValue);

        return new self(
            sprintf(
                "Expected array, got %s as specified in %s!",
                $description,
                $declarationOrigin
            ),
            "EXPECTED_INSTNACE_OF",
            [$actualValue, $declarationOrigin]
        );
    }

    /**
     * @param mixed $value
     */
    private static function generateDescriptionForValue($value): string
    {
        /** @var string $description */
        $description = null;

        if (is_object($value)) {
            $description = get_class($value) . '#' . spl_object_hash($value);

        } else {
            $description = gettype($value);
        }

        return $description;
    }

}
