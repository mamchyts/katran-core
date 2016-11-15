<?php
/**
 * The file contains class Account() extends DbImproved()
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
class Account extends DbImproved
{
    // Available values for area
    const AREA_ADMIN = 'admin';
    const AREA_MEMBER = 'member';
    const AREA_VISITOR = 'visitor';

    // status
    const STATUS_ACTIVE  = 'active';
    const STATUS_BLOCKED = 'blocked';

    // DB table name
    const DB_TABLE = 'accounts';


    /**
     * [getAreaHash description]
     * @return hash|array
     */
    public static function getAreaHash()
    {
        static $cache = null;
        if ($cache === null) {
            $cache = [
                static::AREA_ADMIN => 'Админ',
                static::AREA_MEMBER => 'Клиент',
                static::AREA_VISITOR => 'Гость',
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
        return static::getAreaHash()[$key];
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
                static::STATUS_ACTIVE  => 'Активный',
                static::STATUS_BLOCKED => 'Заблокирован',
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
        return static::getStatusHash()[$key];
    }
}