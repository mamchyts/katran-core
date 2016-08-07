<?php
/**
 * The file contains class Helper()
 */
namespace Katran;

use Katran\Library\Mailer;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Global helper class
 */
class Helper
{
    /**
     * [$storage description]
     * @var array
     */
    private static $storage = [];


    /**
     * Function return part of string
     *
     * @param     string   $str
     * @param     integer  $start
     * @param     integer  $length
     * @return    string
     * @access    public
     * @version   2013-04-05
     */
    public static function _substr($str = '', $start = 0, $length = 0, $encoding = 'utf-8')
    {
        if(function_exists('mb_substr'))
            return mb_substr($str, $start, $length, $encoding);
        else
            return substr($str, $start, $length);
    }


    /**
     * [_mkdir description]
     * @param  string  $dir  [description]
     * @param  integer $mode [description]
     * @return void
     */
    public static function _mkdir($dir = '', $mode = 0777)
    {
        mkdir($dir, $mode, true);
        self::_chmod($dir, $mode);
    }


    /**
     * [_chmod description]
     * @param  string  $dirOrFile  [description]
     * @param  integer $mode       [description]
     * @return void
     */
    public static function _chmod($dirOrFile = '', $mode = 0777)
    {
        chmod($dirOrFile, $mode);
    }


    /**
     * Convert function
     * 
     * Attempts to convert a string from $fromEncoding to $toEncoding encoding
     *
     * @access  public
     * @param   string  $str  income string
     * @param   string  $fromEncoding
     * @param   string  $toEncoding
     * @return  string
     */
    public static function _encoding($str, $fromEncoding, $toEncoding = 'UTF-8')
    {
        if (function_exists('mb_convert_encoding'))
            $str = mb_convert_encoding($str, $toEncoding, $fromEncoding);
        elseif (function_exists('iconv'))
            $str = iconv($fromEncoding, $toEncoding, $str);

        return $str;
    }


    /**
     * Function return time
     *
     * @param     string      $format
     * @param     int|string  $time
     * @param     boolean     $addCorrect
     * @return    void
     */
    public static function _date($format = 'Y-m-d H:i:s', $time = FALSE, $addCorrect = false)
    {
        if(!$time)
            $time = time();
        elseif(!is_numeric($time))
            $time = strtotime($time);

        if(($format === FALSE) || (trim($format) === ''))
            $format = 'Y-m-d H:i:s';

        if($addCorrect)
            $time += self::_cfg('date_correct');

        $date = date($format, $time);
        return $date;
    }


