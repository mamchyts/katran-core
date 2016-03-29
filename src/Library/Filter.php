<?php
/**
 * The file contains class Filter()
 */
namespace Katran\Library;

use Katran\Helper;

/**
 * This class parse $_GET data for create $where array
 * witch will use in SQL query
 *
 * @package Libraries
 */
class Filter
{
    /**
     * Filter data from HTML form
     * var array
     */
    private $form_data = [];


    /**
     * Map for $where param
     * var array
     */
    private $creteria = [];


    /**
     * Constructor
     *
     * @version  2013-01-22
     * @param    object   $request    array of $_POST and $_GET data
     * @param    string   $field_title
     * @return   object
     * @access   public
     */
    public function __construct($request, $field_title = 'filter')
    {
        $this->form_data = $request->getArray($field_title);
    }


    /**
     * Function set creteria
     *
     * @version  2013-01-22
     * @param    array    $creteria
     * @return   void
     * @access   public
     */
    public function setCreteria($creteria = [])
    {
        $this->creteria = $creteria;
    }


    /**
     * Function rerurn array for use in SQL query
     *
     * @version  2013-01-22
     * @return   array
     * @access   public
     */
    public function getWhere()
    {
        // if form was not submit - return empty array
        if(!isset($this->form_data['set']))
            return [];

        // delete 
        unset($this->form_data['set']);

        $where = [];
        foreach ($this->creteria as $key=>$c) {
            $c = strtolower($c);

            if($c === 'range'){
                $sql = $this->getIntRange($key);
                if($sql !== false)
                    $where[$key] = $sql;
            }
            elseif($c === 'in'){
                $sql = $this->getIn($key);
                if($sql !== false)
                    $where[$key] = $sql;
            }
            elseif($c === 'date_range'){
                $sql = $this->getDateRange($key);
                if($sql !== false)
                    $where[$key] = $sql;
            }
            elseif(($c === 'like') && !empty($this->form_data[$key])){
                $where[$key] = 'LIKE "%'.$this->escape($this->form_data[$key]).'%"';
            }
            elseif(in_array($c, array('=', '<', '>', '<=', '>=', '!='))){
                if(isset($this->form_data[$key]) && (trim($this->form_data[$key]) != '')){
                    if(is_numeric($this->form_data[$key]))
                        $where[$key] = $c.' '.$this->escape($this->form_data[$key]);
                    else
                        $where[$key] = $c.' "'.$this->escape($this->form_data[$key]).'"';
                }
            }
        }

        return $where;
    }


    /**
     * Function parse $this->form_data and find field $key.
     * Return string for use in SQL query
     *
     * @version  2013-01-22
     * @param    string    $key
     * @return   string
     * @access   private
     */
    private function getDateRange($key = '')
    {
        if(isset($this->form_data[$key.'_from']) && trim($this->form_data[$key.'_from']))
            $from = Helper::_date($this->form_data[$key.'_from'], 'Y-m-d', false);
        if(isset($this->form_data[$key.'_to']) && trim($this->form_data[$key.'_to']))
            $to = Helper::_date($this->form_data[$key.'_to'], 'Y-m-d', false);

        if(isset($from) && isset($to))
            $res = 'BETWEEN "'.$from.'" AND "'.$to.'"';
        elseif(isset($from))
            $res = ' >= "'.$from.'"';
        elseif(isset($to))
            $res = ' <= "'.$to.'"';
        else
            $res = false;

        return $res;
    }


    /**
     * Function parse $this->form_data and find field $key.
     * Return string for use in SQL query
     *
     * @version  2013-01-22
     * @param    string    $key
     * @return   string
     * @access   private
     */
    private function getIntRange($key = '')
    {
        if(isset($this->form_data[$key.'_from']) && (trim($this->form_data[$key.'_from']) !== ''))
            $from = $this->form_data[$key.'_from'];
        if(isset($this->form_data[$key.'_to']) && (trim($this->form_data[$key.'_to']) !== ''))
            $to = $this->form_data[$key.'_to'];

        if(isset($from) && isset($to))
            $res = 'BETWEEN '.intval($from).' AND '.intval($to).'';
        elseif(isset($from))
            $res = ' >= '.intval($from).'';
        elseif(isset($to))
            $res = ' <= '.intval($to).'';
        else
            $res = false;

        return $res;
    }


    /**
     * Function parse $this->form_data and find field $key.
     * Return string for use in SQL query
     *
     * @version  2015-08-29
     * @param    string    $key
     * @return   string
     * @access   private
     */
    private function getIn($key = '')
    {
        if(!empty($this->form_data[$key])){
            $rows = $this->form_data[$key];
            if(!is_array($rows))
                $rows = [$rows];

            $vals = [];
            foreach ($rows as $r) {
                $vals[] = '"'.$this->escape($r).'"';
            }
            $res = 'IN ('.implode(',', $vals).')';
        }
        else
            $res = false;

        return $res;
    }


    /**
     * Function return form_data
     *
     * @version  2013-01-22
     * @return   array
     * @access   public
     */
    public function getFilterData()
    {
        return $this->form_data;
    }
}
