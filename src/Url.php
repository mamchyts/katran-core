<?php
/**
 * The file contains class Url()
 */
namespace Katran;

/**
 * Url Class
 * Parses Url
 *
 * @package Application
 */
class Url
{
    /**
     * Array of GET arguments
     * var array
     */
    private $args = array();

    /**
     * GET arguments in string
     * var string
     */
    private $str_args  = '';

    /**
     * Url schema (http|https|ftp ...)
     * var string
     */
    private $schema = '';

    /**
     * Url host
     * var string
     */
    private $host = '';

    /**
     * Url path
     * var string
     */
    private $path = '';

    /**
     * Full url string
     * var string
     */
    private $url  = '';


    /**
     * Constructor
     * Parse default string or $_SERVER['QUERY_STRING']
     *
     * @param   mixed   $str
     * @access  public
     */
    public function __construct($str = false)
    {
        if (!$str) {
            $str = $_SERVER['REQUEST_URI'].'?'.$_SERVER['QUERY_STRING'];
        }

        $str  = str_replace(array("#"), "", $str);
        $temp = explode('?', $str);

        $this->path = $temp[0];
        $this->str_args = (isset($temp[1]))?$temp[1]:'';

        parse_str($this->str_args, $this->args);

        // clear empty vars
        foreach ($this->args as $key => $value) {
            if (empty($value)) {
                unset($this->args[$key]);
            }
        }

        if (!strstr($this->path, Helper::_cfg('host'))){
            $this->schema = Helper::_cfg('schema');
            $this->host   = Helper::_cfg('host');
        }
    }


    /**
     * Function return url
     *
     * @return  string
     * @param   boolean $replaceAmp
     * @access  public
     */
    public function getUrl($replaceAmp = false)
    {
        // correct url view (after .htaccess)
        if (isset($this->args['controller']) && isset($this->args['category'])){
            if ($this->path === '/index.php') {
                $this->path = '';
            }

            $this->path = '/'.$this->args['controller'].'/'.$this->args['category'];
            unset($this->args['controller']);
            unset($this->args['category']);

            if (isset($this->args['item'])){
                $this->path .= '/'.$this->args['item'];
                unset($this->args['item']);

                if (isset($this->args['action'])){
                    unset($this->args['action']);                    
                }
            }

            if (isset($this->args['act'])) {
                unset($this->args['act']);
            }
        }
        elseif (isset($this->args['alias'])){
            $this->path = '/'.$this->args['alias'];
            unset($this->args['alias']);
        }

        // create url string
        $this->url = $this->schema.$this->host.$this->path;
        if (count($this->args) !== 0){
            if ($replaceAmp === true) {
                $this->url .= '?'.http_build_query($this->args, '', '&amp;');
            }
            else {
                $this->url .= '?'.http_build_query($this->args);
            }
        }

        return $this->url;
    }


    /**
     * Function set one param to url
     *
     * @param    string      $var
     * @param    int|string  $value
     * @return   void
     * @access  public
     */
    public function setParam($var, $value = 0)
    {
        $this->args[$var] = is_numeric($value)?$value:trim($value);
    }


    /**
     * Function get param from url
     *
     * @param    string  $var
     * @return   string
     * @access  public
     */
    public function getParam($var)
    {
        if (isset($this->args[$var])) {
            return strval(trim($this->args[$var]));
        }
        else {
            return '';
        }
    }


    /**
     * Function get param from url
     *
     * @param    string   $var
     * @return   integer
     * @access  public
     */
    public function getInt($var)
    {
        if (isset($this->args[$var])) {
            return intval($this->args[$var]);
        }
        else {
            return 0;
        }
    }


    /**
     * Function clear all params
     *
     * @return   void
     * @access  public
     */
    public function clearParams()
    {
        $this->args = array();
    }


    /**
     * Function delete one param
     *
     * @param    string   $var
     * @return   void
     * @access  public
     */
    public function deleteParam($var)
    {
        if (isset($this->args[$var])) {
            unset($this->args[$var]);
        }
    }
}

/* End of file Url.php */