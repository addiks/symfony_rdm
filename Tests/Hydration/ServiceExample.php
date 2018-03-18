<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Tests\Hydration;

class ServiceExample
{

    public function __construct(string $lorem, int $ipsum)
    {
        $this->lorem = $lorem;
        $this->ipsum = $ipsum;
    }

    /**
     * @var string
     */
    public $lorem;

    /**
     * @var integer
     */
    public $ipsum;

    public function getLorem($dolor = "")
    {
        return $this->lorem . $dolor;
    }

}
