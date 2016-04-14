<?php
/**
 * The file contains class Application() extends Timer()
 */
namespace Katran;

use Katran\Library\Timer;
use Katran\Model\Accounts;
use GuzzleHttp\Psr7\ServerRequest;

/**
 * Application Class
 * 
 * Base class of framework.
 * Main class of all site.
 *
 * @package Application
 * @uses    Timer
 */
class Application extends Controller
{

    /**
     * Global storage
     * var string
     */
    protected $container = false;


    /**
     * Constructor
     * Set default varaible
     *
     * @return   void
     * @access  public
     */
    public function __construct()
    {
        // start first mark for Timer
        Timer::mark('globalStart');

        // if we view error text, we already have session
        if(!isset($_SESSION))
            session_start();

        // init obj
        $this->container = new Container();

        // create request object
        $request = ServerRequest::fromGlobals();
        $this->container->set('request', $request);

        // need for detect action
        $queryParams = $request->getQueryParams();

        // set alias value if exist, or set default `index`
        $this->container->set('_alias', !empty($queryParams['alias'])?$queryParams['alias']:'index');

        // set controller/action if exist
        if(!empty($queryParams['controller']))
            $this->container->set('_controller', $queryParams['controller']);
        if(!empty($queryParams['action']))
            $this->container->set('_action', $queryParams['action']);

        // if form was send, and submit has special name
        $parsedBody = $request->getParsedBody();
        if( $submitBtn = !empty($parsedBody['submit'])?$parsedBody['submit']:null ){
            $submit = explode('|', key($submitBtn));
            $this->container->set('_controller', $submit[0]);
            $this->container->set('_action', $submit[1]);
        }

        // if debug On save request into debug store
        if(Helper::_cfg('debug'))
            Helper::_debugStore('request', $this->container->get('request'));
    }


    /**
     * Function analise URL and select any action
     *
     * @return   void
     * @access  public
     */
    public function process()
    {
        switch ($this->container->get('_area')) {
            case Accounts::AREA_ADMIN:
            case Accounts::AREA_MEMBER:
                // redirect to login page
                if(!isset($_SESSION[$this->container->get('_area')]) && !in_array($this->container->get('_action'), ['login', 'do_login'])){
                    $this->container->set('_controller', 'account');
                    $this->container->set('_action', 'login');
                }
                elseif(isset($_SESSION[$this->container->get('_area')]) && !$this->container->get('_controller')){
                    $this->container->set('_controller', 'account');
                    $this->container->set('_action', 'stat');
                }
                break;
            default:
                if(!isset($_SESSION[Accounts::AREA_VISITOR])){
                    $_SESSION[Accounts::AREA_VISITOR]['id'] = 0;
                    $_SESSION[Accounts::AREA_VISITOR]['fname'] = 'Гость';
                    $_SESSION[Accounts::AREA_VISITOR]['lname'] = '';
                }
                break;
        }

        // set default layout
        $this->setLayout('./'.ucfirst($this->getArea()).'/View/layout.php');

        // run process
        if($this->container->get('_controller'))
            $this->_processMod();
        elseif($this->container->get('_alias'))
            $this->_processAlias();
        else
            $this->forward('/404');

        // prepare page response
        $this->_getFullText();
    }


    /**
     * Function check if user can view request page
     *
     * @return  void
     * @access  private
     */
    private function _processAlias()
    {
        $rights = $this->_checkPageAlias($this->container->get('_alias'), $this->container->get('_area'));

        // 1 - ok, 0 - must be login, -1 - file none exist
        if($rights === -1)
            $this->forward('/404');
        elseif($rights === 0){
            // access off
            if(empty($_SESSION[$this->container->get('_area')]))
                $this->forward('/401');
        }

        $this->_setHeaderCharset();
        $this->getAliasContent();
    }


