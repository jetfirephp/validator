<?php

namespace JetFire\Validator;

use DateTime;


/**
 * Class Validator
 * @package JetFire\Validator
 */
class Validator
{

    /**
     * @var
     */
    private $rule;
    /**
     * @var
     */
    private $params;
    /**
     * @var
     */
    private $skip;
    /**
     * @var
     */
    private $customMessages;
    /**
     * @var array
     */
    private $request = [];
    /**
     * @var array
     */
    private $response = [];
    /**
     * @var
     */
    private $rules = [];

    /**
     * @var Validator
     */
    private static $instance = null;

    /**
     * Validator constructor.
     */
    public function __construct()
    {
        self::$instance = $this;
    }

    /**
     * @return Validator
     */
    public static function getInstance()
    {
        return is_null(self::$instance) ? new self : self::$instance;
    }

    /**
     * @param $key
     * @return null|array|string|int
     */
    private function get($key)
    {
        return $this->getRecursive($key, $_GET);
    }

    /**
     * @param $key
     * @return null|array|string|int
     */
    private function post($key)
    {
        return $this->getRecursive($key, $_POST);
    }

    /**
     * @param $key
     * @return null|array|string|int
     */
    private function file($key)
    {
        return $this->getRecursive($key, $_FILES);
    }

    /**
     * @param $key
     * @param $value
     * @return null
     */
    public function getRecursive($key, $value)
    {
        $keys = explode('.', $key);
        foreach ($keys as $k)
            $value = isset($value[$k]) ? $value[$k] : null;
        return $value;
    }

    /**
     * @param array $response
     * @param $key
     * @param $val
     * @return mixed
     */
    public function setRecursive(&$response = [], $key, $val)
    {
        $loc = &$response;
        $keys = explode('.', $key);
        foreach ($keys as $k)
            $loc = &$loc[$k];
        if (count($keys) > 1) unset($response[$key]);
        return $loc = $val;
    }


    /**
     * @description validate values
     * @param array $all
     * @param null $customMessages
     * @param null $requests
     * @return array
     */
    public function validate($all = [], $customMessages = null, $requests = null)
    {
        $this->customMessages = $customMessages;
        (is_null($requests)) ? $this->getValues($all) : $this->setValues($requests);
        foreach ($all as $label => $rule) {
            $this->rule = preg_split('/(\||\+)/', $rule);
            $this->params = explode('|', $label);
            $this->make();
        }
        return $this->response();
    }

    /**
     * @description validate $_POST values
     * @param array $all
     * @param null $customMessages
     * @return array
     */
    public function validatePost($all = [], $customMessages = null)
    {
        $this->customMessages = $customMessages;
        $this->getValues($all, 'post');
        foreach ($all as $label => $rule) {
            $this->rule = preg_split('/(\||\+)/', $rule);
            $this->params = explode('|', $label);
            $this->make();
        }
        return $this->response();
    }

    /**
     * @description validate $_GET values
     * @param array $all
     * @param null $customMessages
     * @return array
     */
    public function validateGet($all = [], $customMessages = null)
    {
        $this->customMessages = $customMessages;
        $this->getValues($all, 'get');
        foreach ($all as $label => $rule) {
            $this->rule = preg_split('/(\||\+)/', $rule);
            $this->params = explode('|', $label);
            $this->make();
        }
        return $this->response();
    }

    /**
     * @param $values
     */
    private function setValues($values)
    {
        foreach ($values as $key => $value)
            if (!is_null($this->get($key))) $this->request[$key] = $value;
    }

    /**
     * @description recover all values in request variable
     * @param $rules
     * @param string $type
     */
    private function getValues($rules, $type = 'default')
    {
        switch ($type) {
            case 'default':
                foreach ($rules as $label => $rule) {
                    $params = explode('|', $label);
                    foreach ($params as $param) {
                        $key = strstr($param, '::', true);
                        empty($key) ? $this->request[$param] = $param : $this->request[$key] = str_replace($key . '::', '', $param);
                    }
                }
                break;
            case 'get':
                foreach ($rules as $label => $rule) {
                    $params = explode('|', $label);
                    foreach ($params as $param) {
                        $get = $this->get($param);
                        if (!is_null($get)) $this->request[$param] = $get;
                    }
                }
                break;
            case 'post':
                foreach ($rules as $label => $rule) {
                    $params = explode('|', $label);
                    foreach ($params as $param) {
                        $post = $this->post($param);
                        $file = $this->file($param);
                        if (!is_null($post)) $this->request[$param] = $post;
                        else if (!is_null($file)) $this->request[$param] = $file;
                    }
                }
                break;
        }
    }


    /**
     *
     */
    private function make()
    {
        foreach ($this->params as $key1 => $param) {
            $this->skip = false;
            $key = strstr($param, '::', true);
            $param = (empty($key)) ? $param : $key;
            foreach ($this->rule as $key2 => $rule) {
                if ($this->skip === true) break;
                $exec = explode(':', $rule);
                $parameters = [];
                if (!empty($exec[1])) $parameters[$exec[0]] = $exec[1];
                if (isset($this->rules[$exec[0]])) {
                    $response = (!isset($parameters[$exec[0]])) ? call_user_func_array($this->rules[$exec[0]], [$this->request, $param]) : call_user_func_array($this->rules[$exec[0]], [$this->request, $param, $parameters]);
                } else {
                    $response = (!isset($parameters[$exec[0]])) ? call_user_func_array([$this, $exec[0]], [$param]) : call_user_func_array([$this, $exec[0]], [$param, $parameters]);
                }
                if (is_string($response)) $this->response[$param][$exec[0]] = $response;
            }
        }
    }

