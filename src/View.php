<?php
/**
 * The file contains class View()
 */
namespace Katran;

/**
 * This class create object View.
 * Class has some method for work with this object.
 * 
 * @package Application
 */
class View
{
    /**
     * File name
     * var string
     */
    public $fileName = '';

    /**
     * Array of variables
     * var array
     */
    public $args = array();

    /**
     * Array of templates in current view
     * var array
     */
    public $templates = array();


    /**
     * Constructor
     *
     * Set file name of template if was send his name.
     *
     * @return   void
     * @param    string  $file
     * @access  public
     */
    public function __construct($file = FALSE)
    {
        if($file)
            $this->fileName = $file;
    }


    /**
     * Set file name of template
     *
     * @param    string  $file
     * @return   void
     * @access  public
     */
    public function setView($file = FALSE)
    {
        if($file)
            $this->fileName = $file;
        else
            _d('Class name: '.__CLASS__.' line: '.__LINE__.' function: '.__FUNCTION__.' You must set view file.',1);
    }


    /**
     * Set args - variables for template.
     *
     * @param    string  $name
     * @param    mixed   $var
     * @return   void
     * @access  public
     */
    public function setVar($name = '', $var = FALSE)
    {
        if(intval($name) === 0)
            $this->args[$name] = $var;
        else
            _d('Class name: '.__CLASS__.' line: '.__LINE__.' function: '.__FUNCTION__.'() You must set correct param\'s name.',1);
    }
}

/* End of file view.php */