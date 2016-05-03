<?php
/**
 * The file contains class Flashbag()
 */
namespace Katran\Library;

/**
 * FlashBag flash message container
 */
class Flashbag
{
    /**
     * Flash messages' key
     */
    const SESSION_KEY = 'Katran\Library\Flashbag';


    /**
     * Flash messages' types
     */
    const TYPE_ERROR = 'error';
    const TYPE_INFO  = 'info';


    /**
     * [add description]
     * @param string $type    [description]
     * @param string $message [description]
     */
    public static function add($type, $message)
    {
        // init
        if(empty($_SESSION[self::SESSION_KEY]))
            $_SESSION[self::SESSION_KEY] = [];

        if (self::has($type)) {
            array_push($_SESSION[self::SESSION_KEY][$type], $message);
        }
        else {
            $_SESSION[self::SESSION_KEY][$type][] = $message;
        }
    }


    /**
     * [get description]
     * @param  string $type    [description]
     * @param  array  $default [description]
     * @return [type]          [description]
     */
    public static function get($type, $default = [])
    {
        if (!self::has($type))
            return $default;

        $return = $_SESSION[self::SESSION_KEY][$type];
        $_SESSION[self::SESSION_KEY][$type] = null;
        return $return;
    }


    /**
     * [has description]
     * @param  string  $type [description]
     * @return boolean       [description]
     */
    public static function has($type)
    {
        return !empty($_SESSION[self::SESSION_KEY][$type]);
    }


    /**
     * Function return all container
     * @return  array
     */
    public static function all()
    {
        if(empty($_SESSION[self::SESSION_KEY]))
            return [];

        $return = $_SESSION[self::SESSION_KEY];
        self::clear();
        return $return;
    }


    /**
     * Clear bug
     */
    public static function clear()
    {
        $_SESSION[self::SESSION_KEY] = null;
    }
}
