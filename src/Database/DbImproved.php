<?php
/**
 * The file contains class DbImproved() extends Db()
 */
namespace Katran\Database;

use Katran\Helper;
use Katran\Library\Pager;
use Katran\Library\Sorter;

/**
 * This class has methid for work with MySQL.
 * 
 * @package Database
 * @see     Db()
 */
class DbImproved extends Db
{
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
        foreach ($data as $key=>$d) {
            if (in_array($key, $this->fields)) {
                $sql[] = '`'.$key.'` = ?';

                // fix for save array value
                if (!is_scalar($d)) {
                    $d = json_encode($d);
                }

                $bindParams[] = $d;
            }
        }

        // add WHERE $id
        $bindParams[] = $id;

        $sql = 'UPDATE `'.static::DB_TABLE.'` SET '.implode(', ', $sql).' WHERE `'.$updateBy.'` = ?';
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
        foreach ($data as $key=>$d) {
            if (in_array($key, $this->fields)) {
                $fields[] = $key;

                // fix for save array value
                if (!is_scalar($d))
                    $d = json_encode($d);

                $sql[] = '?';
                $bindParams[] = $d;
            }
        }

        $fieldStr = implode('`, `', $fields);
        $valStr = implode(', ', $sql);

        if (empty($table)) {
            $table = static::DB_TABLE;
        }

        $sql = 'INSERT INTO `'.$table.'` (`'.$fieldStr.'`) VALUES ('.$valStr.')';
        return $this->query($sql, $bindParams, Db::DB_QUERY_TYPE_INSERT);
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
        foreach ($data as $key=>$d) {
            if (in_array($key, $this->fields)) {
                $sql[] = '`'.$key.'` = ?';
                $bindParams[] = $d;
            }
        }

        $sql = 'UPDATE `'.static::DB_TABLE.'` SET '.implode(', ', $sql).' WHERE '.$where['sql'];
        return $this->query($sql, array_merge($bindParams, $where['value']));
    }


    /**
     * Function parse array $where for use in sql request 
     *
     * @param   array|hash $where
     * @param   array|hash $map
     * @return  string
     * @access  protected
     */
    protected function _parseWhere($where = [])
    {
        $res = [
            'sql' => [],
            'value' => [],
        ];

        // compile to $res
        if (sizeof($where) === 2) {
            $res['sql'] = $where[0];
            $res['value'] = $where[1];
        }
        elseif (sizeof($where) === 1) {
            $res['sql'] = $where[0];
        }

        if (empty($res['sql']))
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
        $sql = 'SELECT * FROM `'.static::DB_TABLE.'` WHERE `'.$field.'` = ?';
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
        if (is_string($sorter) && ($sorter !== '')) {
            $order = ' ORDER BY '.$sorter;
        }
        elseif (is_array($sorter) && sizeof($sorter)) {
            $keys = array_keys($sorter);
            $sorterValues = array_values($sorter);
            $order = ' ORDER BY '.$keys[0].' '.$sorterValues[0];
        }

        //parse pager
        if (is_integer($pager)) {
            $limit = ' LIMIT 0, '.$pager;
        }
        elseif (is_array($pager) && (count($pager) > 0)) {
            $limit = ' LIMIT '.((isset($pager[1]))?$pager[0]:0).', '.((isset($pager[1]))?$pager[1]:$pager[0]);
        }

        // create and send sql request
        $sql = 'SELECT * FROM `'.static::DB_TABLE.'` WHERE '.$where['sql'].$order.$limit;
        return $this->getRows($sql, $where['value']);
    }


    /**
     * Function return one row from database by $where, $pager and $sorter
     *
     * @param  array   $where
     * @param  mixed   $pager
     * @param  mixed   $sorter
     * @return array
     * @access public
     * @see    self::findBy()
     */
    public function findByOne($where = [], $pager = [], $sorter = [])
    {
        $rows = $this->findBy($where, $pager, $sorter);
        return sizeof($rows)?array_shift($rows):[];
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
        $sql = 'SELECT COUNT(id) FROM `'.static::DB_TABLE.'` WHERE '.$where['sql'];
        $count = $this->getField($sql, $where['value']);
        if (intval($count) === 0) {
            return [];
        }

        // parse sorter variable
        if (is_string($sorter) && !empty($sorter)) {
            $order = ' ORDER BY '.$sorter;
        }
        elseif ($sorter instanceof Sorter) {
            $order = ' ORDER BY '.$sorter->getOrder($this->fields);
        }

        //parse pager
        if (is_integer($pager) || is_string($pager)) {
            $limit = ' LIMIT '.$pager;
        }
        elseif (is_array($pager) && (sizeof($pager) === 2)) {
            $limit = ' LIMIT '.intval($pager[0]).', '.intval($pager[0]);
        }
        elseif ($pager instanceof Pager) {
            $pager->init($count);
            $limit = ' LIMIT '.$pager->getLimit();
        }

        // create and send sql request
        $sql = 'SELECT * FROM `'.static::DB_TABLE.'` WHERE '.$where['sql'].$order.$limit;
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
        $sql = 'SELECT COUNT(id) FROM `'.static::DB_TABLE.'`';
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

        $sql = 'SELECT COUNT(id) FROM `'.static::DB_TABLE.'` WHERE '.$where['sql'];
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
        $sql = 'DELETE FROM `'.static::DB_TABLE.'` WHERE `'.$field.'` = ?';
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

        $sql = 'DELETE FROM `'.static::DB_TABLE.'` WHERE '.$where['sql'];
        return intval($this->query($sql,$where['value']));
    }


    /**
     * Function return hash
     *
     * @param    string   $sql
     * @return   array
     * @access   public
     */
    public function getHash($sql = false, $id = 'id', $title = 'title')
    {
        // create and send sql request
        if ($sql === false) {
            $sql = 'SELECT `'.$id.'`, `'.$title.'` FROM `'.static::DB_TABLE.'` ORDER BY `'.$title.'` ASC';
        }

        $hash = [];
        foreach ($this->getRows($sql) as $r) {
            $hash[$r[$id]] = $r[$title];
        }

        return $hash;
    }
}