    /**
     * @return array
     */
    private function response()
    {
        if (!is_null($this->customMessages)) {
            $messages = $this->customMessages;
            foreach ($this->response as $field => $rules) {
                foreach ($rules as $rule => $message) {
                    if (isset($messages[$rule . ':' . $field])) {
                        $this->response[$field][$rule] = str_replace(':field', '"' . $field . '"', $messages[$rule . ':' . $field]);
                    } else if (isset($messages[$rule])) {
                        $this->response[$field][$rule] = str_replace(':field', '"' . $field . '"', $messages[$rule]);
                    }
                }
            }
        }
        if (!empty($this->response)) return ['valid' => false, 'status' => 'error', 'message' => $this->response];
        else {
            foreach ($this->request as $key => $message) {
                $this->setRecursive($this->request, $key, $message);
            }
            return ['valid' => true, 'status' => 'success', 'values' => $this->request];
        }
    }


    /**
     * @param $rule
     * @param $function
     */
    public function addRule($rule, $function)
    {
        $this->rules[$rule] = $function;
    }

    /**
     * @param $rules
     */
    public function addRules($rules)
    {
        $this->rules = (is_array($rules)) ? $rules : include($rules);
    }

    /**
     * @param $param
     * @param $parameters
     * @return bool
     */
    public function regex($param, $parameters)
    {
        if (!empty($parameters['regex'])) {
            return (preg_match(str_replace('`OR`', '|', $parameters['regex']), $this->request[$param]))
                ? true
                : $this->response[$param]['regex'] = '"' . $param . '"  must validate against "' . $parameters['regex'] . '"';
        }
        return true;
    }

    /**
     * @param $param
     * @return bool
     */
    public function alpha($param)
    {
        return (ctype_alpha(str_replace(' ', '', $this->request[$param])))
            ? true
            : $this->response[$param]['alpha'] = '"' . $param . '"  must contain only letters (a-z)';
    }

    /**
     * @param $param
     * @return bool
     */
    public function alnum($param)
    {
        return (ctype_alnum(str_replace(' ', '', $this->request[$param])))
            ? true
            : $this->response[$param]['alnum'] = '"' . $param . '"  must contain only letters (a-z) and digits (0-9)';
    }

    /**
     * @param $param
     * @return bool
     */
    public function string($param)
    {
        if (is_string($this->request[$param])) return true;
        return $this->response[$param]['string'] = '"' . $param . '" is not a string';
    }

    /**
     * @param $param
     * @return bool
     */
    public function int($param)
    {
        if (is_numeric($this->request[$param]) && (int)$this->request[$param] == $this->request[$param]) return true;
        return $this->response[$param]['int'] = '"' . $param . '" is not a integer';
    }

    /**
     * @param $param
     * @return bool
     */
    public function numeric($param)
    {
        if (is_numeric($this->request[$param])) return true;
        return $this->response[$param]['numeric'] = '"' . $param . '" is not a numeric';
    }

    /**
     * @param $param
     * @param $parameters
     * @return bool|string
     */
    public function max($param, $parameters)
    {
        return (!empty($parameters['max']) && (int)$this->request[$param] <= (int)$parameters['max'])
            ? true
            : '"' . $param . '" must be lower than "' . $parameters['max'] . '"';
    }

    /**
     * @param $param
     * @param $parameters
     * @return bool|string
     */
    public function min($param, $parameters)
    {
        return (!empty($parameters['min']) && (int)$this->request[$param] >= (int)$parameters['min'])
            ? true
            : '"' . $param . '" must be higher than "' . $parameters['min'] . '"';
    }

    /**
     * @param $param
     * @return bool
     */
    public function url($param)
    {
        if (filter_var($this->request[$param], FILTER_VALIDATE_URL)) return true;
        return $this->response[$param]['url'] = '"' . $param . '" is not a valid url';
    }

    /**
     * @param $param
     * @return bool
     */
    public function boolean($param)
    {
        $param = $this->request[$param];
        if ($param === '1' || $param === '0' || $param === true || $param === false || $param === 1 || $param === 0 || $param == 'true' || $param == 'false') return true;
        return $this->response[$param]['boolean'] = '"' . $param . '" is not a valid boolean';
    }

    /**
     * @param $param
     * @param null $parameters
     * @return bool
     */
    public function date($param, $parameters = null)
    {
        if ($this->request[$param] instanceof DateTime) return true;
        if (DateTime::createFromFormat('Y-m-d G:i:s', $this->request[$param]) !== FALSE) return true;
        if (!empty($parameters['date'])) {
            $exceptionalFormats = array(
                'c' => 'Y-m-d\TH:i:sP',
                'r' => 'D, d M Y H:i:s O',
            );
            if (in_array($parameters['date'], array_keys($exceptionalFormats))) {
                $parameters['date'] = $exceptionalFormats[$parameters['date']];
            }
            $dateFromFormat = DateTime::createFromFormat($parameters['date'], $this->request[$param]);
            if ($dateFromFormat && $this->request[$param] === $dateFromFormat->format($parameters['date'])) return true;
        }
        return $this->response[$param]['date'] = '"' . $param . '" is not a valid date';
    }

    /**
     * @param $param
     * @param null $parameters
     * @return bool
     */
    public function datetime($param, $parameters = null)
    {
        return (!empty($parameters['datetime']))
            ? (DateTime::createFromFormat($parameters['datetime'], $this->request[$param]) !== false)
            : (DateTime::createFromFormat('m/d/Y', $this->request[$param]) !== false);
    }

