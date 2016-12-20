<?php
/**
 * The file contains class Filter()
 */
namespace Katran\Library;

use Katran\Helper;
use Katran\Request;

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
    private $formData = [];


    /**
     * Map for $where param
     * var array
     */
    private $creterias = [];


    /**
     * Constructor
     *
     * @param    Request  $request
     * @param    string   $fieldTitle
     * @return   void
     * @access   public
     */
    public function __construct(Request $request, $fieldTitle = 'filter')
    {
        $this->formData = $request->getArray($fieldTitle);
    }


    /**
     * Function set creteria
     *
     * @param    array    $creteria
     * @return   void
     * @access   public
     */
    public function setCreteria($creteria = [])
    {
        $this->creterias = $creteria;
    }


    /**
     * Function rerurn array for use in SQL query
     *
     * @return   array
     * @access   public
     */
    public function getWhere()
    {
        $sql = '1';
        $values = [];

        // if form was not submit - return empty array
        if (!isset($this->formData['set'])){
            return [$sql, $values];
        }

        // delete 
        unset($this->formData['set']);

        $where = [];
        foreach ($this->creterias as $field=>$creteria) {

            // call internal method if exist
            $method = 'get'.$creteria;
            if (method_exists($this, $method)) {
                $where[] = call_user_func_array([$this, $method], [$field]);
            }
            elseif (in_array($creteria, array('=', '<', '>', '<=', '>=', '!='))){
                if (isset($this->formData[$field]) && (trim($this->formData[$field]) != '')){
                    $where[] = [$field.' '.$creteria.' ?', [$this->formData[$field]]];
                }
            }
        }

        foreach ($where as $w) {
            if(sizeof($w) === 2){
                $sql .= ' AND '.$w[0];
                $values = array_merge($values, $w[1]);
            }
        }

        return [$sql, $values];
    }


    /**
     * Function parse $this->formData and find field $key.
     * Return string for use in SQL query
     *
     * @param    string    $key
     * @return   array
     * @access   private
     */
    private function getDateRange($key = '')
    {
        return $this->_getRange($key, false, false);
    }


    /**
     * Function parse $this->formData and find field $key.
     * Return string for use in SQL query
     *
     * @param    string    $key
     * @return   array
     * @access   private
     */
    private function getIntRange($key = '')
    {
        return $this->_getRange($key, true, false);
    }


    /**
     * Function parse $this->formData and find field $key.
     * Return string for use in SQL query
     *
     * @param    string    $key
     * @return   array
     * @access   private
     */
    private function getFloatRange($key = '')
    {
        return $this->_getRange($key, false, true);
    }


    /**
     * Function parse $this->formData and find field $key.
     * Return string for use in SQL query
     * 
     * @param  string  $key      [description]
     * @param  boolean $intval   [description]
     * @param  boolean $floatval [description]
     * @return array
     * @access private
     */
    private function _getRange($key = '', $intval = false, $floatval = false)
    {
        if (isset($this->formData[$key.'_from']) && (trim($this->formData[$key.'_from']) !== '')) {
            $from = trim($this->formData[$key.'_from']);
            if ($intval) {
                $from = intval($from);
            }
            elseif ($floatval) {
                $from = floatval($from);
            }
        }
        if (isset($this->formData[$key.'_to']) && (trim($this->formData[$key.'_to']) !== '')) {
            $to = trim($this->formData[$key.'_to']);
            if ($intval) {
                $to = intval($to);
            }
            elseif ($floatval) {
                $to = floatval($to);
            }
        }

        $res = [];
        if (isset($from) && isset($to)) {
            $res = [$key.' BETWEEN ? AND ?', [$from, $to]];
        }
        elseif (isset($from)) {
            $res = [$key.' >= ? ', [$from]];
        }
        elseif (isset($to)) {
            $res = [$key.' <= ? ', [$to]];
        }

        return $res;
    }


    /**
     * Function parse $this->formData and find field $key.
     * Return string for use in SQL query
     *
     * @param    string    $key
     * @return   array
     * @access   private
     */
    private function getIn($key = '')
    {
        $res = [];
        if (!empty($this->formData[$key])){
            $rows = $this->formData[$key];
            if (!is_array($rows)) {
                $rows = [$rows];
            }

            // black magic
            $res = [$key.' IN ('.implode(',', array_fill(0, sizeof($rows), '?')).')', $rows];
        }

        return $res;
    }


    /**
     * Function parse $this->formData and find field $key.
     * Return string for use in SQL query
     *
     * @param    string    $key
     * @return   array
     * @access   private
     */
    private function getLike($key = '')
    {
        $res = [];
        if (!empty($this->formData[$key])){
            $res = [$key.' LIKE ?', ['%'.$this->formData[$key].'%']];
        }

        return $res;
    }


    /**
     * Function return formData
     *
     * @return   array
     * @access   public
     */
    public function getFilterData()
    {
        return $this->formData;
    }
}
