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
     * @return   string
     * @access   private
     */
    private function getDateRange($key = '')
    {
        if (isset($this->formData[$key.'_from']) && trim($this->formData[$key.'_from'])) {
            $from = date('Y-m-d', strtotime($this->formData[$key.'_from']));
        }
        if (isset($this->formData[$key.'_to']) && trim($this->formData[$key.'_to'])) {
            $to = date('Y-m-d', strtotime($this->formData[$key.'_to']));
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
     * @return   string
     * @access   private
     */
    private function getIntRange($key = '')
    {
        if (isset($this->formData[$key.'_from']) && (trim($this->formData[$key.'_from']) !== '')) {
            $from = $this->formData[$key.'_from'];
        }
        if (isset($this->formData[$key.'_to']) && (trim($this->formData[$key.'_to']) !== '')) {
            $to = $this->formData[$key.'_to'];
        }

        $res = [];
        if (isset($from) && isset($to)) {
            $res = [$key.' BETWEEN ? AND ?', [intval($from), intval($to)]];
        }
        elseif (isset($from)) {
            $res = [$key.' >= ? ', [intval($from)]];
        }
        elseif (isset($to)) {
            $res = [$key.' <= ? ', [intval($to)]];
        }

        return $res;
    }


    /**
     * Function parse $this->formData and find field $key.
     * Return string for use in SQL query
     *
     * @version  2015-08-29
     * @param    string    $key
     * @return   string
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
            $res = [$key.' IN ('.implode(',', array_fill(0, sizeof($vals), '?')).')', $vals];
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