    /**
     * [errorHandler description]
     * @param  integer $errno   [description]
     * @param  string  $errstr  [description]
     * @param  string  $errfile [description]
     * @param  integer $errline [description]
     * @return [type]           [description]
     */
    public static function _errorHandler($errno = 0, $errstr = '', $errfile = '', $errline = 0)
    {
        // need for @
        if(error_reporting() === 0)
            return false;

        // if console error
        if(php_sapi_name() === 'cli'){
            $str = 'Error code: '.$errno."\n".
                    'Message: '.$errstr."\n".
                    'File: '.$errfile."\n".
                    'Line: '.$errline."\n";

            //save error text into log file
            self::_log($str, self::_cfg('error_log'));
            echo $str;
            exit(0);
        }

        //save error text into log file
        self::_log('Error code: '.$errno."\n".
                'Message: '.$errstr."\n".
                'File: '.$errfile."\n".
                'Line: '.$errline."\n".
                'Url: '.($_SERVER['SCRIPT_NAME'].'?'.$_SERVER['QUERY_STRING'])
             , self::_cfg('error_log'));

        $history = debug_backtrace();

        $strHistory = '<table border=0 cellspadding="0" class="debugTable">';
        $strHistory .= '<tr><td colspan=4>Url: '.($_SERVER['SCRIPT_NAME'].'?'.$_SERVER['QUERY_STRING']).'</td></tr>';
        $strHistory .= '<tr><td colspan=4>&nbsp;</td></tr>';
        $strHistory .= '<tr><td colspan=4 style="height:40px;">'.$errno.':&nbsp;&nbsp; Error text: "'.$errstr.'"&nbsp;&nbsp;=> '.$errfile.' : line '.$errline.'</td></tr>';
        for($i = count($history)-1; $i >= 0; $i--){
            $strHistory .= '<tr>';
            $strHistory .= '<td>['.(count($history)-$i-1).'] =></td>';
            $strHistory .= '<td>'.((isset($history[$i]["file"]))?$history[$i]["file"]:'&nbsp;').'</td>';
            $strHistory .= '<td style="padding-left:15px;">function &nbsp; '.((isset($history[$i]["function"]))?$history[$i]["function"]:'&nbsp;').'()</td>';
            $strHistory .= '<td style="padding-left:10px;">: line '.((isset($history[$i]["line"]))?$history[$i]["line"]:'&nbsp;').'</td>';
            $strHistory .= '</tr>';
        }
        $strHistory .= '<tr><td colspan=4>&nbsp;</td></tr>';
        $strHistory .= '<tr><td colspan=4>GET: <pre>'.print_r($_GET,1).'</pre></td></tr>';
        $strHistory .= '<tr><td colspan=4>&nbsp;</td></tr>';
        $strHistory .= '<tr><td colspan=4>POST: <pre>'.print_r($_POST,1).'</pre></td></tr>';
        $strHistory .= '<tr><td colspan=4>&nbsp;</td></tr>';
        $strHistory .= '<tr><td colspan=4>SERVER: <pre>'.print_r($_SERVER,1).'</pre></td></tr>';
        $strHistory .= '</table>';

        if(self::_cfg('send_bug_to_email') && (strpos(self::_cfg('send_bug_to_email'), '@') !== false)){
            $mailer = new Mailer();
            $mailer->send(self::_cfg('send_bug_to_email'), '_error()', $strHistory);
        }

        $area = 'admin';
        $h = $history[sizeof($history)-1];
        if(!empty($h['object']) && ($h['object'] instanceof \Katran\Application)){
            if($h['object']->getArea())
                $area = $h['object']->getArea();
        }

        // echo error info with history
        $app = new Application();
        $app->setArea($area);
        $app->setLayout('./'.ucfirst($app->getArea()).'/View/layout.php');
        $app->setContainerVar('_aliasPage', 'error.php');

        // set error text into 'error layout' and display them
        header('HTTP/1.1 500 Internal Server Error');
        $app->getAliasContent($strHistory);
        exit(0);
    }


    /**
     * Function display content of given variable
     *
     * @param     mixed
     * @param     boolen
     * @return    string
     */
    public static function _d($var, $die = FALSE)
    {
        $error =  '<hr/><pre>';
        $error .= print_r($var, TRUE);
        $error .= '</pre><hr/>';
        echo $error; 
        if($die)
            die();
    }


    /**
     * [setCfg description]
     * @param array $files [description]
     */
    public static function _setCfg($files = [])
    {
        self::$storage['_cfg'] = [];
        foreach ($files as $f) {
            self::$storage['_cfg'] = array_merge(self::$storage['_cfg'], require_once $f);
        }
    }


    /**
     * Function return config param
     *
     * @return    mixed
     */
    public static function _cfg()
    {
        $tmp = self::$storage['_cfg'];
        foreach (func_get_args() as $key) {
            if(isset($tmp[$key])){
                $tmp = $tmp[$key];
            }
        }

        return $tmp;
    }


    /**
     * [_setMsg description]
     * @param array $files [description]
     */
    public static function _setMsg($files = [])
    {
        self::$storage['_msg'] = [];
        foreach ($files as $f) {
            self::$storage['_msg'] = array_merge(self::$storage['_msg'], require_once $f);
        }
    }


    /**
     * Function return error massage
     *
     * @param   string  $var
     * @param   string  $lang
     * @return  string
     */
    public static function _msg($var, $lang = FALSE)
    {
        if($lang === FALSE)
            $lang = self::_cfg('lang');

        if(isset(self::$storage['_msg'][$var][$lang]))
            return self::$storage['_msg'][$var][$lang];
        else
            return '';
    }


    /**
     * Function save debug variables into some store
     *
     * @param     string    $name
     * @param     mixed     $var
     * @param     boolean   $addInArray
     * @return    mixed
     * @access    public
     */
    public static function _debugStore($name, $var = FALSE, $addInArray = FALSE)
    {
        if(!isset($debugStore))
            static $debugStore = [];

        if($var !== FALSE){
            if($addInArray)
                $debugStore[$name][] = $var;
            else
                $debugStore[$name] = $var;
            return 0;
        }

        elseif(isset($debugStore[$name]))
            return $debugStore[$name];
        else
            return [];
    }


