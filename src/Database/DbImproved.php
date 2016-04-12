<?php
/**
 * The file contains class DbImproved() extends Db()
 */
namespace Katran\Database;

use Katran\Helper;

/**
 * This class has methid for work with MySQL.
 * 
 * @package Database
 * @see     Db()
 */
class DbImproved extends Db
{
    /**
     * Constructor set table
     *
     * @return  void
     * @version 2016-03-30
     * @access  public
     */
    public function __construct($table = null)
    {
        if(!empty($table))
            parent::setTable($table);
    }


    /**
     * Function update one row in database
     *
     * @param    array   $data
     * @param    integer $id
     * @param    string  $updateBy
     * @return   boolen
     * @access  public
     */
    public function update($data = [], $id = 0, $updateBy = 'id')
    {
        $sql = [];
        $bindParams = [];

        // escape input data
        foreach($data as $key=>$d){
            if(in_array($key, $this->fields)){
                $sql[] = '`'.$key.'` = ?';
                $bindParams[] = $d;
            }
        }

        // add WHERE $id
        $bindParams[] = $id;

        $sql = 'UPDATE `'.$this->getTable().'` SET '.implode(', ', $sql).' WHERE `'.$updateBy.'` = ?';
        return $this->query($sql, $bindParams);
    }


    /**
     * Function update one row in database
     *
     * @param    array|hash   $data
     * @param    string       $table
     * @return   boolen
     * @access   public
     */
    public function insert($data = [], $table = '')
    {
        $sql = [];
        $bindParams = [];

        // escape input data
        foreach($data as $key=>$d){
            if(in_array($key, $this->fields)){
                $fields[] = $key;

                $sql[] = '?';
                $bindParams[] = $d;
            }
        }

        $fieldStr = implode('`, `', $fields);
        $valStr = implode(', ', $sql);

        if (empty($table))
            $table = $this->getTable();

        $sql = 'INSERT INTO `'.$table.'` (`'.$fieldStr.'`) VALUES ('.$valStr.')';
        return $this->query($sql, $bindParams, 'insert');
    }


    /**
     * Function update some rows in database 
     *
     * @param    array   $data
     * @param    array   $where
     * @return   boolen
     * @access  public
     */
    public function updateBy($data = [], $where = [])
    {
        // compile $where
        $where = $this->_parseWhere($where);

        $sql = [];
        $bindParams = [];
        foreach($data as $key=>$d){
            if(in_array($key, $this->fields)){
                $sql[] = '`'.$key.'` = ?';
                $bindParams[] = $d;
            }
        }

        $sql = 'UPDATE `'.$this->getTable().'` SET '.implode(', ', $sql).' WHERE '.$where['sql'];
        return $this->query($sql, array_merge($bindParams, $where['value']));
    }


    /**
     * Function parse array $where for use in sql request 
     *
     * @param   array|hash $where
     * @param   array|hash $map
     * @return  string
     * @access  public
     */
    public function _parseWhere($where = [], $map = [])
    {
        $res = [
            'sql' => [],
            'value' => [],
        ];

        // compile to $res
        foreach($where as $w){
            if(sizeof($w) === 3){
                if(isset($map[$w[0]]))
                    $w[0] = $map[$w[0]];

                // for LIKE add %
                if(strtolower($w[1]) === 'like')
                    $w[2] = '%'.$w[2].'%';

                $res['sql'][] = $w[0].' '.$w[1].' ?';
                $res['value'][] = $w[2];
            }
        }

        $res['sql'] = implode(' AND ', $res['sql']);
        if(empty($res['sql']))
            $res['sql'] = 1;

        return $res;
    }


    /**
     * Function return one row from database by unique field
     *
     * @param   mixed   $id
     * @param   string  $field
     * @return  array
     * @access  public
     */
    public function find($id = 0, $field = 'id')
    {
        $sql = 'SELECT * FROM `'.$this->getTable().'` WHERE `'.$field.'` = ?';
        return $this->getRow($sql, [$id]);
    }