    /**
     * @param $param
     * @return bool
     */
    public function lowercase($param)
    {
        return ($this->request[$param] === mb_strtolower($this->request[$param], mb_detect_encoding($this->request[$param])))
            ? true
            : $this->response[$param]['lowercase'] = '"' . $param . '" must be lowercase';
    }

    /**
     * @param $param
     * @return bool|string
     */
    public function uppercase($param)
    {
        return (mb_strtoupper($this->request[$param], mb_detect_encoding($this->request[$param])))
            ? true
            : $this->response[$param]['uppercase'] = '"' . $param . '" must be uppercase';
    }

    /**
     * @param $param
     * @return array|bool
     */
    public function noWhitespace($param)
    {
        if (is_null($this->request[$param])) return true;
        if (false === is_scalar($this->request[$param])) return $this->response[$param] = ['noWhitespace' => '"' . $param . '" must not contain whitespace'];
        if (!preg_match('#\s#', $this->request[$param])) return true;
        return $this->response[$param]['noWhitespace'] = '"' . $param . '" must not contain whitespace';
    }

    /**
     * @param $param
     * @return array|bool
     */
    public function email($param)
    {
        if (filter_var($this->request[$param], FILTER_VALIDATE_EMAIL)) return true;
        return $this->response[$param]['email'] = 'The e-mail address format is incorrect for "' . $param . '"';
    }

    /**
     * @param $param
     * @return bool
     */
    public function phone($param)
    {
        if (preg_match('/^[+]?([\d]{0,3})?[\(\.\-\s]?(([\d]{1,3})[\)\.\-\s]*)?(([\d]{3,5})[\.\-\s]?([\d]{4})|([\d]{2}[\.\-\s]?){4})$/', $this->request[$param])) return true;
        return $this->response[$param]['phone'] = 'Incorrect phone number';
    }

