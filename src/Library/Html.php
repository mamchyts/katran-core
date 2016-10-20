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
     * @param     boolean  $emptyFirst
     * @return    string
     * @access    public
     */
    public static function createSelect($vars = array(), $select = null, $emptyFirst = false)
    {
        $html = '';
        $selected = '';

        if ($emptyFirst && is_null($select)) {
            $html .= '<option selected="selected" value="">&nbsp;</option>';
        }
        elseif ($emptyFirst) {
            $html .= '<option value="">&nbsp;</option>';
        }

        foreach ($vars as $key=>$title) {
            $html .= '<option value="'.$key.'" '.((($select == $key) && !is_null($select))?'selected="selected"':'').'>'.$title.'</option>';
        }

        return $html;
    }
}
