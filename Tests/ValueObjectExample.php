<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 *
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Tests;

class ValueObjectExample
{

    /**
     * @var string
     */
    public $scalarValue = "lorem ipsum";

    /**
     * @var string
     */
    public $lorem = "ipsum";

    /**
     * @var int
     */
    public $dolor = 123;

    /**
     * @var mixed|null
     */
    public $sit;

    /**
     * @var mixed|null
     */
    private $amet;

    public function __construct(string $scalarValue, string $lorem = "ipsum", int $dolor = 123)
    {
        $this->scalarValue = $scalarValue;
        $this->lorem = $lorem;
        $this->dolor = $dolor;
    }

    public static function createFromJson(string $json)
    {
        [$scalarValue, $lorem, $dolor] = json_decode($json, true);

        return new self($scalarValue, $lorem, $dolor);
    }

    public function serializeJson()
    {
        return [$this->scalarValue, $this->lorem, $this->dolor];
    }

    public function setAmet($amet)
    {
        $this->amet = $amet;
    }

    public function getAmet()
    {
        return $this->amet;
    }

}
