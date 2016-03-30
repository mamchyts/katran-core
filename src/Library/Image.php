<?php
/**
 * The file contains class Image()
 */
namespace Katran\Library;


/**
 * This class for work with images
 *  
 * @see Intervention\Image
 * @package     Libraries
 * @version     2015-05-26
 */
class Image
{
    /**
     * Creates a new Image instance
     *
     * @see Intervention\Image
     */
    static function getInstance()
    {
        if(!isset($img))
            static $img = null;

        if(empty($img)){
            $img = new Intervention\Image\ImageManager(array('driver' => 'gd'));
        }

        return $img;
    }
}


/* End of file image.php */