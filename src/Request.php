<?php
/**
 * The file contains class Request()
 */
namespace Katran;

use GuzzleHttp\Psr7\ServerRequest;

/**
 * Request Class
 * 
 * @package Request
 * @uses    ServerRequest
 */
class Request
{
    /**
     * All income parameters $_REQUEST
     * var array
     */
    protected $data = false;

    /**
     * var ServerRequest
     */
    public $serverRequest = null;


    /**
     * Return a ServerRequest populated with superglobals
     * 
     * @see ServerRequest::fromGlobals()
     */
    public function __construct()
    {
        $this->serverRequest = ServerRequest::fromGlobals();
        $this->data = array_merge(
            $this->serverRequest->getQueryParams(),
            $this->serverRequest->getParsedBody()
        );
    }


    /**
     * Function get value from Request
     *
     * @param   string  $key
     * @param   mixed   $defaul
     * @return  int
     * @access  public
     */
    public function get($key = '', $default = null)
    {
        return !empty($this->data[$key])?$this->data[$key]:$default;
    }


    /**
     * Function get value from Request
     *
     * @param   string  $key
     * @param   int     $defaul
     * @return  int
     * @access  public
     */
    public function getInt($key = '', $default = 0)
    {
        return intval($this->get($key, $default));
    }


    /**
     * Function get value ftom container by $key
     *
     * @param   string  $key
     * @param   array   $defaul
     * @return  array
     * @access  public
     */
    public function getArray($key = '', $default = [])
    {
        // get array - only in body
        $tmp = $this->serverRequest->getParsedBody();
        return !empty($tmp[$key])?$tmp[$key]:$default;
    }


    /**
     * Function get all
     *
     * @return  array
     * @access  public
     */
    public function getAll()
    {
        return $this->data;
    }
}