    /**
     * Function analise $controller, $action and call some class
     *
     * @return   void
     * @access  private
     */
    private function _processMod()
    {
        if(!$this->container->get('_controller'))
            trigger_error("Class name: ".__CLASS__.'()  function: '.__FUNCTION__.'()  Request hasn\'t Mod param.');

        $area           = ucfirst($this->container->get('_area'));
        $controller     = '\\'.$this->getSrcNamespace().'\\'.$area.'\\Controller\\'.ucfirst($this->container->get('_controller'));
        $classFilePath  = Helper::_cfg('path_src').'/'.$area.'/Controller/'.ucfirst($this->container->get('_controller')).'.php';
        $viewPath       = Helper::_cfg('path_src').'/'.$area.'/View/';
        $actMethod      = str_replace('_', '', $this->container->get('_action')).'Action';

        if(!file_exists($classFilePath))
            trigger_error('File: <em>'.$classFilePath.'</em>. Not found.');

        // timer mark
        if(Helper::_cfg('debug'))
            Timer::mark('call_controller[_processMod]');

        // call controller
        $obj = new $controller();

        if(!method_exists($obj, $actMethod))
            trigger_error('Call to undefined method '.$controller.'::'.$actMethod.'()');

        // call method
        $view = call_user_func_array([$obj, $actMethod], [$this->container->get('request')]);

        // timer mark
        if(Helper::_cfg('debug'))
            Timer::time('call_controller[_processMod]');

        // parse view for find templates tags
        $templates = $this->getTemplateFromView($viewPath.$view->fileName);

        // if view has templates - get their content
        if(sizeof($templates) > 0){
            $this->parseTemplateFromView($templates, $view);
            $this->getTemplateContent($view);
        }

        // get html text from action result
        $view->content = $this->_getFileContent($viewPath.$view->fileName, $view->args);

        // if view has templates - replace template tags substitute for their content
        if(sizeof($templates) > 0){
            $this->replaceTemplateTags($view);
        }

        // save result
        // it's bad but work quickly
        $this->container->set('_layoutContent', $view->content);
    }


    /**
     * Function check if file exist (and access rights)
     *
     * @param    string     $page
     * @param    string     $area
     * @return   integer    -1: page not found, 0: user havn't rights, 1: ok 
     * @access   private
     */
    private function _checkPageAlias($page, $area)
    {
        // timer mark
        if(Helper::_cfg('debug'))
            Timer::mark('_checkPageAlias');

        $page .= '.php';

        // read directory and save results into tmp variable $okFiles
        $okFiles = [];
        $dir = opendir(Helper::_cfg('path_src').'/Common/View/_alias/');
        while(($f = readdir($dir)) !== FALSE){
            if(strstr($f, $page) !== FALSE)
                $okFiles[] = $f;
        }

        // foreach all matches
        $res = -1;
        $matches_lingth = sizeof($okFiles);
        if($matches_lingth > 0){
            for($i = 0; $i < $matches_lingth; ++$i){
                // full match - return 'ok'
                if($okFiles[$i] === $page){
                    $res = 1;
                    $this->container->set('_aliasPage', $okFiles[$i]);
                    break;
                }
                elseif ($okFiles[$i] === $area.'_'.$page) {
                    $res = 0;
                    $this->container->set('_aliasPage', $okFiles[$i]);
                    break;
                }
            }
        }
        elseif(file_exists(Helper::_cfg('path_src').'/Common/View/_alias/default.php')){
            $res = 1;
            $this->container->set('_aliasPage', 'default.php');
        }

        // timer mark
        if(Helper::_cfg('debug'))
            Timer::time('_checkPageAlias');

        return $res;
    }


    /**
     * Function set layout
     *
     * @param    string
     * @return   void
     * @access  public
     */
    public function setLayout($layout = '')
    {
        $this->container->set('_layout', $layout);
    }