    /**
     * @param $param
     * @param null $parameters
     * @return bool
     */
    public function postalCode($param, $parameters = null)
    {
        $postalCodes = ['AD' => "/^(?:AD)*(\d{3})$/", 'AM' => "/^(\d{6})$/", 'AR' => "/^([A-Z]\d{4}[A-Z]{3})$/", 'AT' => "/^(\d{4})$/", 'AU' => "/^(\d{4})$/", 'AX' => "/^(?:FI)*(\d{5})$/", 'AZ' => "/^(?:AZ)*(\d{4})$/", 'BA' => "/^(\d{5})$/", 'BB' => "/^(?:BB)*(\d{5})$/", 'BD' => "/^(\d{4})$/", 'BE' => "/^(\d{4})$/", 'BG' => "/^(\d{4})$/", 'BH' => "/^(\d{3}\d?)$/", 'BM' => "/^([A-Z]{2}\d{2})$/", 'BN' => "/^([A-Z]{2}\d{4})$/", 'BR' => "/^(\d{8}|\d{5}-\d{3})$/", 'BY' => "/^(\d{6})$/", 'CA' => "/^([ABCEGHJKLMNPRSTVXY]\d[ABCEGHJKLMNPRSTVWXYZ]) ?(\d[ABCEGHJKLMNPRSTVWXYZ]\d)$/", 'CH' => "/^(\d{4})$/", 'CL' => "/^(\d{7})$/", 'CN' => "/^(\d{6})$/", 'CR' => "/^(\d{4})$/", 'CS' => "/^(\d{5})$/", 'CU' => "/^(?:CP)*(\d{5})$/", 'CV' => "/^(\d{4})$/", 'CX' => "/^(\d{4})$/", 'CY' => "/^(\d{4})$/", 'CZ' => "/^(\d{5})$/", 'DE' => "/^(\d{5})$/", 'DK' => "/^(\d{4})$/", 'DO' => "/^(\d{5})$/", 'DZ' => "/^(\d{5})$/", 'EC' => "/^([a-zA-Z]\d{4}[a-zA-Z])$/", 'EE' => "/^(\d{5})$/", 'EG' => "/^(\d{5})$/", 'ES' => "/^(\d{5})$/", 'ET' => "/^(\d{4})$/", 'FI' => "/^(?:FI)*(\d{5})$/", 'FM' => "/^(\d{5})$/", 'FO' => "/^(?:FO)*(\d{3})$/", 'FR' => "/^(\d{5})$/", 'GB' => '/^([Gg][Ii][Rr] 0[Aa]{2})|((([A-Za-z][0-9]{1,2})|(([A-Za-z][A-Ha-hJ-Yj-y][0-9]{1,2})|(([A-Za-z][0-9][A-Za-z])|([A-Za-z][A-Ha-hJ-Yj-y][0-9]?[A-Za-z])))) [0-9][A-Za-z]{2})$/', 'GE' => "/^(\d{4})$/", 'GF' => "/^((97|98)3\d{2})$/", 'GG' => "/^(([A-Z]\d{2}[A-Z]{2})|([A-Z]\d{3}[A-Z]{2})|([A-Z]{2}\d{2}[A-Z]{2})|([A-Z]{2}\d{3}[A-Z]{2})|([A-Z]\d[A-Z]\d[A-Z]{2})|([A-Z]{2}\d[A-Z]\d[A-Z]{2})|(GIR0AA))$/", 'GL' => "/^(\d{4})$/", 'GP' => "/^((97|98)\d{3})$/", 'GR' => "/^(\d{5})$/", 'GT' => "/^(\d{5})$/", 'GU' => "/^(969\d{2})$/", 'GW' => "/^(\d{4})$/", 'HN' => "/^([A-Z]{2}\d{4})$/", 'HR' => "/^(?:HR)*(\d{5})$/", 'HT' => "/^(?:HT)*(\d{4})$/", 'HU' => "/^(\d{4})$/", 'ID' => "/^(\d{5})$/", 'IL' => "/^(\d{5})$/", 'IM' => "/^(([A-Z]\d{2}[A-Z]{2})|([A-Z]\d{3}[A-Z]{2})|([A-Z]{2}\d{2}[A-Z]{2})|([A-Z]{2}\d{3}[A-Z]{2})|([A-Z]\d[A-Z]\d[A-Z]{2})|([A-Z]{2}\d[A-Z]\d[A-Z]{2})|(GIR0AA))$/", 'IN' => "/^(\d{6})$/", 'IQ' => "/^(\d{5})$/", 'IR' => "/^(\d{10})$/", 'IS' => "/^(\d{3})$/", 'IT' => "/^(\d{5})$/", 'JE' => "/^(([A-Z]\d{2}[A-Z]{2})|([A-Z]\d{3}[A-Z]{2})|([A-Z]{2}\d{2}[A-Z]{2})|([A-Z]{2}\d{3}[A-Z]{2})|([A-Z]\d[A-Z]\d[A-Z]{2})|([A-Z]{2}\d[A-Z]\d[A-Z]{2})|(GIR0AA))$/", 'JO' => "/^(\d{5})$/", 'JP' => "/^(\d{7})$/", 'KE' => "/^(\d{5})$/", 'KG' => "/^(\d{6})$/", 'KH' => "/^(\d{5})$/", 'KP' => "/^(\d{6})$/", 'KR' => "/^(?:SEOUL)*(\d{6})$/", 'KW' => "/^(\d{5})$/", 'KZ' => "/^(\d{6})$/", 'LA' => "/^(\d{5})$/", 'LB' => "/^(\d{4}(\d{4})?)$/", 'LI' => "/^(\d{4})$/", 'LK' => "/^(\d{5})$/", 'LR' => "/^(\d{4})$/", 'LS' => "/^(\d{3})$/", 'LT' => "/^(?:LT)*(\d{5})$/", 'LU' => "/^(\d{4})$/", 'LV' => "/^(?:LV)*(\d{4})$/", 'MA' => "/^(\d{5})$/", 'MC' => "/^(\d{5})$/", 'MD' => "/^(?:MD)*(\d{4})$/", 'ME' => "/^(\d{5})$/", 'MG' => "/^(\d{3})$/", 'MK' => "/^(\d{4})$/", 'MM' => "/^(\d{5})$/", 'MN' => "/^(\d{6})$/", 'MQ' => "/^(\d{5})$/", 'MT' => "/^([A-Z]{3}\d{2}\d?)$/", 'MV' => "/^(\d{5})$/", 'MX' => "/^(\d{5})$/", 'MY' => "/^(\d{5})$/", 'MZ' => "/^(\d{4})$/", 'NC' => "/^(\d{5})$/", 'NE' => "/^(\d{4})$/", 'NF' => "/^(\d{4})$/", 'NG' => "/^(\d{6})$/", 'NI' => "/^(\d{7})$/", 'NL' => "/^(\d{4}[A-Z]{2})$/", 'NO' => "/^(\d{4})$/", 'NP' => "/^(\d{5})$/", 'NZ' => "/^(\d{4})$/", 'OM' => "/^(\d{3})$/", 'PF' => "/^((97|98)7\d{2})$/", 'PG' => "/^(\d{3})$/", 'PH' => "/^(\d{4})$/", 'PK' => "/^(\d{5})$/", 'PL' => "/^(\d{5})$/", 'PM' => '/^(97500)$/', 'PR' => "/^(\d{9})$/", 'PT' => "/^(\d{7})$/", 'PW' => '/^(96940)$/', 'PY' => "/^(\d{4})$/", 'RE' => "/^((97|98)(4|7|8)\d{2})$/", 'RO' => "/^(\d{6})$/", 'RS' => "/^(\d{6})$/", 'RU' => "/^(\d{6})$/", 'SA' => "/^(\d{5})$/", 'SD' => "/^(\d{5})$/", 'SE' => "/^(?:SE)*(\d{5})$/", 'SG' => "/^(\d{6})$/", 'SH' => '/^(STHL1ZZ)$/', 'SI' => "/^(?:SI)*(\d{4})$/", 'SK' => "/^(\d{5})$/", 'SM' => "/^(4789\d)$/", 'SN' => "/^(\d{5})$/", 'SO' => "/^([A-Z]{2}\d{5})$/", 'SV' => "/^(?:CP)*(\d{4})$/", 'SZ' => "/^([A-Z]\d{3})$/", 'TC' => '/^(TKCA 1ZZ)$/', 'TH' => "/^(\d{5})$/", 'TJ' => "/^(\d{6})$/", 'TM' => "/^(\d{6})$/", 'TN' => "/^(\d{4})$/", 'TR' => "/^(\d{5})$/", 'TW' => "/^(\d{5})$/", 'UA' => "/^(\d{5})$/", 'US' => "/^\d{5}(-\d{4})?$/", 'UY' => "/^(\d{5})$/", 'UZ' => "/^(\d{6})$/", 'VA' => "/^(\d{5})$/", 'VE' => "/^(\d{4})$/", 'VI' => "/^\d{5}(-\d{4})?$/", 'VN' => "/^(\d{6})$/", 'WF' => "/^(986\d{2})$/", 'YT' => "/^(\d{5})$/", 'ZA' => "/^(\d{4})$/", 'ZM' => "/^(\d{5})$/"];
        $regex = '/^$/';
        if (!empty($parameters['postalCode']))
            if (isset($postalCodes[strtoupper($parameters['postalCode'])]))
                $regex = $postalCodes[strtoupper($parameters['postalCode'])];
        if (preg_match($regex, $this->request[$param])) return true;
        return $this->response[$param]['postalCode'] = 'The postal code format is incorrect';
    }

