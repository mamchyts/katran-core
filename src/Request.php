<?php
/**
 * The file contains class Request()
 */
namespace Katran;

/**
 * This class save all POST and GET data into object Request.
 * Class has some method for work with this object.
 *
 * @package Application
 */
class Request
{
    /**
     * Array of all income variables array_merge($_POST, $_GET)
     * var array
     */
    private $args = array();


    /**
     * Constructor
     *
     * Set default varaible
     * Create array - args
     *
     * @return   void
     * @access   public
     */
    public function __construct()
    {
        $this->args = array_merge($_POST, $_GET);
    }


    /**
     * Function set value
     *
     * @param   string    $name of the varaible
     * @return  mixed
     * @access  public
     */
    public function setParam($name = '', $value = [])
    {
        $this->args[$name] = $value;
    }


    /**
     * Function return value of param
     *
     * @param   string    $name of the varaible
     * @return  mixed
     * @access  public
     */
    public function getParam($name)
    {
        if(isset($this->args[$name]))
            return $this->args[$name];
        else
            return null;
    }


    /**
     * Function return value of param
     *
     * @param     string    $name of the varaible
     * @param     mixed     $default
     * @return    int
     */
    public function getInt($name, $default = 0)
    {
        if(isset($this->args[$name]))
            return intval($this->args[$name]);
        else
            return $default;
    }


    /**
     * Function return value of param
     *
     * @param     string    $name of the varaible
     * @param     mixed     $default
     * @return    float
     */
    public function getFloat($name, $default = 0)
    {
        if(isset($this->args[$name]))
            return floatval($this->args[$name]);
        else
            return $default;
    }


    /**
     * Function return value of param
     *
     * @param     string    $name of the varaible
     * @param     mixed     $default
     * @return    string
     */
    public function getString($name, $default = '')
    {
        if(isset($this->args[$name]))
            return strval($this->args[$name]);
        else
            return $default;
    }


    /**
     * Default get function
     * Function return value of param
     *
     * @version   2012-08-19
     * @param     string    $name of the varaible
     * @param     mixed     $default
     * @return    mixed
     * @access    public
     */
    public function get($name, $default = '')
    {
        if(isset($this->args[$name]))
            return $this->args[$name];
        else
            return $default;
    }


    /**
     * Function return message from  Core::forward
     *
     * @param     string    $url
     * @param     string    $area
     * @return    array
     * @access    public
     */
    public function getMessage($url = null, $area = 'global')
    {
        if(!$url){
            $url = new Url();
            $url = $url->getUrl();
        }

        if(isset($_SESSION[$url][$area])){
            $mess = $_SESSION[$url][$area];
            unset($_SESSION[$url]);
            return $mess;
        }
        else
            return array();
    }


    /**
     * Function return all params ($_args)
     *
     * @return   array
     * @access  public
     */
    public function getArgs()
    {
        return $this->args;
    }


    /**
     * Function return array of params
     *
     * @return   array
     * @param    string    $name of the varaible
     * @param    mixed     $default
     * @access   public
     */
    public function getArray($name, $default = array())
    {
        if(isset($this->args[$name]))
            return $this->args[$name];
        else
            return $default;
    }


    /**
     * Function true if XMLHttpRequest
     *З
     * @return   boolean
     * @access   public
     */
    public function isXMLHttpRequest()
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest'));
    }

}