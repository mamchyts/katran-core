<?php
/**
 * The file contains class Db()
 */
namespace Katran\Database;

use Katran\Library\Timer;
use Katran\Helper;


/**
 * This class create connection to MySQL.
 * Class has some method for work with database.
 *
 * @package	Database
 * @use     Timer()
 */
class Db
{

    /**
     * Table name
     * @var string
     */
    private $table = '';

    /**
     * PDO connection object
     * @var resource
     */
    private $pdo = FALSE;

    /**
     * Result of query
     * @var mixed
     */
    private $result = FALSE;

    /**
     * Full information abount table's fields
     * @var array
     */
    private $fullFields = [];

    /**
     * Array of connections
     * @var string
     */
    private static $pdoArray = [];

    /**
     * Tables array
     * @var string
     */
    public static $tables = [];

    /**
     * Names of columns
     * @var array
     */
    public $fields = [];


    /**
     * Constructor set connect with MySQL
     *
     * @return  void
     * @param   array|hash $config
     * @access	private
     */
    private function __construct($config = [])
    {
        try {
            $dsn = 'mysql:dbname='.$config['name'].';host='.$config['host'].';charset='.$config['charset'];
            $this->pdo = new \PDO($dsn, $config['user'], $config['pass']);
        }
        catch (\PDOException $e) {
            trigger_error(sprintf(Helper::_msg('mysql'), $e->getMessage()));
        }
    }


    /**
     * Function return object for work with table $dbModel
     *
     * @param   mixed      $dbModel
     * @param   array|hash $config
     * @return  object
     * @access  public
     */
    public static function getModel($dbModel = '', $config = [])
    {
        if(empty($config))
            $config = Helper::_cfg('db');

        $pdoHash = $config['host'].'::'.$config['name'].'::'.$config['port'].'::'.$config['user'];

        if(isset(self::$pdoArray[$pdoHash])){
            $pdo = self::$pdoArray[$pdoHash];
        }
        else{
            // call constructor
            $db = new Db($config);

            $pdo = $db->pdo;
            self::$pdoArray[$pdoHash] = $pdo;
            self::$tables[$pdoHash] = $db->getFields('SHOW TABLES;');
        }

        // if get already DbImproved child
        if($dbModel instanceof DbImproved){
            $obj = $dbModel;
        }
        // if we already have such table
        elseif(in_array($dbModel, self::$tables[$pdoHash])){
            $obj = new DbImproved($dbModel);
        }
        else{
            trigger_error(sprintf(Helper::_msg('mysql'), 'Not real table name'));
        }

        $obj->pdo = $pdo;
        $obj->fullFields = $obj->getRows('SHOW FIELDS FROM `'.$obj->getTable().'`');

        foreach($obj->fullFields as $f)
            $obj->fields[] = $f['Field'];

        return $obj;
    }


    /**
     * Function send some request to MySQL
     * 
     * @param   string  $sql         [description]
     * @param   array   $whereValues [description]
     * @param   string  $type        [description]
     * @return  mixed
     * @access  public
     */
    public function query($sql = '', $whereValues = [], $type = '')
    {
        if(trim($sql) === '')
            trigger_error(sprintf(Helper::_msg('mysql'), 'Empty request to SQL server'));

        // try execute SQL query
        Timer::mark('sql_start');
        $this->result = $this->pdo->prepare($sql);
        $this->result->execute($whereValues);
        Timer::mark('sql_finish');

        // if error
        if($this->result->errorCode() !== '00000')
            trigger_error(sprintf(Helper::_msg('mysql'), implode('::', $this->result->errorInfo())));

        // if debug On save request into debug store
        if(Helper::_cfg('debug')){
            $data = [];
            $data['time']    = Timer::time('sql_start', 'sql_finish');
            $data['request'] = str_replace(['?'], $whereValues, $sql);
            Helper::_debugStore('sql_log', $data, 1);
        }

        // insert() must return ID
        if($type === 'insert'){
            return $this->pdo->lastInsertId();
        }
    }


    /**
     * [getField description]
     * @param  string $sql         [description]
     * @param  array  $whereValues [description]
     * @return mixed
     */
    public function getField($sql = '', $whereValues = [])
    {
        $this->query($sql, $whereValues);
        $row = $this->result->fetch(\PDO::FETCH_NUM);
        return $row[0];
    }


    /**
     * [getFields description]
     * @param  string  $sql         [description]
     * @param  array   $whereValues [description]
     * @param  boolean $notEmpty    [description]
     * @return array
     */
    public function getFields($sql = '', $whereValues = [], $notEmpty = TRUE)
    {
        $this->query($sql, $whereValues);
        $rows = $this->result->fetchAll(\PDO::FETCH_COLUMN, 0);
        return (empty($rows) && $notEmpty)?[0]:$rows;
    }


    /**
     * [getRow description]
     * @param  string  $sql         [description]
     * @param  array   $whereValues [description]
     * @param  boolean $assoc       [description]
     * @return hash|array
     */
    public function getRow($sql = '', $whereValues = [], $assoc = true)
    {
        $this->query($sql, $whereValues);
        return $this->result->fetch(($assoc)?\PDO::FETCH_ASSOC:\PDO::FETCH_NUM);
    }


    /**
     * [getRows description]
     * @param  string $sql
     * @param  array  $whereValues
     * @return array
     */
    public function getRows($sql = '', $whereValues = [])
    {
        $this->query($sql, $whereValues);
        return $this->result->fetchAll(\PDO::FETCH_ASSOC);
    }


    /**
     * Function return database table
     *
     * @return  string
     * @access  public
     */
    public function getTable()
    {
        return $this->table;
    }


    /**
     * Function set table parameter
     *
     * @param   string $table
     * @return  string
     * @access  public
     */
    public function setTable($table = '')
    {
        return $this->table = $table;
    }


    /**
     * Function return database connection
     *
     * @return  mixed
     * @access  public
     */
    public function getConnection()
    {
        return $this->pdo;
    }
}