    /**
     * @param $param
     * @param $parameters
     * @return bool
     */
    public function equal($param, $parameters)
    {
        if (!empty($parameters['equal'])) {
            $params = explode(',', $parameters['equal']);
            if (count($params) > 1) {
                if ($params[1] == 'password_verify') {
                    return (password_verify($this->request[$param], $params[0]))
                        ? true
                        : $this->response[$param]['equal'] = '"' . $param . '" value is not equal to "' . $params[0] . '"';
                } else {
                    return ($params[1]($this->request[$param]) == $params[0])
                        ? true
                        : $this->response[$param]['equal'] = '"' . $param . '" value is not equal to "' . $params[0] . '"';
                }
            } else if (count($params) == 1)
                if ($this->request[$param] == $parameters['equal']) return true;
        }
        return $this->response[$param]['equal'] = '"' . $param . '" value is not equal to "' . $parameters['equal'] . '"';
    }

    /**
     * @param $param
     * @param $parameters
     * @return bool
     */
    public function values($param, $parameters)
    {
        if (!empty($parameters['values'])) {
            $params = explode(',', $parameters['values']);
            foreach ($params as $value)
                if ($this->request[$param] == $value) return true;
        }
        return $this->response[$param]['values'] = '"' . $param . '" must contain one of these values : "' . $parameters['values'] . '"';
    }


    /**
     * @param $param
     * @param null $parameters
     * @return array|bool
     */
    public function same($param, $parameters)
    {
        if ($this->request[$param] == $this->request[$parameters['same']]) return true;
        return $this->response[$param]['same'] = '"' . $param . '" and "' . $parameters['same'] . '" are not the same';
    }

    /**
     * @param $param
     * @param $parameters
     * @return bool
     */
    public function length($param, $parameters)
    {
        $params = explode(',', $parameters['length']);
        if (count($params) == 2) {
            if (strlen($this->request[$param]) > intval($params[0]) && strlen($this->request[$param]) < intval($params[1])) return true;
            return $this->response[$param]['length'] = '"' . $param . '" must have a length between "' . $params[0] . '" and "' . $params[1] . '"';
        }
        if ($params[0][0] == '<' || $params[0][0] == '>') {
            if ($this->operate($params[0][0], strlen($this->request[$param]), intval(substr($params[0], 1)))) return true;
            return $this->response[$param]['length'] = '"' . $param . '" must have a length ' . $params[0][0] . ' than "' . substr($params[0], 1) . '"';
        }
        if (strlen($this->request[$param]) === intval($parameters['length'])) return true;
        return $this->response[$param]['length'] = '"' . $param . '" length is not equal to "' . $parameters['length'] . '"';
    }

    /**
     * @param $param
     * @return bool
     */
    public function image($param)
    {
        $format = ['png', 'jpeg', 'jpg', 'gif', 'svg', 'bmp'];
        $extension = pathinfo($this->request[$param]['name'], PATHINFO_EXTENSION);
        if (in_array(strtolower($extension), $format)) {
            $infoImg = getimagesize($this->request[$param]['tmp_name']);
            if ($infoImg[2] >= 1 && $infoImg[2] <= 14) {
                return true;
            }
        }
        return $this->response[$param]['image'] = '"' . $param . '" image format is incorrect';
    }

    /**
     * @param $param
     * @param null $parameters
     * @return bool
     */
    public function format($param, $parameters = null)
    {
        if (!empty($parameters['format']) && isset($this->request[$param]['name'])) {
            $extension = pathinfo($this->request[$param]['name'], PATHINFO_EXTENSION);
            if (in_array(strtolower($extension), explode(',', $parameters['format']))) {
                return true;
            }
        }
        return $this->response[$param]['format'] = '"' . $param . '" file format is incorrect';
    }

    /**
     * @param $param
     * @param null $parameters
     * @return bool
     */
    public function mimes($param, $parameters = null)
    {
        if (!empty($parameters['mimes']) && isset($this->request[$param]['type'])) {
            $mime = $this->request[$param]['type'];
            if ($this->strposa($mime, explode(',', $parameters['mimes'])) !== false) {
                return true;
            }
        }
        return $this->response[$param]['mimes'] = '"' . $param . '" file type is incorrect';
    }

    /**
     * @param $param
     * @param null $parameters
     * @return bool
     */
    public function size($param, $parameters = null)
    {
        if (!empty($parameters['size'])) {
            $size = explode(',', $parameters['size']);
            $image = filesize($this->request[$param]['tmp_name']);
            if (count($size) == 1) {
                $operator = $size[0][0];
                $value = substr($size[0], 1);
                if ($this->operate($operator, $image, $value)) return true;
                $this->response[$param]['size'] = '"' . $param . '" size is not correct';
                return false;
            } else if ($image >= $size[0] && $image <= $size[1]) return true;
        }
        return $this->response[$param]['size'] = '"' . $param . '" size is not correct';
    }

