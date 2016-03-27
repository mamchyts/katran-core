<?php
/**
 * The file contains class Mailer()
 */
namespace Katran\Library;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use PHPMailer\PHPMailer\PHPMailer;
use Katran\Helper;

/**
 * Mailer class
 * 
 * This class used for send Email letter
 * Class has some method for work with this object.
 * 
 * @version 2013-02-14
 * @package Libraries
 */
class Mailer
{
    /**
     * PHPMailer class
     * @var object
     */
    public $phpmailer = FALSE;

    /**
     * Constructor
     *
     * Create PHPMailer object
     *
     * @return  object
     * @access  public
     */
    public function __construct()
    {
        // create object
        $this->phpmailer = new PHPMailer();

        // set from options
        $this->phpmailer->SetFrom(Helper::_cfg('mail', 'return_path'), Helper::_cfg('mail', 'return_path_name'));
        $this->phpmailer->AddReplyTo(Helper::_cfg('mail', 'return_path'), Helper::_cfg('mail', 'return_path_name'));
        $this->phpmailer->IsHTML(true);
        $this->phpmailer->IsSMTP();
        $this->phpmailer->SMTPDebug  = 0;
        $this->phpmailer->CharSet    = Helper::_cfg('page_charset');
        $this->phpmailer->Host       = Helper::_cfg('mail', 'smtp', 'host');
        $this->phpmailer->SMTPAuth   = Helper::_cfg('mail', 'smtp', 'auth');
        $this->phpmailer->SMTPSecure = Helper::_cfg('mail', 'smtp', 'secure');
        $this->phpmailer->Port       = Helper::_cfg('mail', 'smtp', 'port');
        $this->phpmailer->Timeout    = Helper::_cfg('mail', 'smtp', 'timeout');

        // set username/password
        $this->phpmailer->Username   = Helper::_cfg('mail', 'smtp', 'user');
        $this->phpmailer->Password   = Helper::_cfg('mail', 'smtp', 'pass');

        // create dir if need 
        if(!file_exists(dirname(Helper::_cfg('mail_log'))))
            Helper::_mkdir(dirname(Helper::_cfg('mail_log')));

        // create a log channel
        $this->log = new Logger('mail');
        $this->log->pushHandler(new StreamHandler(Helper::_cfg('mail_log'), null, null, 0777));
    }


    /**
     * Method for send email letter
     *
     * @param  array  $to
     * @param  string $subject
     * @param  string $body
     * @param  array  $attachment  array of attachment files
     * @return void
     * @access public
     */
    public function send($to = array(), $subject = '', $body = '', $attachment = array())
    {
        // clear old data;
        $this->phpmailer->clearAllRecipients();
        $this->phpmailer->clearAttachments();

        // $to must be array
        if(!is_array($to))
            $to = array($to);

        $this->phpmailer->Subject = $subject;
        $this->phpmailer->MsgHTML($body);

        $error = FALSE;
        try {
            // attachment files
            foreach ($attachment as $key => $file_name)
                $this->phpmailer->AddAttachment($key, $file_name);

            // add addresses
            foreach ($to as $name => $address)
                $this->phpmailer->AddAddress($address, (!is_numeric($name))?$name:'');

            // try send 
            if($this->phpmailer->Send()){
                $status = 'ok';
                $res = TRUE;
            }
            else{
                $status = 'error';
                $error = $this->phpmailer->ErrorInfo;
                $res = FALSE;
            }
        } catch (\Exception $e) {
            $status = 'error';
            $error = $e->getMessage();
            $res = FALSE;
        }

        // drow into .log file
        $this->log($to, $status, $error, $subject);

        return $res;
    }


    /**
     * Function save data into log file
     * 
     * @param  array   $to     array of addresses
     * @param  string  $status
     * @param  boolean|string $error
     * @param  string  $subject
     * @return void
     */
    private function log($to = array(), $status = '', $error = FALSE, $subject = '')
    {
        // add records to the log
        $this->log->addInfo("Status: ".$status."\n", [$subject, $to]);

        if($error)
            $this->log->addError("Error: ".$error."\n");
    }
}

/* End of file mailer.php */