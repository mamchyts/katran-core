<?php
/**
 * The file contains class FileHelper()
 */
namespace Katran\Library;

/**
 * This class have some method for work with files
 * 
 * @package Libraries
 */
class FileHelper
{
    /**
     * This method return file content with right headers
     *
     * @param     string   $file_path
     * @param     string   $file_name
     * @return    file
     * @access    public
     */
    public static function downloadFile($file_path = '', $file_name = FALSE)
    {
        if (!file_exists($file_path))
            return FALSE;

        // get file content
        $data = file_get_contents($file_path);

        if (!$file_name)
            $file_name = basename($file_path);

        // Try to determine if the filename includes a file extension.
        // We need it in order to set the MIME type
        if (FALSE === strpos($file_name, '.'))
            return FALSE;

        // Grab the file extension
        $x = explode('.', $file_name);
        $extension = end($x);

        // Load the mime types and set a default mime if we can't find it
        $mimes = self::getAllMimes();
        if (!isset($mimes[$extension]))
            $mime = 'application/octet-stream';
        else
            $mime = (is_array($mimes[$extension])) ? $mimes[$extension][0] : $mimes[$extension];

        // clean buffer
        if (ob_get_level())
            ob_end_clean();

        // Generate the server headers
        if (strpos($_SERVER['HTTP_USER_AGENT'], "MSIE") !== FALSE){
            header('Content-Type: "'.$mime.'"');
            header('Content-Disposition: attachment; filename="'.$file_name.'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header("Content-Transfer-Encoding: binary");
            header('Pragma: public');
            header("Content-Length: ".strlen($data));
        }
        else{
            header('Content-Type: "'.$mime.'"');
            header('Content-Disposition: attachment; filename="'.$file_name.'"');
            header("Content-Transfer-Encoding: binary");
            header('Expires: 0');
            header('Pragma: no-cache');
            header("Content-Length: ".strlen($data));
        }
        exit($data);
    }


    /**
     * This method return check file if it is an image
     *
     * @param     string   $file_path
     * @param     string   $file_name
     * @return    boolean
     * @access    public
     */
    public static function isImage($file_path = '', $file_name = FALSE)
    {
        if (!file_exists($file_path) || empty($file_name))
            return FALSE;

        $file_name = strtolower($file_name);

        // Grab the file extension
        $x = explode('.', $file_name);
        $extension = end($x);

        // Load the mime types and set a default mime if we can't find it
        $mimes = self::getAllMimes();
        if (!isset($mimes[$extension]))
            $mime = 'application/octet-stream';
        else
            $mime = (is_array($mimes[$extension])) ? $mimes[$extension][0] : $mimes[$extension];

        // image/***
        if(strpos($mime, 'image') !== 0)
            return FALSE;

        // check mime type
        $info = getimagesize($file_path);
        if(!isset($info['mime']))
            return FALSE;

        return TRUE;
    }


    /**
     * This method return check file if it is an image
     *
     * @param     string   $file_path
     * @param     string   $file_name
     * @return    boolean
     * @access    public
     */
    public static function getAllMimes()
    {
        static $mimes;
        if(empty($mimes)){
            include(__DIR__.'/other/mimes.php');
        }

        return $mimes;
    }


    /**
     * [getExtentionByMime description]
     * 
     * @param     string $mime [description]
     * @return    boolean
     * @access    public
     */
    public static function getExtentionByMime($mime = '')
    {
        // Load the mime types and set a default mime if we can't find it
        $mimes = self::getAllMimes();

        $extention = '';
        foreach ($mimes as $key => $value) {
            if( ($value === $mime) || (is_array($value) && in_array($mime, $value)) ){
                $extention = $key;
                break;
            }
        }

        return $extention;
    }
}