    /**
     * Function get content from file './src/Common/View/_alias/'.$this->container->get('_alias').'.php'
     * Used in error handler.
     *
     * @return   void
     * @param  boolean $debugErrorText
     * @access public
     */
    public function getAliasContent($debugErrorText = FALSE)
    {
        // timer mark
        if(Helper::_cfg('debug'))
            Timer::mark('getAliasContent');

        // get content from alias page.
        $file = Helper::_cfg('path_src').'/Common/View/_alias/'.$this->container->get('_aliasPage');

        if($debugErrorText){
            $vars = array('error' => $debugErrorText);
            $this->container->set('_layoutContent', $this->_getFileContent($file, $vars));
            $this->_getFullText();
            $this->display();
            exit(0);
        }

        $this->container->set('_alias_content_file', $this->_getFileContent($file));
        $this->_parserAliasFile($this->container->get('_alias_content_file'));
        if($this->container->get('_alias_content_action') && sizeof($this->container->get('_alias_content_action'))){
            $this->_processGetModelAction($this->container->get('_alias_content_action'));

            // replace tags <action controller="..." action="..."></action> substitute for their content
            foreach($this->container->get('_alias_content_action') as $tag=>$act){
                $this->container->set('_alias_content_file', str_replace($tag, $act, $this->container->get('_alias_content_file')));
            }

            $this->container->set('_layoutContent', $this->container->get('_alias_content_file'));
        }
        else{
            $vars = [];
            $this->container->set('_layoutContent', $this->_getFileContent($file, $vars));
        }

        // timer mark
        if(Helper::_cfg('debug'))
            Timer::time('getAliasContent');
    }


    /**
     * Function parse file './src/Common/View/_alias/'.$this->container->get('_alias').'.php'
     *
     * @param    string     $content
     * @return   void
     * @access  private
     */
    private function _parserAliasFile($content)
    {
        // timer mark
        if(Helper::_cfg('debug'))
            Timer::mark('_parserAliasFile');

        preg_match_all('|([<]{1}action([^<]*)[>]{1})([^<]*)([/<]{2}action[>]{1})|i', $content, $action);
        if(sizeof($action[0]) > 0){
            $tmp = [];
            foreach($action[0] as $act){
                $tmp[] = $act;
            }

            $this->container->set('_alias_content_action', $tmp);
            $tmp = null;
        }

        // timer mark
        if(Helper::_cfg('debug'))
            Timer::time('_parserAliasFile');
    }


    /**
     * Function  parse <action controller="..." action="..."></action>.
     * Then call some controller and method
     * 
     * @param    array  $action
     * @return   void
     * @access  private
     */
    private function _processGetModelAction($action = [])
    {
        // timer mark
        if(Helper::_cfg('debug'))
            Timer::mark('_processGetModelAction');

        $tmp = [];
        foreach($action as $key=>$act){
            preg_match('/(controller=[\'|"]?([[:alnum:]_]+)[\'|"]?)[\s]*(action=[\'|"]?([[:alnum:]_]+)[\'|"]?)/i', $act, $_controller);

            // create controller object (if exists)
            if(sizeof($_controller) === 5){
                $area           = ucfirst($this->container->get('_area'));
                $controller     = '\\'.$this->getSrcNamespace().'\\'.$area.'\\Controller\\'.ucfirst($_controller[2]);
                $classFilePath  = Helper::_cfg('path_src').'/'.$area.'/Controller/'.ucfirst($_controller[2]).'.php';
                $viewPath       = Helper::_cfg('path_src').'/'.$area.'/View/';
                $actMethod      = str_replace('_', '', $_controller[4]).'Action';

                if(!file_exists($classFilePath))
                    trigger_error('File: <em>'.$classFilePath.'</em>. Not found.');

                // timer mark
                if(Helper::_cfg('debug'))
                    Timer::mark('call_controller[_processGetModelAction]');

                // call controller
                $obj = new $controller();

                if(!method_exists($obj, $actMethod))
                    trigger_error('Call to undefined method '.$controller.'::'.$actMethod.'()');

                $view = call_user_func_array([$obj, $actMethod], [$this->container->get('request')]);

                // timer mark
                if(Helper::_cfg('debug'))
                    Timer::time('call_controller[_processGetModelAction]');

                $templates = $this->getTemplateFromView($viewPath.$view->fileName);

                // if view has templates - get their content
                if(sizeof($templates) > 0){
                    $this->parseTemplateFromView($templates, $view);
                    $this->getTemplateContent($view);
                }

                // get html text from action result
                $view->content = $this->_getFileContent($viewPath.$view->fileName, $view->args);

                // if view has templates - replace template tags substitute for their content
                if(sizeof($templates) > 0){
                    $this->replaceTemplateTags($view);
                }

                // save result into tmp value
                $tmp[$act] = $view->content;
            }
            else{
                trigger_error("Class name: ".__CLASS__);
            }
        }

        // set value
        $this->container->set('_alias_content_action', $tmp);
        $tmp = null;

        // timer mark
        if(Helper::_cfg('debug'))
            Timer::time('_processGetModelAction');
    }


