<?php
/**
 * The file contains class Validator()
 */
namespace Katran\Library;


use Katran\Helper;

/**
 * This class check received form for correctly input information.
 * 
 * @version 2012-07-07
 * @package	Libraries
 */
class Validator
{
    /**
     * Array of errors
     * var array
     */
    private $errors = array();

    /**
     * Array of income fields
     * var array
     */
    private $fields = array();

    /**
     * Array of rules
     * var array
     */
    private $rules = array();


    /**
     * Set Fields
     * This function get input data from form and save into $fields
     *
     * @param    object    $request
     * @return   void
     * @access   public
     */
    public function setFields($request)
    {
        if(is_object($request))
            $this->fields = $request->getArgs();
        elseif(is_array($request))
            $this->fields = $request;
    }


    /**
     * Set Rules
     * This function takes an array of field names and validation rules as input.
     *
     * @param	mixed    $field
     * @param	string   $label
     * @param	string   $rules
     * @return	void
     * @access	public
     */
    public function setRules($field, $label = '', $rules = '')
    {
        if (count($this->fields) === 0)
            return $this;

        // If an array was passed via the first parameter instead of individual string
        // values we cycle through it and recursively call this function.
        if (is_array($field)){
            foreach ($field as $row){
                if (!isset($row[0]) || !isset($row[2]))
                    continue;

                // If the field label wasn't passed we use the field name
                $label = (!isset($row[1]))?$row[0] : $row[1];

                $this->setRules(trim($row[0]), trim($label), trim($row[2]));
            }
            return 1;
        }

        if (!is_string($field) || !is_string($rules) || $field == '')
            return $this;

        // If the field label wasn't passed we use the field name
        $label = ($label == '') ? trim($field) : trim($label);

        // parse $rules on many rules '|'
        $rule  = explode('|', trim($rules));

        // We test for the existence of a bracket "[" in the rules.
        foreach($rule as &$r){
            if (strpos($r, '[') !== FALSE && preg_match_all('/\[(.*?)\]/', $r, $matches)){
                $pos = strpos($r, '[');
                $r = array(substr($r, 0, $pos), substr($r, $pos+1, -1));
            }
        }

        // Build our master array
        $this->rules[$field] = array(
            'field'     => $field,
            'label'	    => $label,
            'rule'	    => $rule,
            'errors'     => array()
        );
        return 1;
    }


    /**
     * Set Error Message
     *
     * @param    string    $label
     * @param    mixed     $rule
     * @param    string    $key
     * @return   void
     * @access   private
     */
    private function setError($label = '', $rule = '', $key = '')
    {
        if(is_array($rule))
            $error = sprintf(Helper::_msg($rule[0]), $label, $rule[1]);
        else
            $error = sprintf(Helper::_msg($rule), $label);

        $this->errors[$key] = $error;
    }


    /**
     * Return All Errors Message
     *
     * @param    boolean    $withKey
     * @return   array
     * @access   public
     */
    public function getErrors($withKey = false)
    {
        if($withKey)
            $errors = $this->errors;
        else
            $errors = array_values($this->errors);

        return $errors;
    }


    /**
     * Return 1 if validator has some errors, else - 0
     *
     * @return   array
     * @access   public
     */
    public function hasErrors()
    {
        return sizeof($this->errors);
    }


    /**
     * Run the Validator
     * This function does all the work.
     *
     * @return	boolean
     * @access	public
     */
    public function run()
    {
        // If no validation rules
        if (count($this->rules) === 0)
            return FALSE;

        // Cycle apply rules for each field
        foreach ($this->rules as $r){
            // Check, if we have field for this rule...
            if (preg_match('/([[:word:].]+)\[([[:word:].]+)\]/i', $r['field'], $field)){
                // if we have field for rule - check rule, else - set error text
            	if(isset($this->fields[$field[1]][$field[2]])){
                    $this->check($this->fields[$field[1]][$field[2]], $r);
                }
                else{
                    foreach($r['rule'] as $rule){
                        if($rule === 'required'){
                            $this->setError($r['label'], 'required', $r['field']);
                            break;
                        }
                    }
                }
            }
            else{
                // if we have field for rule - check rule, else - set error text
            	if(isset($this->fields[$r['field']])){
                    $this->check($this->fields[$r['field']], $r);
                }
                else{
                    foreach($r['rule'] as $rule){
                        if($rule === 'required'){
                            $this->setError($r['label'], 'required', $r['field']);
                            break;
                        }
                    }
                }
            }
        }

        // if has errors - return false
        return !(count($this->errors));
    }


