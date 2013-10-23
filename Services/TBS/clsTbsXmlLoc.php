<?php

namespace Ryba\OTBSBundle\Services\TBS;

/**
 * Constants to drive the plugin.
 */
define('OPENTBS_PLUGIN','clsOpenTBS');
define('OPENTBS_DOWNLOAD',1);   // download (default) = TBS_OUTPUT
define('OPENTBS_NOHEADER',4);   // option to use with DOWNLOAD: no header is sent
define('OPENTBS_FILE',8);       // output to file   = TBSZIP_FILE
define('OPENTBS_DEBUG_XML',16); // display the result of the current subfile
define('OPENTBS_STRING',32);    // output to string = TBSZIP_STRING
define('OPENTBS_DEBUG_AVOIDAUTOFIELDS',64); // avoit auto field merging during the Show() method
define('OPENTBS_INFO','clsOpenTBS.Info');       // command to display the archive info
define('OPENTBS_RESET','clsOpenTBS.Reset');      // command to reset the changes in the current archive
define('OPENTBS_ADDFILE','clsOpenTBS.AddFile');    // command to add a new file in the archive
define('OPENTBS_DELETEFILE','clsOpenTBS.DeleteFile'); // command to delete a file in the archive
define('OPENTBS_REPLACEFILE','clsOpenTBS.ReplaceFile'); // command to replace a file in the archive
define('OPENTBS_FILEEXISTS','clsOpenTBS.FileExists');
define('OPENTBS_CHART','clsOpenTBS.Chart'); // command to delete a file in the archive
define('OPENTBS_DEFAULT','');   // Charset
define('OPENTBS_ALREADY_XML',false);
define('OPENTBS_ALREADY_UTF8','already_utf8');
define('OPENTBS_DEBUG_XML_SHOW','clsOpenTBS.DebugXmlShow');
define('OPENTBS_DEBUG_XML_CURRENT','clsOpenTBS.DebugXmlCurrent');
define('OPENTBS_DEBUG_INFO','clsOpenTBS.DebugInfo');
define('OPENTBS_DEBUG_CHART_LIST','clsOpenTBS.DebugInfo'); // deprecated
define('OPENTBS_FORCE_DOCTYPE','clsOpenTBS.ForceDocType');
define('OPENTBS_DELETE_ELEMENTS','clsOpenTBS.DeleteElements');
define('OPENTBS_SELECT_SHEET','clsOpenTBS.SelectSheet');
define('OPENTBS_SELECT_SLIDE','clsOpenTBS.SelectSlide');
define('OPENTBS_SELECT_MAIN','clsOpenTBS.SelectMain');
define('OPENTBS_DISPLAY_SHEETS','clsOpenTBS.DisplaySheets');
define('OPENTBS_DELETE_SHEETS','clsOpenTBS.DeleteSheets');
define('OPENTBS_DELETE_COMMENTS','clsOpenTBS.DeleteComments');
define('OPENTBS_MERGE_SPECIAL_ITEMS','clsOpenTBS.MergeSpecialItems');
define('OPENTBS_CHANGE_PICTURE','clsOpenTBS.ChangePicture');
define('OPENTBS_COUNT_SLIDES','clsOpenTBS.CountSlides');
define('OPENTBS_SEARCH_IN_SLIDES','clsOpenTBS.SearchInSlides');
define('OPENTBS_DISPLAY_SLIDES','clsOpenTBS.DisplaySlides');
define('OPENTBS_DELETE_SLIDES','clsOpenTBS.DeleteSlides');
define('OPENTBS_FIRST',1);
define('OPENTBS_GO',2);
define('OPENTBS_ALL',4);

/**
 * clsTbsXmlLoc
 * Wrapper to search and replace in XML entities.
 * The object represents only the opening tag until method FindEndTag() is called.
 * Then is represents the complete entity.
 *
 * @version 1.8.1
 * @date 2013-08-31
 * @see     http://www.tinybutstrong.com/plugins.php
 * @author  Skrol29 http://www.tinybutstrong.com/onlyyou.html
 * @license LGPL
 */
