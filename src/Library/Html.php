<?php
/**
 * The file contains class Html()
 */
namespace Katran\Library;

/**
 * This class have some method for create html elements
 *
 * @package Libraries
 */
class Html
{
    /**
     * This method create content for <select></select>
     *
     * @param     array    $vars
     * @param     mixed    $select
     * @param     boolean  $empty_first
     * @return    string
     * @access    public
     * @version   2012-02-10
     */
    public static function createSelect($vars = array(), $select = null, $empty_first = false)
    {
        $html = '';
        $selected = '';

        if($empty_first && is_null($select))
            $html .= '<option selected="selected" value="">&nbsp;</option>';
        elseif($empty_first)
            $html .= '<option value="">&nbsp;</option>';

        foreach($vars as $key=>$title)
            $html .= '<option value="'.$key.'" '.((($select == $key) && !is_null($select))?'selected="selected"':'').'>'.$title.'</option>';

        return $html;
    }
}