    /**
     * @param $param
     * @param null $parameters
     * @return bool
     */
    public function height($param, $parameters = null)
    {
        if (!empty($parameters['height'])) {
            $height = explode(',', $parameters['height']);
            $infoImg = getimagesize($this->request[$param]['tmp_name']);
            if (count($height) == 1) {
                $operator = $height[0][0];
                $value = substr($height[0], 1);
                if ($this->operate($operator, $infoImg[1], $value)) return true;
                $this->response[$param]['height'] = '"' . $param . '" height is not correct';
                return false;
            } else if ($infoImg[1] >= $height[0] && $infoImg[1] <= $height[1]) return true;
        }
        return $this->response[$param]['height'] = '"' . $param . '" height is not correct';

    }

    /**
     * @param $param
     * @param null $parameters
     * @return bool
     */
    public function width($param, $parameters = null)
    {
        if (!empty($parameters['width'])) {
            $width = explode(',', $parameters['width']);
            $infoImg = getimagesize($this->request[$param]['tmp_name']);
            if (count($width) == 1) {
                $operator = $width[0][0];
                $value = substr($width[0], 1);
                if ($this->operate($operator, $infoImg[0], $value)) return true;
                $this->response[$param]['width'] = '"' . $param . '" width is not correct';
                return false;
            } else if ($infoImg[0] >= $width[0] && $infoImg[0] <= $width[1]) return true;
        }
        return $this->response[$param]['width'] = '"' . $param . '" width is not correct';
    }

    /**
     * @param $operator
     * @param $param
     * @param $value
     * @return bool
     */
    private function operate($operator, $param, $value)
    {
        switch ($operator) {
            case '<':
                if ($param < $value) return true;
                break;
            case '>':
                if ($param > $value) return true;
                break;
            default:
                if ($param == $value) return true;
                break;
        }
        return false;
    }


    /**
     * @description the input must be set
     * @param $param
     * @return array|bool
     */
    public function set($param)
    {
        if (isset($this->request[$param])) return true;
        else if (!empty($this->request[$param]['name'])) return true;
        return $this->response[$param]['set'] = '"' . $param . '" is not set';
    }


    /**
     * @description the input must be set and not empty
     * @param $param
     * @param string $key
     * @return array|bool
     */
    public function required($param, $key = 'required')
    {
        if (isset($this->request[$param]) && !empty($this->request[$param]) && !isset($_FILES[$param])) return true;
        else if (isset($_FILES[$param]['name']) && !empty($this->request[$param]['name'])) return true;
        return $this->response[$param][$key] = '"' . $param . '" is required';
    }

    /**
     * @description the input is required if it valid the conditions above
     * @param $param
     * @param null $parameters
     * @return array|bool
     */
    public function requiredIf($param, $parameters = null)
    {
        if (!empty($parameters['requiredIf'])) {
            $params = explode(',', $parameters['requiredIf']);
            switch ($params[0]) {
                case 'field':
                    foreach ($params as $key => $value) {
                        if ($key > 0 && ((isset($_FILES[$value]['name']) && !empty($_FILES[$value]['name'])) || (isset($this->request[$value]) && !empty($this->request[$value]) && !isset($_FILES[$value])))) {
                            return $this->required($param, 'requiredIf');
                        }
                    }
                    break;
                case 'empty_field':
                    foreach ($params as $key => $value) {
                        if ($key > 0 && ((isset($this->request[$value]) && empty($this->request[$value])) || (isset($_FILES[$value]) && empty($_FILES[$value]['name'])))) {
                            return $this->required($param, 'requiredIf');
                        }
                    }
                    break;
                case 'field_set':
                    foreach ($params as $key => $value) {
                        if ($key > 0 && isset($this->request[$value])) {
                            return $this->required($param, 'requiredIf');
                        }
                    }
                    break;
                case 'field_not_set':
                    foreach ($params as $key => $value) {
                        if ($key > 0 && !isset($this->request[$value])) {
                            return $this->required($param, 'requiredIf');
                        }
                    }
                    break;
                case 'field_value':
                    $required = false;
                    for ($i = 2; $i < count($params); ++$i) {
                        if (isset($this->request[$params[1]]) && !empty($this->request[$params[1]]) && $this->request[$params[1]] == $params[$i]) {
                            $required = true;
                        }
                    }
                    if($required) return $this->required($param, 'requiredIf');
                    break;
                case 'field_value_not':
                    $required = true;
                    for ($i = 2; $i < count($params); ++$i) {
                        if (isset($this->request[$params[1]]) && !empty($this->request[$params[1]]) && $this->request[$params[1]] == $params[$i]) {
                            $required = false;
                        }
                    }
                    if($required) return $this->required($param, 'requiredIf');
                    break;
                default:
                    if ($params[0] == $params[1]) {
                        return $this->required($param, 'requiredIf');
                    }
                    break;
            }
        }
        return true;
    }

    /**
     * @description the input is required with all of the input specified
     * @param $param
     * @param null $parameters
     * @return bool
     */
    public function requiredWith($param, $parameters = null)
    {
        if (isset($this->request[$param]) && !empty($this->request[$param])) {
            if (!empty($parameters['requiredWith'])) {
                $params = explode(',', $parameters['requiredWith']);
                foreach ($params as $field) {
                    if (!isset($this->request[$field]) || empty($this->request[$field]) || (isset($_FILES[$field]['name']) && empty($_FILES[$field]['name'])))
                        return $this->response[$param]['requiredWith'] = 'All of the following input(s) must not be empty : ' . $parameters['requiredWith'];
                }
                return true;
            }
        }
        return $this->response[$param]['requiredWith'] = '"' . $param . '" is required';
    }

