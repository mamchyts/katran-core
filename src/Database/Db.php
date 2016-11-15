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
    // list of query types
    const DB_QUERY_TYPE_INSERT = 'insert';

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
     * @param   DbImproved $dbModel
     * @param   array|hash $config
     * @return  object
     * @access  public
     */
    public static function getModel(DbImproved $dbModel, $config = [])
    {
        if (empty($config)) {
            $config = Helper::_cfg('db');
        }

        $pdoHash = $config['host'].'::'.$config['name'].'::'.$config['port'].'::'.$config['user'];

        if (isset(self::$pdoArray[$pdoHash])){
            $pdo = self::$pdoArray[$pdoHash];
        }
        else {
            // call constructor
            $db = new Db($config);

            // set charset
            $db->query('SET NAMES '.$config['charset'].';');

            $pdo = $db->pdo;
            self::$pdoArray[$pdoHash] = $pdo;
        }

        // if incorrect $dbModel
        if (!($dbModel instanceof DbImproved)){
            trigger_error(sprintf(Helper::_msg('mysql'), 'Not real table name'));
        }

        $dbModel->pdo = $pdo;
        $dbModel->fullFields = $dbModel->getRows('SHOW FIELDS FROM `'.$dbModel::DB_TABLE.'`');

        foreach($dbModel->fullFields as $f) {
            $dbModel->fields[] = $f['Field'];
        }

        return $dbModel;
    }


    /**
     * Function send some request to MySQL
     * 
     * @param   string  $sql         [description]
     * @param   array   $whereValues [description]
     * @param   string  $type        [description]
     * @return  mixed
     * @access  protected
     */
    protected function query($sql = '', $whereValues = [], $type = '')
    {
        if (empty($sql)) {
            trigger_error(sprintf(Helper::_msg('mysql'), 'Empty request to SQL server'));
        }

        // try execute SQL query
        Timer::mark('sql_start');
        $this->result = $this->pdo->prepare($sql);
        $this->result->execute($whereValues);
        Timer::mark('sql_finish');

        // if error
        if ($this->result->errorCode() !== '00000') {
            trigger_error(sprintf(Helper::_msg('mysql'), implode('::', $this->result->errorInfo())));
        }

        // if debug On save request into debug store
        if (Helper::_cfg('debug')){
            $data = [];
            $data['time']    = Timer::time('sql_start', 'sql_finish');
            $data['request'] = str_replace(['?'], $whereValues, $sql);
            Helper::_debugStore('sql_log', $data, 1);
        }

        // insert() must return ID
        if ($type === self::DB_QUERY_TYPE_INSERT) {
            return $this->pdo->lastInsertId();
        }
    }


    /**
     * [getField description]
     * @param  string $sql         [description]
     * @param  array  $whereValues [description]
     * @return mixed
     */
    protected function getField($sql = '', $whereValues = [])
    {
        $this->query($sql, $whereValues);
        $row = $this->result->fetch(\PDO::FETCH_NUM);
        return !empty($row[0])?$row[0]:null;
    }


    /**
     * [getFields description]
     * @param  string  $sql         [description]
     * @param  array   $whereValues [description]
     * @param  boolean $notEmpty    [description]
     * @return array
     */
    protected function getFields($sql = '', $whereValues = [], $notEmpty = true)
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
    protected function getRow($sql = '', $whereValues = [], $assoc = true)
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
    protected function getRows($sql = '', $whereValues = [])
    {
        $this->query($sql, $whereValues);
        return $this->result->fetchAll(\PDO::FETCH_ASSOC);
    }


    /**
     * Function return database connection
     *
     * @return  mixed
     * @access  private
     */
    private function getConnection()
    {
        return $this->pdo;
    }


    /**
     * Function escape string
     *
     * @param    string   $var
     * @return   string
     * @access   public
     */
    public function escape($var = '', $isTrim = true)
    {
        if ($isTrim) {
            $var = trim($var);
        }

        return $this->getConnection()->quote($var);
    }
}