    /**
     * Function  search file for construction like: <template name="templateFileName" param1="..." param2="..."> ... </template>
     * 
     * @param    string    $fileName
     * @return   array
     * @access  private
     */
    private function getTemplateFromView($fileName)
    {
        // timer mark
        if(Helper::_cfg('debug'))
            Timer::mark('getTemplateFromView');

        $fileContent = file_get_contents($fileName);
        if(!preg_match_all('{([<]{1}template([^<]*)[>]{1})(.*)([/<]{2}template[>]{1})}imsU', $fileContent, $templates))
            $templates = [];

        // timer mark
        if(Helper::_cfg('debug'))
            Timer::time('getTemplateFromView');

        return $templates;
    }


    /**
     * Function  parse <template param1="..." param2="..."> ... </template>
     * 
     * @param    array     $templates
     * @param    object    $view
     * @return   void
     * @access   private
     */
    private function parseTemplateFromView($templates = [], &$view = FALSE)
    {
        // timer mark
        if(Helper::_cfg('debug'))
            Timer::time('parseTemplateFromView');

        if(sizeof($templates) === 0)
            trigger_error("Class name: ".__CLASS__.'  function: '.__FUNCTION__.'  \$templates must be array!');
        if(!$view)
            trigger_error("Class name: ".__CLASS__.'  function: '.__FUNCTION__.'  \$view is required!');

        foreach($templates as $key1=>$t){
            if($key1 === 1){
                // get tag of template
                foreach($t as $key2=>$r){
                    $view->templates[$key2]['templateTag'] = $r;
                }
            }
            elseif($key1 === 2){
                // get params of template from file in View
                foreach($t as $key2=>$r){
                    preg_match_all('/([[:alnum:]_]+)=[\'|"]?([[:alnum:]_]+)[\'|"]?/i', $r, $vars);
                    for($i = 0; $i < sizeof($vars[0]); $i++){
                        // param 'name' is a file name of template
                        if($vars[1][$i] === 'name')
                            $view->templates[$key2]['templateFileName'] = './'.ucfirst($this->getArea()).'/View/_template/'.$vars[2][$i].'.php';

                        $view->templates[$key2]['args'][$vars[1][$i]] = $vars[2][$i];
                    }
                }
            }
            elseif($key1 === 3){
                // if template has content - set 'true', else - set 'false';
                foreach($t as $key2=>$r){
                    if(trim($r) === ''){
                        $view->templates[$key2]['templateTag'] = $templates[0][$key2];
                        $view->templates[$key2]['hasContent'] = false;
                    }
                    else
                        $view->templates[$key2]['hasContent'] = true;
                }
            }
        }

        // timer mark
        if(Helper::_cfg('debug'))
            Timer::time('parseTemplateFromView');

        return 0;
    }


    /**
     * Function get content from template file
     *
     * @param    object    $view
     * @return   void
     * @access  private
     */
    private function getTemplateContent(&$view)
    {
        // timer mark
        if(Helper::_cfg('debug'))
            Timer::mark('getTemplateContent');

        foreach($view->templates as &$t){
            $path = Helper::_cfg('path_src').$t['templateFileName'];
            $t['contentText'] = $this->_getFileContent($path, $t['args']);

            if($t['hasContent'] === true){
                $content = explode('<content></content>', $t['contentText']);
                $t['contentHeader'] = $content[0];
                $t['contentFooter'] = $content[1];
                unset($t['contentText']);
            }
        }

        // timer mark
        if(Helper::_cfg('debug'))
            Timer::time('getTemplateContent');
    }