class clsTbsXmlLoc
{
    public $PosBeg;
    public $PosEnd;
    public $SelfClosing;
    public $Txt;
    public $Name = '';

    public $pST_PosEnd = false; // start tag: position of the end
    public $pST_Src = false;    // start tag: source
    public $pET_PosBeg = false; // end tag: position of the begining

    public $Parent = false; // parent object

    // Create an instance with the given parameters
    public function __construct(&$Txt, $Name, $PosBeg, $SelfClosing = null, $Parent=false)
    {
        $this->PosEnd = strpos($Txt, '>', $PosBeg);
        if ($this->PosEnd===false) $this->PosEnd = strlen($Txt)-1; // should no happen but avoid errors

        $this->Txt = &$Txt;
        $this->Name = $Name;
        $this->PosBeg = $PosBeg;
        $this->pST_PosEnd = $this->PosEnd;
        $this->SelfClosing = $SelfClosing;
        $this->Parent = $Parent;
    }

    // Return an array of (val_pos, val_len, very_sart, very_len) of the attribute. Return false if the attribute is not found.
    // Positions are relative to $this->PosBeg.
    // This method is lazy because it assumes the attribute is separated by a space and its value is deleimited by double-quote.
    public function _GetAttValPos($Att)
    {
        if ($this->pST_Src===false) $this->pST_Src = substr($this->Txt, $this->PosBeg, $this->pST_PosEnd - $this->PosBeg + 1 );
        $a = ' '.$Att.'="';
        $p0 = strpos($this->pST_Src, $a);
        if ($p0!==false) {
            $p1 = $p0 + strlen($a);
            $p2 = strpos($this->pST_Src, '"', $p1);
            if ($p2!==false) return array($p1, $p2-$p1, $p0, $p2-$p0+1);
        }

        return false;
    }

    public function _ApplyDiffFromStart($Diff)
    {
        $this->pST_PosEnd += $Diff;
        $this->pST_Src = false;
        if ($this->pET_PosBeg!==false) $this->pET_PosBeg += $Diff;
        $this->PosEnd += $Diff;
    }

    // Return the outer len of the locator.
    public function GetLen()
    {
        return $this->PosEnd - $this->PosBeg + 1;
    }

    // Return the outer source of the locator.
    public function GetSrc()
    {
        return substr($this->Txt, $this->PosBeg, $this->GetLen() );
    }

    // Replace the source of the locator in the TXT contents.
    // Update the locator's ending position.
    // Too complicated to update other information, given that it can be deleted.
    public function ReplaceSrc($new)
    {
        $len = $this->GetLen(); // avoid PHP error : Strict Standards: Only variables should be passed by reference
        $this->Txt = substr_replace($this->Txt, $new, $this->PosBeg, $len);
        $diff = strlen($new) - $len;
        $this->PosEnd += $diff;
        $this->pST_Src = false;
        if ($new==='') {
            $this->pST_PosBeg = false;
            $this->pST_PosEnd = false;
        } else {
            $this->pST_PosEnd += $diff;
        }
    }

    // Return the start of the inner content, or false if it's a self-closing tag
    // Return false if SelfClosing.
    public function GetInnerStart()
    {
        return ($this->pST_PosEnd===false) ? false : $this->pST_PosEnd + 1;
    }

    // Return the length of the inner content, or false if it's a self-closing tag
    // Assume FindEndTag() is previsouly called.
    // Return false if SelfClosing.
    public function GetInnerLen()
    {
        return ($this->pET_PosBeg===false) ? false : $this->pET_PosBeg - $this->pST_PosEnd - 1;
    }

