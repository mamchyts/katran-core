<?php
/**
 * The file contains class Controller()
 */
namespace Katran;

/**
 * Base Controller class (parent of all controllers)
 *
 * @package Application
 */
class Controller
{
    /**
     * [$errors description]
     * @var array
     */
    var $errors = [];


    /**
     * Function redirect user to $url
     *
     * @param    string  $url
     * @param    array   $error
     * @param    array   $mes
     * @param    string  $area
     * @return   void
     * @access  public
     */
    public function forward($url = FALSE, $error = array(), $mes = array(), $area = 'global')
    {
        if($url){
            $_url   = new Url($url);
            $errors = array();
            $mess   = array();

            if(is_string($error))
                $errors[] = $error;
            else
                $errors = $error;

            if(is_string($mes))
                $mess[] = $mes;
            else
                $mess = $mes;

            $_SESSION[$_url->getUrl()][$area]['error'] = $errors;
            $_SESSION[$_url->getUrl()][$area]['mess']  = $mess;

            header('HTTP/1.1 301 Moved Permanently');
            Header('Location: '.$url);
            exit();
        }
        else
            trigger_error('Error. No URL string!');
    }


    /**
     * Function disable caching in browser
     *
     * @return    void
     * @access    public
     */
    public static function noCache()
    {
        Header("Expires: Mon, 31 Jul 1989 14:45:00 GMT");
        Header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
        Header("Cache-Control: no-cache, must-revalidate");
        Header("Cache-Control: post-check=0, pre-check=0");
        Header("Cache-Control: max-age=0");
        Header("Pragma: no-cache");
    }


    /**
     * Function return array of errors
     *
     * @return    array
     * @access    public
     */
    public function getErrors()
    {
        return $this->errors;
    }


    /**
     * Function add new error to another errors
     *
     * @param     mixed
     * @return    void
     * @access    public
     */
    public function addError($error = '')
    {
        if(is_string($error))
            $error = [$error];

        $this->errors = array_merge($this->errors, $error);
    }


    /**
     * Function add errors into session
     *
     * @param   array   $error  array of errors
     * @param   boolean $url
     * @param   string  $area
     * @return  void
     * @access  public
     */
    public function addSessionError($error = array(), $url = '', $area = 'global')
    {
        $_url = new Url($url);

        if(is_string($error))
            $errors[] = $error;
        else
            $errors = $error;

        $_SESSION[$_url->getUrl()][$area]['error'] = $errors;
        $_SESSION[$_url->getUrl()][$area]['mess']  = array();
    }


    /**
     * [ajaxResponse description]
     * @param  array   $data
     * @return void
     */
    public function ajaxResponse($data = array())
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit(0);
    }


    /**
     * Function redirect user
     *
     * @param   string $page
     * @return  void
     * @access  public
     */
    public function redirectPage($page)
    {
        $url = new Url($page);
        header('HTTP/1.1 301 Moved Permanently');
        Header('Location: '.$url->getUrl());
        exit();
    }
}