    /**
     * @description the input is required with one of the following inputs
     * @param $param
     * @param null $parameters
     * @return bool
     */
    public function requiredOneOf($param, $parameters = null)
    {
        if (isset($this->request[$param]) && !empty($this->request[$param])) {
            if (!empty($parameters['requiredOneOf'])) {
                $params = explode(',', $parameters['requiredOneOf']);
                foreach ($params as $field) {
                    if (isset($this->request[$field]) && !empty($this->request[$field]) && !isset($_FILES[$field])) return true;
                    else if (isset($_FILES[$field]['name']) && !empty($this->request[$field]['name'])) return true;
                }
                return $this->response[$param]['requiredOneOf'] = 'At least one of the following input(s) must not be empty : ' . $parameters['requiredOneOf'];
            }
        }
        return $this->response[$param]['requiredOneOf'] = '"' . $param . '" is required';
    }

    /**
     * @description the input is optional but the followings input must not be empty
     * @param $param
     * @param null $parameters
     * @return bool
     */
    public function with($param, $parameters = null)
    {
        if (isset($this->request[$param]) && !empty($this->request[$param])) {
            if (!empty($parameters['with'])) {
                $params = explode(',', $parameters['with']);
                foreach ($params as $field)
                    if (!isset($this->request[$field]) || empty($this->request[$field]) || (isset($_FILES[$field]['name']) && empty($_FILES[$field]['name'])))
                        return $this->response[$param]['with'] = 'All of the following input(s) must not be empty : ' . $parameters['with'];
            }
        }
        return true;
    }

    /**
     * @description the input is optional but one of the following input must not be empty
     * @param $param
     * @param null $parameters
     * @return bool
     */
    public function oneOf($param, $parameters = null)
    {
        if (isset($this->request[$param]) && !empty($this->request[$param])) {
            if (!empty($parameters['oneOf'])) {
                $params = explode(',', $parameters['oneOf']);
                foreach ($params as $field) {
                    if (isset($this->request[$field]) && !empty($this->request[$field]) && !isset($_FILES[$field])) return true;
                    else if (isset($_FILES[$field]['name']) && !empty($this->request[$field]['name'])) return true;
                }
                return $this->response[$param]['oneOf'] = 'At least one of the following input must not be empty : ' . $parameters['oneOf'];
            }
        }
        return true;
    }

    /**
     * @description the input is optional and the following rules are not execute if the input is empty
     * @param $param
     * @return bool
     */
    public function optional($param)
    {
        if (isset($this->request[$param]) && !empty($this->request[$param]) && !is_array($this->request[$param])) return true;
        if (!empty($this->request[$param]['name'])) return true;
        $this->skip = true;
    }


    /**
     * @description the input is optional if it validate the condition above
     * @param $param
     * @param null $parameters
     * @return bool
     */
    public function optionalIf($param, $parameters = null)
    {
        if (!empty($parameters['optionalIf'])) {
            $params = explode(',', $parameters['optionalIf']);
            switch ($param[0]) {
                case 'field':
                    if (isset($this->request[$params[1]]) && !empty($this->request[$params[1]]))
                        return $this->optional($param);
                    break;
                case 'empty_field':
                    if (isset($this->request[$params[1]]) && empty($this->request[$params[1]]))
                        return $this->optional($param);
                    break;
                case 'field_set':
                    if (isset($this->request[$params[1]]))
                        return $this->optional($param);
                    break;
                case 'field_not_set':
                    if (!isset($this->request[$params[1]]))
                        return $this->optional($param);
                    break;
                case 'field_value':
                    $optional = false;
                    for ($i = 2; $i < count($params); ++$i) {
                        if (isset($this->request[$params[1]]) && !empty($this->request[$params[1]]) && $this->request[$params[1]] == $params[$i]) {
                            $optional = true;
                        }
                    }
                    if($optional) return $this->optional($param);
                    break;
                case 'field_value_not':
                    $optional = true;
                    for ($i = 2; $i < count($params); ++$i) {
                        if (isset($this->request[$params[1]]) && !empty($this->request[$params[1]]) && $this->request[$params[1]] == $params[$i]) {
                            $optional = false;
                        }
                    }
                    if($optional) return $this->optional($param);
                    break;
                default:
                    if ($params[0] == $params[1])
                        return $this->optional($param);
                    break;
            }
        }
        return true;
    }


    /**
     * @description skip the following rules if the condition above is validate
     * @param $param
     * @param null $parameters
     * @return bool
     */
    public function skipIf($param, $parameters = null)
    {
        if (!empty($parameters['skipIf'])) {
            $params = explode(',', $parameters['skipIf']);
            switch ($param[0]) {
                case 'field':
                    if (isset($this->request[$params[1]]) && !empty($this->request[$params[1]]))
                        $this->skip = true;
                    break;
                case 'empty_field':
                    if (isset($this->request[$params[1]]) && empty($this->request[$params[1]]))
                        $this->skip = true;
                    break;
                case 'field_set':
                    if (isset($this->request[$params[1]]))
                        $this->skip = true;
                    break;
                case 'field_not_set':
                    if (!isset($this->request[$params[1]]))
                        $this->skip = true;
                    break;
                case 'field_value':
                    if (isset($this->request[$params[1]]) && !empty($this->request[$params[1]]) && $this->request[$params[1]] == $params[2])
                        $this->skip = true;
                    break;
                case 'field_value_not':
                    if (isset($this->request[$params[1]]) && !empty($this->request[$params[1]]) && $this->request[$params[1]] != $params[2])
                        $this->skip = true;
                    break;
                default:
                    if ($params[0] == $params[1])
                        $this->skip = true;
                    break;
            }
        }
        return true;
    }