    /**
     * Function return all rows from database by $where, $pager and $sorter
     *
     * @param    array   $where
     * @param    mixed   $pager
     * @param    mixed   $sorter
     * @return   array
     * @access  public
     */
    public function findBy($where = [], $pager = [], $sorter = [])
    {
        $limit = '';
        $order = '';

        // compile $where
        $where = $this->_parseWhere($where);

        // parse sorter
        if(is_string($sorter) && ($sorter !== ''))
            $order = ' ORDER BY '.$sorter;
        elseif(is_array($sorter) && sizeof($sorter)){
            $keys = array_keys($sorter);
            $sorterValues = array_values($sorter);
            $order = ' ORDER BY '.$keys[0].' '.$sorterValues[0];
        }

        //parse pager
        if(is_integer($pager))
            $limit = ' LIMIT 0, '.$pager;
        elseif(is_array($pager) && (count($pager) > 0)){
            $limit = ' LIMIT '.((isset($pager[1]))?$pager[0]:0).', '.((isset($pager[1]))?$pager[1]:$pager[0]);
        }

        // create and send sql request
        $sql = 'SELECT * FROM `'.$this->getTable().'` WHERE '.$where['sql'].$order.$limit;
        return $this->getRows($sql, $where['value']);
    }


    /**
     * Function return all rows from database by $where, $pager and $sorter
     *
     * @param    array   $where
     * @param    mixed   $pager
     * @param    mixed   $sorter
     * @return   array
     * @access  public
     */
    public function findByFull($where = [], &$pager = [], &$sorter = [])
    {
        $limit = '';
        $order = '';

        // compile $where
        $where = $this->_parseWhere($where);

        // Get count of possibly rows if count = 0  - return []
        $sql = 'SELECT COUNT(id) FROM `'.$this->getTable().'` WHERE '.$where['sql'];
        $count = $this->getField($sql, $where['value']);
        if(intval($count) === 0)
            return [];

        // parse sorter
        if(is_string($sorter) && ($sorter !== ''))
            $order = ' ORDER BY '.$sorter;
        elseif(is_object($sorter)){
            // Work with class Sorter.
            $order = ' ORDER BY '.$sorter->getOrder($this->fields);
        }

        //parse pager
        if(is_integer($pager))
            $limit = ' LIMIT '.$pager;
        elseif(is_array($pager) && (count($pager) > 0)){
            if(isset($pager['from']))
                $from = intval($pager['from']);
            else
                $from = 0;

            $limit = ' LIMIT '.$from.', '.intval($pager['to']-$from);
        }
        elseif(is_object($pager)){
            // Work with class Pager.
            $pager->init($count);
            $limit = ' LIMIT '.$pager->getLimit();
        }

        // create and send sql request
        $sql = 'SELECT * FROM `'.$this->getTable().'` WHERE '.$where['sql'].$order.$limit;
        return $this->getRows($sql, $where['value']);
    }


    /**
     * Function return count of all rows into a table
     *
     * @return   int
     * @access  public
     */
    public function count()
    {
        $sql = 'SELECT COUNT(id) FROM `'.$this->getTable().'`';
        return intval($this->getField($sql));
    }


    /**
     * Function return count of rows by where
     *
     * @param    array   $where
     * @return   int
     * @access   public
     */
    public function countBy($where = [])
    {
        // compile $where
        $where = $this->_parseWhere($where);

        $sql = 'SELECT COUNT(id) FROM `'.$this->getTable().'` WHERE '.$where['sql'];
        return intval($this->getField($sql, $where['value']));
    }


    /**
     * Function delete one row from table by id
     *
     * @param   integer    $id
     * @return  integer
     * @access  public
     */
    public function delete($id = 0, $field = 'id')
    {
        $sql = 'DELETE FROM `'.$this->getTable().'` WHERE `'.$field.'` = ?';
        return intval($this->query($sql, [$id]));
    }


    /**
     * Function delete rows from table by where
     *
     * @param   array   $where
     * @return  integer
     * @access  public
     */
    public function deleteBy($where = [])
    {
        // compile $where
        $where = $this->_parseWhere($where);

        $sql = 'DELETE FROM `'.$this->getTable().'` WHERE '.$where['sql'];
        return intval($this->query($sql,$where['value']));
    }


    /**
     * Function return hash
     *
     * @param    string   $sql
     * @return   array
     * @access   public
     */
    public function getHash($sql = FALSE, $id = 'id', $title = 'title')
    {
        // create and send sql request
        if($sql === FALSE)
            $sql = 'SELECT `'.$id.'`, `'.$title.'` FROM `'.$this->getTable().'` ORDER BY `'.$title.'` ASC';

        $hash = [];
        foreach ($this->getRows($sql) as $r) {
            $hash[$r[$id]] = $r[$title];
        }

        return $hash;
    }
}