    /**
     * Check the Validation rules
     *
     * @param    mixed    $data
     * @param    array    $rules
     * @return   mixed
     * @access   private
     */
    private function check($data, $rules)
    {
        // Cycle check each rule and run it
        foreach ($rules['rule'] as $r){
            if(is_array($r)){
                if (!method_exists($this, $r[0]))
                    continue;
                $result = $this->$r[0]($data, $r[1]);
            }
            else{
                if (!method_exists($this, $r))
                    continue;
                $result = $this->$r($data);
            }

            // If the rule test negatively.
            if ($result === FALSE){
                $this->setError($rules['label'], $r, $rules['field']);
            }
            elseif($result !== TRUE){
                $this->setError($rules['label'], array($r[0], $result), $rules['field']);
            }
        }
    }


    /**
     * Required
     *
     * @param     string    $str
     * @return    bool
     * @access    private
     */
    private function required($str)
    {
        return strlen(trim($str)) > 0;
    }


    /**
     * Trim
     *
     * @param     string    $str
     * @return    bool
     * @access    private
     */
    private function trim(&$str)
    {
        $str = trim($str);
        return TRUE;
    }


    /**
     * Performs a Regular Expression match test.
     *
     * @param    string    $str
     * @param    regex     $regex
     * @return   bool
     * @access   private
     */
    function regex_match($str, $regex)
    {
        return preg_match($regex, $str);
    }


    /**
     * Match one field to another
     *
     * @param     string    $str
     * @param     string    $field
     * @return    bool
     * @access    private
     */
    private function match($str, $field)
    {
        if (isset($this->rules[$field]['label']))
            $label = $this->rules[$field]['label'];
        else
            $label = $field;

        // Cycle search field
        foreach ($this->fields as $key=>$f)
        {
            if(is_array($f)){
                foreach ($f as $key_2=>$d)
                {
                    if($key.'['.$key_2.']' === $field){
                        if($str == $d)
                            return TRUE;
                    }
                }
            }
            else{
                if($key === $field){
                    if($str == $f)
                        return TRUE;
                }
            }
        }
        return $label;
    }


    /**
     * Minimum Length
     *
     * @param     string    $str
     * @param     length    $val
     * @return    bool
     * @access    private
     */
    private function min_length($str, $val)
    {
        if (function_exists('mb_strlen'))
            return (mb_strlen($str, Helper::_cfg('page_charset')) < $val) ? FALSE : TRUE;

        return (strlen($str) < $val) ? FALSE : TRUE;
    }


    /**
     * Max Length
     *
     * @param     string    $str
     * @param     length    $val
     * @return    bool
     * @access    private
     */
    private function max_length($str, $val)
    {
        if (function_exists('mb_strlen'))
            return (mb_strlen($str, Helper::_cfg('page_charset')) > $val) ? FALSE : TRUE;

        return (strlen($str) > $val) ? FALSE : TRUE;
    }


    /**
     * Exact Length
     *
     * @param     string    $str
     * @param     length    $val
     * @return    bool
     * @access    private
     */
    function exact_length($str, $val)
    {
        if (function_exists('mb_strlen'))
            return (mb_strlen($str, Helper::_cfg('page_charset')) != $val) ? FALSE : TRUE;

        return (strlen($str) != $val) ? FALSE : TRUE;
    }