    /**
     * @param $param
     * @param $parameters
     */
    public function add($param, $parameters)
    {
        if (!empty($parameters['add'])) {
            $params = explode(',', $parameters['add']);
            switch ($params[0]) {
                case 'end':
                    $this->request[$param] = $this->request[$param] . $params[1];
                    break;
                case 'begin':
                    $this->request[$param] = $params[1] . $this->request[$param];
                    break;
            }
        }
    }

    /**
     * @param $param
     * @param null $parameters
     */
    public function assign($param, $parameters = null)
    {
        if (!empty($parameters['assign'])) {
            $params = explode(',', $parameters['assign']);
            if ($params[0] == 'date') $this->request[$param] = new \DateTime($param);
            if (count($params) > 1) {
                switch ($params[0]) {
                    case 'crypt':
                        if (isset($this->request[$param]) && !empty($this->request[$param]))
                            $this->crypt($param, $params);
                        break;
                    case 'value':
                        $this->request[$param] = $params[1];
                        break;
                    case 'field':
                        if (isset($this->request[$param]) && !empty($this->request[$param]) && isset($this->request[$params[1]]) && !empty($this->request[$params[1]]))
                            $this->request[$param] = $this->request[$params[1]];
                        break;
                    case 'file':
                        if (!empty($this->request[$param]['name']))
                            $this->request[$param] = $_FILES[$param]['name'];
                        break;
                    case 'this':
                        if (isset($this->request[$param]) && !empty($this->request[$param]))
                            $this->request[$params[1]] = $this->request[$param];
                        break;
                }
                if (isset($params[2]) && $params[2] == 'file') {
                    if (!empty($_FILES[$param]['name'])) {
                        $extension = pathinfo($_FILES[$param]['name'], PATHINFO_EXTENSION);
                        $this->request[$param] = $this->request[$param] . '.' . $extension;
                    }
                }
            } else
                $this->request[$param] = $params[0];
        }
    }

    /**
     * @param $param
     * @param null $parameters
     */
    public function assignIf($param, $parameters = null)
    {
        if (!empty($parameters['assignIf'])) {
            $params = explode(',', $parameters['assignIf']);
            switch ($params[0]) {
                // if input is not equal to argument , we assign to request input the
                case 'not':
                    if ($this->request[$param] != $params[1])
                        $this->request[$param] = $this->clean($this->request[$param]);
                    else
                        $this->request[$param] = $params[1];
                    break;
                case 'file':
                    if (!empty($this->request[$param]['name']))
                        $this->request[$param] = $this->clean($_FILES[$param]['name']);
                    break;
                case 'empty':
                    if (!isset($this->request[$param]) || empty($this->request[$param]))
                        $this->request[$param] = $this->clean($params[1]);
                    break;
                case 'not_set':
                    if (!isset($this->request[$param]))
                        $this->request[$param] = $this->clean($params[1]);
                    break;
                case 'empty_file':
                    if (empty($this->request[$param]['name']))
                        $this->request[$param] = $this->clean($params[1]);
                    break;
                case 'check_file':
                    if (!isset($this->request[$param]['name']) && isset($_FILES[$param]['name']))
                        $this->request[$param] = $_FILES[$param];
                    break;
                case 'empty_value':
                    if (empty($this->request[$param]))
                        $this->request[$param] = $this->clean($params[1]);
                    break;
                default:
                    if ($params[0] == $params[1])
                        $this->request[$param] = $this->clean($params[1]);
                    break;
            }
        }
    }

    /**
     * @param $param
     * @param $params
     */
    public function crypt($param, $params)
    {
        $value = '';
        if (isset($this->request[$param]) && !empty($this->request[$param]))
            $value = $this->clean($this->request[$param]);
        else if (!empty($this->request[$param]['name']))
            $value = $this->clean($this->request[$param]['name']);
        switch ($params[1]) {
            case 'password_hash':
                $this->request[$param] = password_hash($value, PASSWORD_BCRYPT);
                break;
            default:
                $this->request[$param] = $params[1]($value);
                break;
        }
    }


    /**
     * @param $name
     * @param $arguments
     * @return bool
     */
    public function __call($name, $arguments)
    {
        if (strpos($name, 'is') !== false) {
            $name = lcfirst(str_replace('is', '', $name));
            if (isset($arguments[0])) {
                $value = $arguments[0];
                if (!is_null($this->post($value)) && $this->post($value) != '') $this->request[$value] = $this->post($value);
                else if (!is_null($this->file($value))) $this->request[$value] = $this->file($value);
                else if (!is_null($this->get($value)) && $this->get($value) != '') $this->request[$value] = $this->get($value);
                else $this->request[$value] = $value;
                array_shift($arguments);
                if (method_exists($this, $name) && call_user_func_array([$this, $name], [$value, [$name => implode(',', $arguments)]]) !== true) return $this->response[$value];
                return true;
            }
        }
        return false;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array([self::getInstance(), $name], $arguments);
    }

    /**
     * @param $value
     * @return array
     */
    private function clean($value)
    {
        return (!is_array($value))
            ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false)
            : $value;
    }

    /**
     * @param $haystack
     * @param $needle
     * @param int $offset
     * @return bool
     */
    private function strposa($haystack, $needle, $offset = 0)
    {
        if (!is_array($needle)) $needle = array($needle);
        foreach ($needle as $query) {
            if (strpos($haystack, $query, $offset) !== false) return true; // stop on first true result
        }
        return false;
    }
}