    /**
     * Function save string into log file
     *
     * @param     string    $string
     * @param     string    $file
     * @return    void
     */
    public static function _log($string = '', $file = FALSE)
    {
        if(trim($string) === '')
            return FALSE;

        // path to file
        $path = empty($file)?self::_cfg('log'):$file;

        // create dir if need 
        if(!file_exists(dirname($path)))
            self::_mkdir(dirname($path));

        // add records to the log
        $log = new Logger('default');
        $log->pushHandler(new StreamHandler($path, null, null, 0777));
        $log->addInfo($string);
    }


    /**
     * Function set/get menu item in store
     *
     * @param   string|array   $menu
     * @param   boolean        $return
     * @param   boolean        $returnTitle
     * @return  void
     * @access  public
     */
    public static function _menu($menu = '', $return = FALSE, $returnTitle = false)
    {
        if(!isset($store))
            static $store = null;

        if($return){
            $res = true;

            if(is_array($store)){
                $res = $store[1];
                $tmp = $store[0];
            }
            else{
                $tmp = $store;
            }

            // need for show title in admin layout
            if($returnTitle)
                return ($res === true)?'':$res;

            return (($tmp === $menu) || strstr($tmp, $menu.'-') !== false);
        }
        else{
            $store = $menu;
        }
    }