    /**
     * Function search templates tags and substitute for template content
     *
     * @param    object    $view
     * @return   void
     * @access  private
     */
    private function replaceTemplateTags(&$view)
    {
        // timer mark
        if(Helper::_cfg('debug'))
            Timer::mark('replaceTemplateTags');

        foreach($view->templates as &$t){
            if($t['hasContent'] === false){
                $view->content = str_replace($t['templateTag'], $t['contentText'], $view->content);
            }
            else{
                $pattern = '/('.$t['templateTag'].')(.*)(</template>)/imsU';
                $replacement = $t['contentHeader'].' $2 '.$t['contentFooter'];
                $view->content = preg_replace($pattern, $replacement, $view->content);
            }
        }
        unset($view->templates);

        // timer mark
        if(Helper::_cfg('debug'))
            Timer::time('replaceTemplateTags');
    }


    /**
     * Function get content from file with real param
     *
     * @param    string    $file
     * @param    array     $vars
     * @return   string
     * @access  private
     */
    private function _getFileContent($file, $vars = [])
    {
        $url = new Url();
        $currentUrl = $url->getUrl(TRUE);
        $vars['currentUrl'] = $currentUrl;

        if(!file_exists($file))
            trigger_error("Class name:".__CLASS__.'  function: '.__FUNCTION__.'  File '.$file.' not exist.');

        extract($vars, EXTR_OVERWRITE);
        ob_start();
        require $file;
        $string = ob_get_contents();
        ob_end_clean();
        return $string;
    }


    /**
     * Function set charset for site (for php and html page, not for mysql)
     *
     * @param   boolean $charset
     * @return  void
     * @access  private
     */
    private function _setHeaderCharset($charset = FALSE)
    {
        if(!$charset)
            $charset = Helper::_cfg('page_charset');
        header("Content-Type: text/html; charset=".$charset);
    }


    /**
     * Function set $this->fullText
     *
     * @return   void
     * @access  private
     */
    private function _getFullText()
    {
        $file = Helper::_cfg('path_src').$this->container->get('_layout');
        $array = [
            'layoutContent' => $this->container->get('_layoutContent'),
        ];

        // if debug On - show debug div
        if(Helper::_cfg('debug')){
            $debugTemplateFile = Helper::_cfg('path_src').'/Common/View/_template/debug.php';

            // timer mark
            Timer::time('globalStart');

            $debug = [
                'sql'     => Helper::_debugStore('sql_log'),
                'request' => Helper::_debugStore('request'),
                'timer'   => Helper::_debugStore('timer'),
            ];

            $array['debug'] = $this->_getFileContent($debugTemplateFile, $debug);
        }

        // compile view
        $layoutFullText = $this->_getFileContent($file, $array);

        // parse view tpl
        // if view has templates - get their content
        $templates = $this->getTemplateFromView($file);
        if(sizeof($templates) > 0){
            $view = new View();
            $this->parseTemplateFromView($templates, $view);
            $this->getTemplateContent($view);

            foreach ($view->templates as $t) {
                $layoutFullText = str_replace($t['templateTag'], $t['contentText'], $layoutFullText);
            }
        }

        $this->container->set('fullText', $layoutFullText);
    }


    /**
     * Function display all content (layout+views)
     *
     * @return   string
     * @access   private
     */
    public function display()
    {
        echo $this->container->get('fullText');
        return;
    }


    /**
     * [setArea description]
     * @param string $area [description]
     */
    public function setArea($area = 'public')
    {
        $this->container->set('_area', $area);
    }


    /**
     * [getArea description]
     * @return    string
     * @access    public
     */
    public function getArea()
    {
        return $this->container->get('_area');
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
        $this->container->set($key, $value);
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
        return $this->container->get($key);
    }


    /**
     * [setSrcNamespace description]
     * @param string $namespace [description]
     */
    public function setSrcNamespace($namespace = 'Src')
    {
        $this->container->set('_srcNamespace', $namespace);
    }


    /**
     * [getSrcNamespace description]
     * @return    string
     * @access    public
     */
    public function getSrcNamespace()
    {
        return $this->container->get('_srcNamespace');
    }
}