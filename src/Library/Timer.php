<?php
/**
 * The file contains class Timer()
 */
namespace Katran\Library;

use Katran\Helper;

/**
 * This class enables you to mark points and calculate the time difference
 * between them.  Memory consumption can also be displayed.
 * 
 * @version 2012-07-07
 * @package Libraries
 */
class Timer
{
    /**
     * Array of all markers
     * @var array
     */
    private static $marker = [];


    /**
     * Multiple calls to this function can be made so that several
     * execution points can be timed
     *
     * @param  string    $name of the marker
     * @return void
     */
    public static function mark($name)
    {
        self::$marker[$name] = microtime(true);
    }


    /**
     * Calculates the time difference between two marked points.
     *
     * @param    string    $point1  a particular marked point
     * @param    string    $point2  a particular marked point
     * @param    integer   $decimals   the number of decimal places
     * @return   mixed
     */
    public static function time($point1 = '', $point2 = '', $decimals = 8)
    {
        if (empty($point1))
            return 'not started';

        if ( empty(self::$marker[$point1]))
            return 'has\'n first point';

        if ( empty(self::$marker[$point2]) || empty($point2) )
            self::$marker[$point2] = microtime(true);

        // get points difference
        $time = number_format(self::$marker[$point2] - self::$marker[$point1], $decimals);

        // if debug = On save time into debug store
        if(Helper::_cfg('debug'))
            Helper::_debugStore('timer', [$point1, $point2, $time], true);

        return $time;
    }


    /**
     * This function returns the {memory_get_usage} pseudo-variable.
     *
     * @return    string
     */
    public static function memUse()
    {
        if (function_exists('memory_get_usage') && ($usage = memory_get_usage()) != '')
            $output = '<font class="memUse">'.number_format($usage).' bytes</font>';
        else
            $output = '<font class="memUse">Need older PHP</font>';

        return $output;
    }
}

/* End of file timer.php */