    /**
     * 
     * Return a URL/filesystem-friendly version of string
     * 
     * @return string
     * @param $text string
     * @see https://github.com/bcosca/fatfree/blob/master/lib/web.php
     **/
    public static function _slug($text)
    {
        return trim(strtolower(preg_replace('/([^\pL\pN])+/u','-',
            trim(strtr(str_replace('\'','',$text),
            array(
                'Ǎ'=>'A','А'=>'A','Ā'=>'A','Ă'=>'A','Ą'=>'A','Å'=>'A',
                'Ǻ'=>'A','Ä'=>'Ae','Á'=>'A','À'=>'A','Ã'=>'A','Â'=>'A',
                'Æ'=>'AE','Ǽ'=>'AE','Б'=>'B','Ç'=>'C','Ć'=>'C','Ĉ'=>'C',
                'Č'=>'C','Ċ'=>'C','Ц'=>'C','Ч'=>'Ch','Ð'=>'Dj','Đ'=>'Dj',
                'Ď'=>'Dj','Д'=>'Dj','É'=>'E','Ę'=>'E','Ё'=>'E','Ė'=>'E',
                'Ê'=>'E','Ě'=>'E','Ē'=>'E','È'=>'E','Е'=>'E','Э'=>'E',
                'Ë'=>'E','Ĕ'=>'E','Ф'=>'F','Г'=>'G','Ģ'=>'G','Ġ'=>'G',
                'Ĝ'=>'G','Ğ'=>'G','Х'=>'H','Ĥ'=>'H','Ħ'=>'H','Ï'=>'I',
                'Ĭ'=>'I','İ'=>'I','Į'=>'I','Ī'=>'I','Í'=>'I','Ì'=>'I',
                'И'=>'I','Ǐ'=>'I','Ĩ'=>'I','Î'=>'I','Ĳ'=>'IJ','Ĵ'=>'J',
                'Й'=>'J','Я'=>'Ja','Ю'=>'Ju','К'=>'K','Ķ'=>'K','Ĺ'=>'L',
                'Л'=>'L','Ł'=>'L','Ŀ'=>'L','Ļ'=>'L','Ľ'=>'L','М'=>'M',
                'Н'=>'N','Ń'=>'N','Ñ'=>'N','Ņ'=>'N','Ň'=>'N','Ō'=>'O',
                'О'=>'O','Ǿ'=>'O','Ǒ'=>'O','Ơ'=>'O','Ŏ'=>'O','Ő'=>'O',
                'Ø'=>'O','Ö'=>'Oe','Õ'=>'O','Ó'=>'O','Ò'=>'O','Ô'=>'O',
                'Œ'=>'OE','П'=>'P','Ŗ'=>'R','Р'=>'R','Ř'=>'R','Ŕ'=>'R',
                'Ŝ'=>'S','Ş'=>'S','Š'=>'S','Ș'=>'S','Ś'=>'S','С'=>'S',
                'Ш'=>'Sh','Щ'=>'Shch','Ť'=>'T','Ŧ'=>'T','Ţ'=>'T','Ț'=>'T',
                'Т'=>'T','Ů'=>'U','Ű'=>'U','Ŭ'=>'U','Ũ'=>'U','Ų'=>'U',
                'Ū'=>'U','Ǜ'=>'U','Ǚ'=>'U','Ù'=>'U','Ú'=>'U','Ü'=>'Ue',
                'Ǘ'=>'U','Ǖ'=>'U','У'=>'U','Ư'=>'U','Ǔ'=>'U','Û'=>'U',
                'В'=>'V','Ŵ'=>'W','Ы'=>'Y','Ŷ'=>'Y','Ý'=>'Y','Ÿ'=>'Y',
                'Ź'=>'Z','З'=>'Z','Ż'=>'Z','Ž'=>'Z','Ж'=>'Zh','á'=>'a',
                'ă'=>'a','â'=>'a','à'=>'a','ā'=>'a','ǻ'=>'a','å'=>'a',
                'ä'=>'ae','ą'=>'a','ǎ'=>'a','ã'=>'a','а'=>'a','ª'=>'a',
                'æ'=>'ae','ǽ'=>'ae','б'=>'b','č'=>'c','ç'=>'c','ц'=>'c',
                'ċ'=>'c','ĉ'=>'c','ć'=>'c','ч'=>'ch','ð'=>'dj','ď'=>'dj',
                'д'=>'dj','đ'=>'dj','э'=>'e','é'=>'e','ё'=>'e','ë'=>'e',
                'ê'=>'e','е'=>'e','ĕ'=>'e','è'=>'e','ę'=>'e','ě'=>'e',
                'ė'=>'e','ē'=>'e','ƒ'=>'f','ф'=>'f','ġ'=>'g','ĝ'=>'g',
                'ğ'=>'g','г'=>'g','ģ'=>'g','х'=>'h','ĥ'=>'h','ħ'=>'h',
                'ǐ'=>'i','ĭ'=>'i','и'=>'i','ī'=>'i','ĩ'=>'i','į'=>'i',
                'ı'=>'i','ì'=>'i','î'=>'i','í'=>'i','ï'=>'i','ĳ'=>'ij',
                'ĵ'=>'j','й'=>'j','я'=>'ja','ю'=>'ju','ķ'=>'k','к'=>'k',
                'ľ'=>'l','ł'=>'l','ŀ'=>'l','ĺ'=>'l','ļ'=>'l','л'=>'l',
                'м'=>'m','ņ'=>'n','ñ'=>'n','ń'=>'n','н'=>'n','ň'=>'n',
                'ŉ'=>'n','ó'=>'o','ò'=>'o','ǒ'=>'o','ő'=>'o','о'=>'o',
                'ō'=>'o','º'=>'o','ơ'=>'o','ŏ'=>'o','ô'=>'o','ö'=>'oe',
                'õ'=>'o','ø'=>'o','ǿ'=>'o','œ'=>'oe','п'=>'p','р'=>'r',
                'ř'=>'r','ŕ'=>'r','ŗ'=>'r','ſ'=>'s','ŝ'=>'s','ș'=>'s',
                'š'=>'s','ś'=>'s','с'=>'s','ş'=>'s','ш'=>'sh','щ'=>'shch',
                'ß'=>'ss','ţ'=>'t','т'=>'t','ŧ'=>'t','ť'=>'t','ț'=>'t',
                'у'=>'u','ǘ'=>'u','ŭ'=>'u','û'=>'u','ú'=>'u','ų'=>'u',
                'ù'=>'u','ű'=>'u','ů'=>'u','ư'=>'u','ū'=>'u','ǚ'=>'u',
                'ǜ'=>'u','ǔ'=>'u','ǖ'=>'u','ũ'=>'u','ü'=>'ue','в'=>'v',
                'ŵ'=>'w','ы'=>'y','ÿ'=>'y','ý'=>'y','ŷ'=>'y','ź'=>'z',
                'ž'=>'z','з'=>'z','ż'=>'z','ж'=>'zh','ь'=>'','ъ'=>'',
                '`'=>'-','"'=>'-'
            ))))),'-');
    }


    /**
     * Function escape some symbols
     *
     * @param   string  $str
     * @return  string
     */
    public static function _jsEscape($str = '')
    {
        $str = trim($str);
        if(!is_numeric($str))
            $str = str_replace(['"'], ['&quot;'], $str);


        return $str;
    }
}