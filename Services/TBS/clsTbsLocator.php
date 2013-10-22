<?php

namespace DevDash\OTBSBundle\Services\TBS;

// Render flags
define('TBS_NOTHING', 0);
define('TBS_OUTPUT', 1);
define('TBS_EXIT', 2);

// Plug-ins actions
define('TBS_INSTALL', -1);
define('TBS_ISINSTALLED', -3);

/**
 * @version 1.8.1
 * @date 2013-08-31
 * @see     http://www.tinybutstrong.com/plugins.php
 * @author  Skrol29 http://www.tinybutstrong.com/onlyyou.html
 * @license LGPL
 */
class clsTbsLocator
{
    public $PosBeg = false;
    public $PosEnd = false;
    public $Enlarged = false;
    public $FullName = false;
    public $SubName = '';
    public $SubOk = false;
    public $SubLst = array();
    public $SubNbr = 0;
    public $PrmLst = array();
    public $PrmIfNbr = false;
    public $MagnetId = false;
    public $BlockFound = false;
    public $FirstMerge = true;
    public $ConvProtect = true;
    public $ConvStr = true;
    public $ConvMode = 1; // Normal
    public $ConvBr = true;
}