    // Return the length of the inner content, or false if it's a self-closing tag
    // Assume FindEndTag() is previsouly called.
    // Return false if SelfClosing.
    public function GetInnerSrc()
    {
        return ($this->pET_PosBeg===false) ? false : substr($this->Txt, $this->pST_PosEnd + 1, $this->pET_PosBeg - $this->pST_PosEnd - 1 );
    }

    // Replace the inner source of the locator in the TXT contents. Update the locator's positions.
    // Assume FindEndTag() is previsouly called.
    public function ReplaceInnerSrc($new)
    {
        $len = $this->GetInnerLen();
        if ($len===false) return false;
        $this->Txt = substr_replace($this->Txt, $new, $this->pST_PosEnd + 1, $len);
        $this->PosEnd += strlen($new) - $len;
        $this->pET_PosBeg += strlen($new) - $len;
    }

    // Update the parent object, if any.
    public function UpdateParent($Cascading=false)
    {
        if ($this->Parent) {
            $this->Parent->ReplaceSrc($this->Txt);
            if ($Cascading) $this->Parent->UpdateParent($Cascading);
        }
    }

    // Get an attribut's value. Or false if the attribute is not found.
    // It's a lazy way because the attribute is searched with the patern {attribute="value" }
    public function GetAttLazy($Att)
    {
        $z = $this->_GetAttValPos($Att);
        if ($z===false) return false;
        return substr($this->pST_Src, $z[0], $z[1]);
    }

    public function ReplaceAtt($Att, $Value, $AddIfMissing = false)
    {
        $Value = ''.$Value;

        $z = $this->_GetAttValPos($Att);
        if ($z===false) {
            if ($AddIfMissing) {
                // Add the attribute
                $Value = ' '.$Att.'="'.$Value.'"';
                $z = array($this->pST_PosEnd - $this->PosBeg, 0);
            } else {
                return false;
            }
        }

        $this->Txt = substr_replace($this->Txt, $Value, $this->PosBeg + $z[0], $z[1]);

        // update info
        $this->_ApplyDiffFromStart(strlen($Value) - $z[1]);

        return true;

    }

    public function DeleteAtt($Att)
    {
        $z = $this->_GetAttValPos($Att);
        if ($z===false) return false;
        $this->Txt = substr_replace($this->Txt, '', $this->PosBeg + $z[2], $z[3]);
        $this->_ApplyDiffFromStart( - $z[3]);

        return true;
    }

    // Find the name of the element
    public function FindName()
    {
        if ($this->Name==='') {
            $p = $this->PosBeg;
            do {
                $p++;
                $z = $this->Txt[$p];
            } while ( ($z!==' ') && ($z!=="\r") && ($z!=="\n") && ($z!=='>') && ($z!=='/') );
            $this->Name = substr($this->Txt, $this->PosBeg + 1, $p - $this->PosBeg - 1);
        }

        return $this->Name;
    }

    // Find the ending tag of the object
    // Use $Encaps=true if the element can be self encapsulated (like <div>).
    // Return true if the end is funf
    public function FindEndTag($Encaps=false)
    {
        if (is_null($this->SelfClosing)) {
            $pe = $this->PosEnd;
            $SelfClosing = (substr($this->Txt, $pe-1, 1)=='/');
            if (!$SelfClosing) {
                if ($Encaps) {
                    $loc = clsTinyButStrong::f_Xml_FindTag($this->Txt , $this->FindName(), null, $pe, true, -1, false, false);
                    if ($loc===false) return false;
                    $this->pET_PosBeg = $loc->PosBeg;
                    $this->PosEnd = $loc->PosEnd;
                } else {
                    $pe = clsTinyButStrong::f_Xml_FindTagStart($this->Txt, $this->FindName(), false, $pe, true , true);
                    if ($pe===false) return false;
                    $this->pET_PosBeg = $pe;
                    $pe = strpos($this->Txt, '>', $pe);
                    if ($pe===false) return false;
                    $this->PosEnd = $pe;
                }
            }
            $this->SelfClosing = $SelfClosing;
        }

        return true;
    }

