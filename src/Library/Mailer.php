<?php
/**
 * The file contains class Mailer()
 */
namespace Katran\Library;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
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
     * Mailer class
     * @var object
     */
    public $mailer = FALSE;

    /**
     * Constructor
     *
     * Create Mailer object
     *
     * @return  object
     * @access  public
     */
    public function __construct()
    {
        // smtp OR mail (sendmail)
        $conf = Helper::_cfg('mail', 'smtp');
        if (!empty($conf['host'])) {
            $transport = \Swift_SmtpTransport::newInstance($conf['host'], $conf['port'], $conf['secure'])
                ->setTimeout($conf['timeout'])
                ->setUsername($conf['user'])
                ->setPassword($conf['pass']);
        }
        else {
            $transport = \Swift_MailTransport::newInstance();
            $transport->setExtraParams('');
        }

        // Create the Mailer using your created Transport
        $this->mailer = \Swift_Mailer::newInstance($transport);

        // create dir if need 
        if (!file_exists(dirname(Helper::_cfg('mail_log')))) {
            Helper::_mkdir(dirname(Helper::_cfg('mail_log')), Helper::_cfg('filemode', 'folder'));
        }

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
    public function send($to = [], $subject = '', $body = '', $attachment = [])
    {
        // $to must be array
        if (!is_array($to)) {
            $to = array($to);
        }

        // create instance
        $message = \Swift_Message::newInstance();

        // set from options
        $message->setFrom([Helper::_cfg('mail', 'return_path') => Helper::_cfg('mail', 'return_path_name')]);
        $message->setReplyTo([Helper::_cfg('mail', 'return_path') => Helper::_cfg('mail', 'return_path_name')]);
        $message->setContentType('text/html');
        $message->setCharset(Helper::_cfg('page_charset'));

        // set content
        $message->setSubject($subject);
        $message->setBody($body);

        $error = FALSE;
        try {
            // attachment files
            foreach ($attachment as $path => $fileName) {
                $attach = \Swift_Attachment::fromPath($path)->setFilename($fileName);
                $message->attach($attach);
            }

            // add addresses
            $message->setTo($to);

            // try send 
            if ($res = $this->mailer->send($message)) {
                $status = 'ok';
            }
            else {
                $status = 'error';
                $error = 'Internal system error';
            }
        } catch (\Exception $e) {
            $status = 'error';
            $error = $e->getMessage();
            $res = FALSE;
        }

        // drow into .log file
        $this->log($to, $status, $error, $subject);

        return !!$res;
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
    private function log($to = [], $status = '', $error = FALSE, $subject = '')
    {
        // add records to the log
        $this->log->addInfo('Try send email. Result - '.$status."\n", [$subject, $to]);

        if ($error) {
            $this->log->addError('Error: '.$error."\n");
        }
    }
}

/* End of file mailer.php */