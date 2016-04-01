<?php
/**
 * The file contains class Accounts() extends DbImproved()
 */
namespace Katran\Model;

use Katran\Database\DbImproved;
use Katran\Helper;

/**
 * This class has methods for work with table 'accounts'
 *
 * @package Model
 * @see     DbImproved()
 */
class Accounts extends DbImproved
{
    // Available values for area
    const AREA_ADMIN = 'admin';
    const AREA_MEMBER = 'member';
    const AREA_VISITOR = 'visitor';

    // status
    const STATUS_ACTIVE  = 'active';
    const STATUS_BLOCKED = 'blocked';

    /**
     * Constructor set table
     *
     * @return  void
     * @version 2016-03-30
     * @access  public
     */
    public function __construct()
    {
        parent::setTable('accounts');
    }


    /**
     * [getAreaHash description]
     * @return hash|array
     */
    public static function getAreaHash()
    {
        static $cache = null;
        if ($cache === null) {
            $cache = [
                self::AREA_ADMIN => 'Админ',
                self::AREA_MEMBER => 'Клиент',
                self::AREA_VISITOR => 'Гость',
            ];
        }

        return $cache;
    }


    /**
     * [getArea description]
     * @param  string $key [description]
     * @return string
     */
    public static function getArea($key = '')
    {
        return self::getAreaHash()[$key];
    }


    /**
     * [getStatusHash description]
     * @return hash|array
     */
    public static function getStatusHash()
    {
        static $cache = null;
        if ($cache === null) {
            $cache = [
                self::STATUS_ACTIVE  => 'Активный',
                self::STATUS_BLOCKED => 'Заблокирован',
            ];
        }

        return $cache;
    }


    /**
     * [getStatus description]
     * @param  string $key [description]
     * @return string
     */
    public static function getStatus($key = '')
    {
        return self::getStatusHash()[$key];
    }
}