    /**
     * Search a start tag of an element in the TXT contents, and return an object if it is found.
     * Instead of a TXT content, it can be an object of the class. Thus, the object is linked to a copy
     *  of the source of the parent element. The parent element can receive the changes of the object using method UpdateParent().
     */
    public static function FindStartTag(&$TxtOrObj, $Tag, $PosBeg, $Forward=true)
    {
        if (is_object($TxtOrObj)) {
            $TxtOrObj->FindEndTag();
            $Txt = $TxtOrObj->GetSrc();
            if ($Txt===false) return false;
            $Parent = &$TxtOrObj;
        } else {
            $Txt = &$TxtOrObj;
            $Parent = false;
        }

        $PosBeg = clsTinyButStrong::f_Xml_FindTagStart($Txt, $Tag, true , $PosBeg, $Forward, true);
        if ($PosBeg===false) return false;
        return new clsTbsXmlLoc($Txt, $Tag, $PosBeg, null, $Parent);

    }

    // Search a start tag by the prefix of the element
    public static function FindStartTagByPrefix(&$Txt, $TagPrefix, $PosBeg, $Forward=true)
    {
        $x = '<'.$TagPrefix;
        $xl = strlen($x);

        if ($Forward) {
            $PosBeg = strpos($Txt, $x, $PosBeg);
        } else {
            $PosBeg = strrpos(substr($Txt, 0, $PosBeg+2), $x);
        }
        if ($PosBeg===false) return false;

        // Read the actual tag name
        $Tag = $TagPrefix;
        $p = $PosBeg + $xl;
        do {
            $z = substr($Txt,$p,1);
            if ( ($z!==' ') && ($z!=="\r") && ($z!=="\n") && ($z!=='>') && ($z!=='/') ) {
                $Tag .= $z;
                $p++;
            } else {
                $p = false;
            }
        } while ($p!==false);

        return new clsTbsXmlLoc($Txt, $Tag, $PosBeg);

    }

    // Search an element in the TXT contents, and return an object if it is found.
    public static function FindElement(&$TxtOrObj, $Tag, $PosBeg, $Forward=true)
    {
        $XmlLoc = clsTbsXmlLoc::FindStartTag($TxtOrObj, $Tag, $PosBeg, $Forward);
        if ($XmlLoc===false) return false;

        $XmlLoc->FindEndTag();

        return $XmlLoc;

    }

    // Search an element in the TXT contents which has the asked attribute, and return an object if it is found.
    // Note that the element found has an unknwown name until FindEndTag() is called.
    // The given attribute can be with or without a specific value. Example: 'visible' or 'visible="1"'
    public static function FindStartTagHavingAtt(&$Txt, $Att, $PosBeg, $Forward=true)
    {
        $p = $PosBeg - (($Forward) ? 1 : -1);
        $x = (strpos($Att, '=')===false) ? (' '.$Att.'="') : (' '.$Att); // get the item more precise if not yet done
        $search = true;

        do {
            if ($Forward) {
                $p = strpos($Txt, $x, $p+1);
            } else {
                $p = strrpos(substr($Txt, 0, $p+1), $x);
            }
            if ($p===false) return false;
            do {
              $p = $p - 1;
              if ($p<0) return false;
              $z = $Txt[$p];
            } while ( ($z!=='<') && ($z!=='>') );
            if ($z==='<') $search = false;
        } while ($search);

        return new clsTbsXmlLoc($Txt, '', $p);

    }

    public static function FindElementHavingAtt(&$Txt, $Att, $PosBeg, $Forward=true)
    {
        $XmlLoc = clsTbsXmlLoc::FindStartTagHavingAtt($Txt, $Att, $PosBeg, $Forward);
        if ($XmlLoc===false) return false;

        $XmlLoc->FindEndTag();

        return $XmlLoc;

    }

}
