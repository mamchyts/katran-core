<?php
/**
 * The file contains class Pager()
 */
namespace Katran\Library;

/**
 * This class used in database for show not all rows
 * Class also has method for show html pager
 *
 * @package Libraries
 */
class Pager
{
    /**
     * Total page
     * var integer
     */
    private $current = 0;

    /**
     * Count of all pages
     * var integer
     */
    private $pages = 0;

    /**
     * Count rows on one page
     * var integer
     */
    private $count = 0;

    /**
     * Total page
     * var integer
     */
    private $rows = 0;

    /**
     * Count of all rows in database
     * var integer
     */
    private $init = 0;

    /**
     * Url object
     * var object
     */
    private $url = false;


    /**
     * Constructor set url and rows count on page
     *
     * @param     object    $url
     * @param     integer   $count
     * @return    void
     * @access    public
     */
    public function __construct($url, $count = 20)
    {
        $this->url = $url;
        $this->count = $count;
        $this->current = $url->getInt('page');
        if($this->current === 0)
            $this->current = 1;
    }


    /**
     * Calculates how mach pages need.
     *
     * @param    integer   $init
     * @return   void
     * @access   public
     */
    public function init($init = 0)
    {
        $this->init = $init;
        $this->pages = intval(ceil($init/$this->count));
        if($this->current > $this->pages)
            $this->current = 1;
    }


    /**
     * Function return string for sql request
     *
     * @return   string
     * @access   public
     */
    public function getLimit()
    {
        $from = $this->count*$this->current - $this->count;
        $to   = $this->count;
        return $from.', '.$to;
    }


    /**
     * Function return html code for insert into template
     *
     * @return   string
     * @access   public
     */
    public function getHtml()
    {
        if(($this->pages === 0) || ($this->pages === 1))
            return '';

        for($i = 0; $i < $this->pages; $i++){
            $this->url->setParam('page', $i+1);
            $href[$i] = $this->url->getUrl(TRUE);
        }

        $html = '<div class="pagination-block">';
        $html .= '<ul class="pagination">';

        // first link
        if($this->current != 1)
            $html .= '<li class=""><a href="'.$href[$this->current-2].'"><i class="material-icons">&#xe5cb;</i></a></li>';

        if($this->pages <= 9){
            foreach($href as $key=>$a){
                if($key === ($this->current-1))
                    $html .= '<li class="active"><a href="javascript:void(0)">'.($key+1).'</a></li>';
                else
                    $html .= '<li class=""><a href="'.$a.'">'.($key+1).'</a></li>';
            }
        }
        else{
            $before = FALSE;
            $after = FALSE;
            $html_after = FALSE;
            for($i = 0; $i < $this->pages; $i++){
                if(($i < 2  )){
                    if($i === ($this->current-1)){
                        $html .= '<li class="active"><a href="javascript:void(0)">'.($i+1).'</a></li>';
                        $before = TRUE;
                    }
                    else
                        $html .= '<li class=""><a href="'.$href[$i].'">'.($i+1).'</a></li>';
                }
                elseif(($i >= $this->pages - 2)){
                    if($i === ($this->current-1)){
                        $html .= '<li class="active"><a href="javascript:void(0)">'.($i+1).'</a></li>';
                        $after = TRUE;
                    }
                    else
                        $html .= '<li class=""><a href="'.$href[$i].'">'.($i+1).'</a></li>';
                }
                elseif(in_array($i, array($this->current-3, $this->current-2, $this->current-1, $this->current, $this->current+1))){
                    if($i > 3)
                        $before = TRUE;
                    $after = TRUE;

                    if($i === ($this->current-1))
                        $html .= '<li class="active"><a href="javascript:void(0)">'.($i+1).'</a></li>';
                    else
                        $html .= '<li class=""><a href="'.$href[$i].'">'.($i+1).'</a></li>';

                }
                else{
                    if(!$before){
                        $before = TRUE;
                        $html .= '<li class="disabled"><a href="javascript:void(0)">...</a></li>';
                    }
                    elseif($after && !$html_after){
                        $html_after = TRUE;
                        $html .= '<li class="disabled"><a href="javascript:void(0)">...</a></li>';
                    }
                }
            }
        }

        // first link
        if($this->current != $this->pages)
            $html .= '<li class=""><a href="'.$href[$this->current].'"><i class="material-icons">&#xe5cc;</i></a></li>';

        $html .= '</ul></div>';
        $this->url->setParam('page', $this->current);
        return $html;
    }
}
