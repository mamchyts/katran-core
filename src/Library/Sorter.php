<?php
/**
 * The file contains class Sorter()
 */
namespace Katran\Library;

/**
 * This class used in database for sort rows
 * Class also has method for show html sorter
 *
 * @version 2015-10-07
 * @package Libraries
 */
class Sorter
{
    /**
     * SQL value for ORDER BY
     * var string
     */
    private $sqlSort = '';

    /**
     * SQL value for ORDER BY: DESC|ASC
     * var string
     */
    private $by = '';

    /**
     * SQL value for ORDER BY: name of column
     * var string
     */
    private $field = '';

    /**
     * Table columns
     * var array
     */
    private $map = array();

    /**
     * Path to image
     * var string
     */
    private $imgAsc = '<i class="fa fa-long-arrow-up" aria-hidden="true"></i>';

    /**
     * Path to image
     * var string
     */
    private $imgDesc = '<i class="fa fa-long-arrow-down" aria-hidden="true"></i>';


    /**
     * Constructor set url and rows count on page
     *
     * @param     object    $url
     * @return    void
     * @access    public
     */
    public function __construct($url, $count = 20)
    {
        $this->url = $url;
    }


    /**
     * Initialize method
     *
     * Method parser url for find param 'sort', if it param exist - sort by him.
     * Else sort by param which sent in method init().
     *
     * @param    array   $init  array('title' => 'asc')
     * @return   void
     * @access   public
     */
    public function init($init = array())
    {
        // init run
        $sql = array();
        foreach($init as $key=>$by){
            if(in_array(strtolower($by), ['asc', 'desc'])){
                $this->by = strtoupper($by);
                $this->field = $key;
                $sql[] = $key.' '.$this->by;
            }
        }

        // if ulr already contain sort field
        $sort = $this->url->getParam('sort');
        if(!empty($sort)){
            $init = strtolower($sort);
            $by = trim(strrchr($init, '_'), '_');

            $sql = [];
            $this->by = strtoupper($by);
            $this->field = substr($init, 0, -(strlen($this->by)+1));
            $sql[] = $key.' '.$this->by;
        }

        $this->sqlSort = implode(',', $sql);
    }


    /**
     * Function return string for sql request
     *
     * @return   string
     * @param    array|hash $map
     * @access   public
     */
    public function getOrder($map = array())
    {
        if (!isset($map[$this->field])) {
            $map[$this->field] = $this->field;
        }

        return $map[$this->field].' '.$this->by;
    }


    /**
     * Function return html code for insert into template
     *
     * @param    string    $field
     * @param    string    $title
     * @return   string
     * @access   public
     */
    public function getHtml($field = '', $title = '')
    {
        $field = strtolower($field);
        $htmlImage = '';

        //save current sort param
        $totalSort = $this->url->getParam('sort');

        // if field is already in sort object
        if(isset($this->field) && ($this->field === $field)){
            if($this->by === 'ASC'){
                $this->url->setParam('sort', $field.'_desc');
                $url = $this->url->getUrl(TRUE);
                $image = $this->imgAsc;
            }
            else{
                $this->url->setParam('sort', $field.'_asc');
                $url = $this->url->getUrl(TRUE);
                $image = $this->imgDesc;
            }
            $htmlImage = '<a href="'.$url.'" class="sorter-block__img">'.$image.'</a>';
        }
        else{
            $this->url->setParam('sort', $field.'_asc');
            $url = $this->url->getUrl(TRUE);
            $image = $this->imgDesc;
        }
        $this->url->setParam('sort', $totalSort);

        $html = '<div class="sorter-block"><a href="'.$url.'" class="sorter-block__title">'.$title.'</a>'.$htmlImage.'</div>';
        return $html;
    }
}

/* End of file sorter.php */