    /**
     * Valid Email
     *
     * @param     string    $str
     * @return    bool
     * @access    private
     */
    private function email($str)
    {
        return (!preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $str)) ? FALSE : TRUE;
    }


    /**
     * Valid Emails
     *
     * @param     string    $str
     * @return    bool
     * @access    private
     */
    private function emails($str)
    {
        if (strpos($str, ',') === FALSE)
            return $this->email(trim($str));

        foreach (explode(',', $str) as $email){
            if (trim($email) != '' && $this->email(trim($email)) === FALSE)
                return FALSE;
        }
        return TRUE;
    }


    /**
     * Validate IP Address
     *
     * @param     string    $ip
     * @return    bool
     * @access    private
     */
    private function valid_ip($ip)
    {
        $ip_segments = explode('.', $ip);

        // Always 4 segments needed
        if (count($ip_segments) != 4)
            return FALSE;

        // IP can not start with 0
        if ($ip_segments[0][0] == '0')
            return FALSE;

        // Check each segment
        foreach ($ip_segments as $segment)
        {
            // IP segments must be digits and can not be
            // longer than 3 digits or greater then 255
            if ($segment == '' || preg_match("/[^0-9]/", $segment) || $segment > 255 || strlen($segment) > 3)
                return FALSE;
        }
        return TRUE;
    }


    /**
     * Alpha
     *
     * @param     string    $str
     * @return    bool
     * @access    private
     */
    private function alpha($str)
    {
        return ( ! preg_match("/^([a-z])+$/i", $str)) ? FALSE : TRUE;
    }


    /**
     * Alpha-numeric
     *
     * @param     string    $str
     * @return    bool
     * @access    private
     */
    private function alpha_numeric($str)
    {
        return ( ! preg_match("/^([a-z0-9])+$/i", $str)) ? FALSE : TRUE;
    }


    /**
     * Alpha-numeric with underscores and dashes
     *
     * @param     string    $str
     * @return    bool
     * @access    private
     */
    private function alpha_dash($str)
    {
        return ( ! preg_match("/^([-a-z0-9_-])+$/i", $str)) ? FALSE : TRUE;
    }


    /**
     * Numeric
     *
     * @param     string    $str
     * @return    bool
     * @access    private
     */
    private function numeric($str)
    {
        return (bool)preg_match( '/^[\-+]?[0-9]*\.?[0-9]+$/', $str);
    }


    /**
     * Is Numeric
     *
     * @param     mixed    $str
     * @return    bool
     * @access    private
     */
    private function is_numeric($str)
    {
        return ( ! is_numeric($str)) ? FALSE : TRUE;
    }


    /**
     * Integer
     *
     * @param     mixed    $str
     * @return    bool
     * @access    private
     */
    private function integer($str)
    {
        return (bool) preg_match('/^[\-+]?[0-9]+$/', $str);
    }


    /**
     * Decimal number
     *
     * @param     mixed    $str
     * @return    bool
     * @access    private
     */
    private function decimal($str)
    {
        return (bool) preg_match('/^[\-+]?[0-9]+\.[0-9]+$/', $str);
    }


    /**
     * Greather than
     *
     * @param     mixed    $str
     * @param     int      $min
     * @return    bool
     * @access    private
     */
    private function more_than($str, $min)
    {
        if ( ! is_numeric($str))
            return FALSE;

        return $str > $min;
    }


    /**
     * Less than
     *
     * @param     mixed    $str
     * @param     int      $max
     * @return    bool
     * @access    private
     */
    private function less_than($str, $max)
    {
        if ( ! is_numeric($str))
            return FALSE;

        return $str < $max;
    }


    /**
     * Is a Natural number  (0,1,2,3, etc.)
     *
     * @param     mixed    $str
     * @return    bool
     * @access    private
     */
    private function is_natural($str)
    {
        return (bool) preg_match( '/^[0-9]+$/', $str);
    }


    /**
     * Is a Natural number, but not a zero  (1,2,3, etc.)
     *
     * @param     mixed    $str
     * @return    bool
     * @access    private
     */
    private function is_natural_no_zero($str)
    {
        if ( ! preg_match( '/^[0-9]+$/', $str))
            return FALSE;

        if ($str == 0)
            return FALSE;

        return TRUE;
    }


    /**
     * Valid Base64
     *
     * Tests a string for characters outside of the Base64 alphabet
     * as defined by RFC 2045 http://www.faqs.org/rfcs/rfc2045
     *
     * @param     mixed    $str
     * @return    bool
     * @access    private
     */
    private function valid_base64($str)
    {
        return (bool) ! preg_match('/[^a-zA-Z0-9\/\+=]/', $str);
    }


    /**
     * Prepare URL
     *
     * @param     mixed    $str
     * @return    bool
     * @access    private
     */
    private function prep_url(&$str)
    {
        if ($str == 'http://' OR $str == '')
            $srt = '';

        if (substr($str, 0, 7) != 'http://' && substr($str, 0, 8) != 'https://')
            $str = 'http://'.$str;

        return TRUE;
    }


    /**
     * Check field if it's time
     *
     * @param     mixed    $str
     * @return    bool
     * @access    private
     */
    private function time($str)
    {
        $temp = explode(':', $str);

        if(sizeof($temp) == 1)
            return FALSE;

        foreach($temp as $key=>$t){
            if($key == 0)
                if(($t < 0 ) || ($t >= 24 ))
                    return FALSE;
            else
                if(($t < 0 ) || ($t > 60 ))
                    return FALSE;
        }

        return TRUE;
    }
}