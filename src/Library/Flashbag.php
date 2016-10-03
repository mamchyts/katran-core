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
     * Flash key
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
     * @param mixed  $message [description]
     */
    public static function add($type, $message)
    {
        // init
        if (!self::has($type))
            $_SESSION[self::SESSION_KEY][$type] = [];

        if (is_scalar($message)) {
            array_push($_SESSION[self::SESSION_KEY][$type], $message);
        }
        elseif (is_array($message)) {
            foreach ($message as $m) {
                self::add($type, $m);
            }
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
        if (empty($_SESSION[self::SESSION_KEY]))
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
