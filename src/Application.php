<?php
/**
 * The file contains class Application() extends Timer()
 */
namespace Katran;

use Katran\Library\Timer;

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
        $request = new Request();
        $this->container->set('request', $request);

        // set controller/action if exist
        if($_controller = $request->getParam('controller', false))
            $this->container->set('_controller', $_controller);
        if($_action = $request->getParam('action', false))
            $this->container->set('_action', $_action);

        // if form was send, and submit has special name
        $submitBtn = $request->getParam('submit');
        if( !empty($submitBtn) && is_array($submitBtn)){
            $submit = explode('|', key($submitBtn));
            $this->container->set('_controller', $submit[0]);
            $this->container->set('_action', $submit[1]);
        }

        // if debug On save request into debug store
        if(Helper::_cfg('debug'))
            Helper::_debugStore('request', $this->container->get('request'));
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
     * [setAliasPage description]
     * @param string $alias_page
     */
    public function setAliasPage($alias_page = '')
    {
        $this->container->set('_aliasPage', $alias_page);
    }


    /**
     * Function analise URL and select any action
     *
     * @return   void
     * @access  public
     */
    public function process()
    {
        // set alias value if exist, or set default `index`
        $this->container->set('_alias', $this->container->get('request')->getString('alias', 'index'));

        switch ($this->container->get('_area')) {
            case 'admin':
                // default actions
                if(!isset($_SESSION['admin']) && !in_array($this->container->get('_action'), ['login', 'do_login'])){
                    $this->container->set('_controller', 'account');
                    $this->container->set('_action', 'login');
                }
                elseif(isset($_SESSION['admin']) && !$this->container->get('_controller')){
                    $this->container->set('_controller', 'account');
                    $this->container->set('_action', 'stat');
                }
                break;
            default:
                if(!isset($_SESSION['visitor'])){
                    $_SESSION['visitor']['id'] = 0;
                    $_SESSION['visitor']['fname'] = 'Гость';
                    $_SESSION['visitor']['lname'] = '';
                }
                break;
        }

        // set default layout
        $this->setLayout('layout_'.$this->container->get('_area'));

        // run process
        if($this->container->get('_controller'))
            $this->_processMod();
        elseif($this->container->get('_alias'))
            $this->_processAlias();
        else
            $this->redirectPage('/404');

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
            $this->redirectPage('/404');
        elseif($rights === 0){
            // access off
            if(empty($_SESSION[$this->container->get('_area')]))
                $this->redirectPage('/401');
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
        $namespace      = '\\'.Helper::_cfg('namespace').'\\'.$area.'\\Controller\\'.ucfirst($this->container->get('_controller'));
        $classFilePath  = Helper::_cfg('path_src').'/'.$area.'/Controller/'.ucfirst($this->container->get('_controller')).'.php';
        $viewPath       = Helper::_cfg('path_src').'/'.$area.'/View/';
        $actMethod      = str_replace('_', '', $this->container->get('_action')).'Action';

        if(!file_exists($classFilePath))
            trigger_error('File: <em>'.$classFilePath.'</em>. Not found.');

        // timer mark
        if(Helper::_cfg('debug'))
            Timer::mark('call_controller[_processMod]');

        // call controller
        $obj = new $namespace();

        if(!method_exists($obj, $actMethod))
            trigger_error('Call to undefined method '.$namespace.'::'.$actMethod.'()');

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
        $this->container->set('_aliasContentText', $view->content);
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

        // read directory and save results into tmp variable $ok_files
        $ok_files = array();
        $dir = opendir(Helper::_cfg('path_src').'/tpl/alias/');
        while(($f = readdir($dir)) !== FALSE){
            if(strstr($f, $page) !== FALSE)
                $ok_files[] = $f;
        }

        // foreach all matches
        $res = -1;
        $matches_lingth = sizeof($ok_files);
        if($matches_lingth > 0){
            for($i = 0; $i < $matches_lingth; ++$i){
                // full match - return 'ok'
                if($ok_files[$i] === $page){
                    $res = 1;
                    $this->container->set('_aliasPage', $ok_files[$i]);
                    break;
                }
                elseif ($ok_files[$i] === $area.'_'.$page) {
                    $res = 0;
                    $this->container->set('_aliasPage', $ok_files[$i]);
                    break;
                }
            }
        }
        elseif(file_exists(Helper::_cfg('path_src').'/tpl/alias/default.php')){
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
    public function setLayout($layout)
    {
        $this->container->set('_layout', $layout);
    }


    /**
     * Function get content from file '/app/tpl/alias/'.$this->container->get('_alias').'.php'
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
        $file = Helper::_cfg('path_src').'/tpl/alias/'.$this->container->get('_aliasPage');

        if($debugErrorText){
            $vars = array('error' => $debugErrorText);
            $this->container->set('_aliasContentText', $this->_getFileContent($file, $vars));
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

            $this->container->set('_aliasContentText', $this->container->get('_alias_content_file'));
        }
        else{
            $vars = array();
            $this->container->set('_aliasContentText', $this->_getFileContent($file, $vars));
        }

        // timer mark
        if(Helper::_cfg('debug'))
            Timer::time('getAliasContent');
    }


    /**
     * Function parse file '/app/tpl/alias/'.$this->container->get('_alias').'.php'
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
    private function _processGetModelAction($action = array())
    {
        // timer mark
        if(Helper::_cfg('debug'))
            Timer::mark('_processGetModelAction');

        $tmp = [];
        foreach($action as $key=>$act){
            preg_match('/(controller=[\'|"]?([[:alnum:]_]+)[\'|"]?)[\s]*(action=[\'|"]?([[:alnum:]_]+)[\'|"]?)/i', $act, $controller);

            // create controller object (if exists)
            if(sizeof($controller) === 5){
                $area           = ucfirst($this->container->get('_area'));
                $namespace      = '\\'.Helper::_cfg('namespace').'\\'.$area.'\\Controller\\'.ucfirst($controller[2]);
                $classFilePath  = Helper::_cfg('path_src').'/'.$area.'/Controller/'.ucfirst($controller[2]).'.php';
                $viewPath       = Helper::_cfg('path_src').'/'.$area.'/View/';
                $actMethod      = str_replace('_', '', $controller[4]).'Action';

                if(!file_exists($classFilePath))
                    trigger_error('File: <em>'.$classFilePath.'</em>. Not found.');

                // timer mark
                if(Helper::_cfg('debug'))
                    Timer::mark('call_controller[_processGetModelAction]');

                // call controller
                $obj = new $namespace();

                if(!method_exists($obj, $actMethod))
                    trigger_error('Call to undefined method '.$namespace.'::'.$actMethod.'()');

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
    private function parseTemplateFromView($templates = array(), &$view = FALSE)
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
                            $view->templates[$key2]['templateFileName'] = $vars[2][$i].'.php';

                        $view->templates[$key2]['args'][$vars[1][$i]] = $vars[2][$i];
                    }
                }
            }
            elseif($key1 === 3){
                // if template has content - set '1', else - set '0';
                foreach($t as $key2=>$r){
                    if(trim($r) === ''){
                        $view->templates[$key2]['templateTag'] = $templates[0][$key2];
                        $view->templates[$key2]['hasContent'] = '0';
                    }
                    else
                        $view->templates[$key2]['hasContent'] = '1';
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
            $t['contentText'] = $this->_getFileContent(Helper::_cfg('path_src').'/tpl/template/'.$t['templateFileName'], $t['args']);
            if($t['hasContent'] === '1'){
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
            if($t['hasContent'] === '0'){
                $view->content = str_replace($t['templateTag'], $t['contentText'], $view->content);
            }
            else{
                $pattern = '{('.$t['templateTag'].')(.*)(</template>)}imsU';
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
    private function _getFileContent($file, $vars = array())
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
        $file = Helper::_cfg('path_src').'/tpl/'.$this->container->get('_layout').'.php';
        $array = array('layoutContent' => $this->container->get('_aliasContentText'));

        // if debug On - show debug div
        if(Helper::_cfg('debug')){
            $debug_file = Helper::_cfg('path_src').'/tpl/template/debug.php';

            // timer mark
            Timer::time('globalStart');

            $debug = [
                'sql'     => Helper::_debugStore('sql_log'),
                'request' => Helper::_debugStore('request'),
                'timer'   => Helper::_debugStore('timer'),
            ];

            $debug_str = $this->_getFileContent($debug_file, $debug);
            $array['debug'] = $debug_str;
        }

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
}