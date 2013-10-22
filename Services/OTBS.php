<?php

namespace Ryba\OTBSBundle\Services;

use Ryba\OTBSBundle\Services\TBS\clsTinyButStrong;

/**
 * Construction of OpenTBS
 *
 * @author mr.mateuszryba@gmail.com
 */
class OTBS extends clsTinyButStrong
{
    public function __construct()
    {
        $this->Plugin(-1, 'clsOpenTBS');

        parent::__construct();
    }
}
