<?php
/**
 * The file contains class Controller()
 */
namespace Katran;

use Katran\Library\Flashbag;

/**
 * Base Controller class (parent of all controllers)
 */
class Controller
{
    // constant
    const CONTAINER_VAR_AREA = '_area';
    const CONTAINER_VAR_ACTION = '_action';
    const CONTAINER_VAR_CONTROLLER = '_controller';

    const CONTAINER_VAR_ALIAS = '_alias';
    const CONTAINER_VAR_ALIAS_PAGE = '_aliasPage';
    const CONTAINER_VAR_ALIAS_CONTENT_FILE = '_aliasContentFile';
    const CONTAINER_VAR_ALIAS_CONTENT_ACTION = '_aliasContentAction';

    const CONTAINER_VAR_LAYOUT = '_layout';
    const CONTAINER_VAR_LAYOUT_CONTENT = '_layoutContent';

    /**
     * Global storage
     * var string
     */
    protected $container = false;

    /**
     * [$errors description]
     * @var array
     */
    private $errors = [];


    /**
     * Function redirect user to $url
     * 
     * @param  string  $url
     * @param  string  $error
     * @param  string  $info
     * @param  boolean $is301Redirect
     * @return void
     */
    public function forward($url, $error = '', $info = '', $is301Redirect = false)
    {
        // save messages
        if (!empty($error)) {
            Flashbag::add(Flashbag::TYPE_ERROR, $error);
        }
        if (!empty($info)) {
            Flashbag::add(Flashbag::TYPE_INFO, $info);
        }

        if ($is301Redirect) {
            header('HTTP/1.1 301 Moved Permanently');
        }

        Header('Location: '.$url);
        exit();
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
        if (is_scalar($error)) {
            $error = [$error];
        }

        $this->errors = array_merge($this->errors, $error);
    }


    /**
     * Function add errors into session
     *
     * @param   array   $e
     * @return  void
     * @access  public
     */
    public function addSessionErrors(array $e)
    {
        foreach ($e as $error) {
            Flashbag::add(Flashbag::TYPE_ERROR, $error);
        }
    }


    /**
     * [ajaxResponse description]
     * @param  array   $data
     * @return void
     */
    public function ajaxResponse($data = [])
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }


    /**
     * Function set Container
     *
     * @param  string
     * @return void
     * @access protected
     */
    protected function setContainer(Container $container)
    {
        $this->container = $container;
    }


    /**
     * Function return Container
     *
     * @return Container
     * @access protected
     */
    protected function getContainer()
    {
        return $this->container;
    }


    /**
     * Function set value in container
     *
     * @param   string  $key
     * @param   mixed   $value
     * @return  void
     * @access  public
     */
    public function setContainerVar($key = '', $value = null)
    {
        $this->getContainer()->set($key, $value);
    }


    /**
     * Function get value ftom container by $key
     *
     * @param   string  $key
     * @return  mixed
     * @access  public
     */
    public function getContainerVar($key = '')
    {
        return $this->getContainer()->get($key);
    }


    /**
     * Function set layout
     *
     * @param   string $layout
     * @return  void
     * @access  public
     */
    public function setLayout($layout = '')
    {
        $this->setContainerVar(self::CONTAINER_VAR_LAYOUT, $layout);
    }


    /**
     * Function get layout
     *
     * @return  string
     * @access  protected
     */
    protected function getLayout()
    {
        $this->getContainerVar(self::CONTAINER_VAR_LAYOUT);
    }


    /**
     * [setArea description]
     * @param string $area [description]
     */
    public function setArea($area = 'public')
    {
        $this->setContainerVar(self::CONTAINER_VAR_AREA, $area);
    }


    /**
     * [getArea description]
     * @return    string
     * @access    public
     */
    public function getArea()
    {
        return $this->getContainerVar(self::CONTAINER_VAR_AREA);
    }
}