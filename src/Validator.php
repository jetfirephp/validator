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
    private static $rules;
    /**
     * @var
     */
    private static $params;
    /**
     * @var array
     */
    private static $response = [];
    /**
     * @var
     */
    private static $skip;
    /**
     * @var
     */
    private static $customMessages;

    /**
     * @var array
     */
    private static $request = [];

    /**
     * @description validate values
     * @param array $all
     * @param null $customMessages
     * @return array
     */
    public static function validate($all = [], $customMessages = null)
    {
        self::$customMessages = $customMessages;
        self::getValues($all);
        foreach ($all as $label => $rule) {
            self::$rules = preg_split('/(\||\+)/', $rule);
            self::$params = explode('|', $label);
            self::make();
        }
        return self::response();
    }


    /**
     * @description validate $_POST values
     * @param array $all
     * @param null $customMessages
     * @return array
     */
    public static function validatePost($all = [], $customMessages = null)
    {
        self::$customMessages = $customMessages;
        self::getValues($all,'post');
        foreach ($all as $label => $rule) {
            self::$rules = preg_split('/(\||\+)/', $rule);
            self::$params = explode('|', $label);
            self::make();
        }
        return self::response();
    }

    /**
     * @description validate $_GET values
     * @param array $all
     * @param null $customMessages
     * @return array
     */
    public static function validateGet($all = [], $customMessages = null)
    {
        self::$customMessages = $customMessages;
        self::getValues($all,'get');
        foreach ($all as $label => $rule) {
            self::$rules = preg_split('/(\||\+)/', $rule);
            self::$params = explode('|', $label);
            self::make();
        }
        return self::response();
    }

    /**
     * @description recover all values in request variable
     * @param $rules
     * @param string $type
     */
    private static function getValues($rules,$type = 'default')
    {
        switch($type) {
            case 'default':
                foreach ($rules as $label => $rule) {
                    $params = explode('|', $label);
                    foreach ($params as $param) {
                        $key = strstr($param, '::', true);
                        empty($key) ? self::$request[$param] = $param : self::$request[$key] = str_replace($key.'::','',$param);
                    }
                }
                break;
            case 'get':
                foreach ($rules as $label => $rule) {
                    $params = explode('|', $label);
                    foreach ($params as $param)
                        if (isset($_GET[$param])) self::$request[$param] = $_GET[$param];
                }
                break;
            case 'post':
                foreach ($rules as $label => $rule) {
                    $params = explode('|', $label);
                    foreach ($params as $param) {
                        if (isset($_POST[$param])) self::$request[$param] = $_POST[$param];
                        else if (!empty($_FILES[$param]['name'])) self::$request[$param] = $_FILES[$param];
                    }
                }
                break;
        }
    }


    /**
     *
     */
    private static function make()
    {
        foreach (self::$params as $key1 => $param) {
            self::$skip = false;
            $key = strstr($param, '::', true);
            $param = (empty($key))?$param:$key;
            foreach (self::$rules as $key2 => $rule) {
                if (self::$skip == true) break;
                $exec = explode(':', $rule);
                if (!empty($exec[1])) $parameters[$exec[0]] = $exec[1];
                (empty($parameters[$exec[0]])) ? self::$exec[0]($param) : self::$exec[0]($param, $parameters);
            }
        }
    }

    /**
     * @return array
     */
    private static function response()
    {
        if (!is_null(self::$customMessages)) {
            $messages = self::$customMessages;
            foreach (self::$response as $field => $rules)
                foreach ($rules as $rule => $message)
                    if (isset($messages[$rule . ':' . $field]))
                        self::$response[$field][$rule] = str_replace(':field', '"' . $field . '"', $messages[$rule . ':' . $field]);
                    else if (isset($messages[$rule]))
                        self::$response[$field][$rule] = str_replace(':field', '"' . $field . '"', $messages[$rule]);
        }
        return (!empty(self::$response)) ? ['valid' => false, 'status' => 'error', 'message' => self::$response] : ['valid' => true, 'status' => 'success', 'values' => self::$request];
    }

    /*-----------------------------------------------------------------------*/

    /**
     * @param $param
     * @param $parameters
     * @return bool
     */
    public static function regex($param, $parameters)
    {
        if (!empty($parameters['regex'])) {
            return (preg_match($parameters['regex'], self::$request[$param]))
                ? true
                : self::$response[$param]['regex'] = '"' . $param . '"  must validate against "' . $parameters['regex'] . '"';
        }
        return true;
    }

    /**
     * @param $param
     * @return bool
     */
    public static function alpha($param)
    {
        return (ctype_alpha(str_replace(' ', '', self::$request[$param])))
            ? true
            : self::$response[$param]['alpha'] = '"' . $param . '"  must contain only letters (a-z)';
    }

    /**
     * @param $param
     * @return bool
     */
    public static function alnum($param)
    {
        return (ctype_alnum(str_replace(' ', '', self::$request[$param])))
            ? true
            : self::$response[$param]['alnum'] = '"' . $param . '"  must contain only letters (a-z) and digits (0-9)';
    }

    /**
     * @param $param
     * @return bool
     */
    public static function string($param)
    {
        if (is_string(self::$request[$param])) return true;
        return self::$response[$param]['string'] = '"' . $param . '" is not a string';
    }

    /**
     * @param $param
     * @return bool
     */
    public static function int($param)
    {
        if (is_numeric(self::$request[$param]) && (int)self::$request[$param] == self::$request[$param]) return true;
        return self::$response[$param]['int'] = '"' . $param . '" is not a integer';
    }

    /**
     * @param $param
     * @return bool
     */
    public static function numeric($param)
    {
        if (is_numeric(self::$request[$param])) return true;
        return self::$response[$param]['numeric'] = '"' . $param . '" is not a numeric';
    }

    /**
     * @param $param
     * @param $parameters
     * @return bool|string
     */
    public static function max($param,$parameters){
        if (!empty($parameters['max'])) {
            if ((int)self::$request[$param] <= (int)$parameters['max'])return true;
        }
        return self::$response[$param]['max'] = '"' . $param . '" must be lower than "'.$parameters['max'].'"';
    }

    /**
     * @param $param
     * @param $parameters
     * @return bool|string
     */
    public static function min($param,$parameters){
        if (!empty($parameters['min'])) {
            if ((int)self::$request[$param] >= (int)$parameters['min'])return true;
        }
        return self::$response[$param]['min'] = '"' . $param . '" must be higher than "'.$parameters['min'].'"';
    }

    /**
     * @param $param
     * @return bool
     */
    public static function url($param)
    {
        if (filter_var(self::$request[$param], FILTER_VALIDATE_URL)) return true;
        return self::$response[$param]['url'] = '"' . $param . '" is not a valid url';
    }

    /**
     * @param $param
     * @return bool
     */
    public static function boolean($param)
    {
        $param = self::$request[$param];
        if ($param === '1' || $param === '0' || $param === true || $param === false || $param === 1 || $param === 0 || $param == 'true' || $param == 'false') return true;
        return self::$response[$param]['boolean'] = '"' . $param . '" is not a valid boolean';
    }

    /**
     * @param $param
     * @param null $parameters
     * @return bool
     */
    public static function date($param, $parameters = null)
    {
        if (self::$request[$param] instanceof DateTime) return true;
        if (DateTime::createFromFormat('Y-m-d G:i:s', self::$request[$param]) !== FALSE) return true;
        if (!empty($parameters['date'])) {
            $exceptionalFormats = array(
                'c' => 'Y-m-d\TH:i:sP',
                'r' => 'D, d M Y H:i:s O',
            );
            if (in_array($parameters['date'], array_keys($exceptionalFormats))) {
                $parameters['date'] = $exceptionalFormats[$parameters['date']];
            }
            $dateFromFormat = DateTime::createFromFormat($parameters['datetime'], self::$request[$param]);
            if ($dateFromFormat && self::$request[$param] === $dateFromFormat->format($parameters['date'])) return true;
        }
        return self::$response[$param]['date'] = '"' . $param . '" is not a valid date';
    }

    /**
     * @param $param
     * @return bool
     */
    public static function lowercase($param)
    {
        return (self::$request[$param] === mb_strtolower(self::$request[$param], mb_detect_encoding(self::$request[$param])))
            ? true
            : self::$response[$param]['lowercase'] = '"' . $param . '" must be lowercase';
    }

    /**
     * @param $param
     * @return bool|string
     */
    public static function uppercase($param){
        return (mb_strtoupper(self::$request[$param], mb_detect_encoding(self::$request[$param])))
            ? true
            : self::$response[$param]['uppercase'] = '"' . $param . '" must be uppercase';
    }

    /**
     * @param $param
     * @return array|bool
     */
    public static function noWhitespace($param)
    {
        if (is_null(self::$request[$param])) return true;
        if (false === is_scalar(self::$request[$param])) return self::$response[$param] = ['noWhitespace' => '"' . $param . '" must not contain whitespace'];
        if (!preg_match('#\s#', self::$request[$param])) return true;
        return self::$response[$param]['noWhitespace'] = '"' . $param . '" must not contain whitespace';
    }

    /**
     * @param $param
     * @return array|bool
     */
    public static function email($param)
    {
        if (filter_var(self::$request[$param], FILTER_VALIDATE_EMAIL)) return true;
        return self::$response[$param]['mail'] = 'The e-mail address format is incorrect for "' . $param . '"';
    }

    /**
     * @param $param
     * @return bool
     */
    public static function phone($param)
    {
        if (preg_match('/^[+]?([\d]{0,3})?[\(\.\-\s]?(([\d]{1,3})[\)\.\-\s]*)?(([\d]{3,5})[\.\-\s]?([\d]{4})|([\d]{2}[\.\-\s]?){4})$/', self::$request[$param])) return true;
        return self::$response[$param]['phone'] = 'Incorrect phone number';
    }

    /**
     * @param $param
     * @param null $parameters
     * @return bool
     */
    public static function postalCode($param, $parameters = null)
    {
        $postalCodes = ['AD' => "/^(?:AD)*(\d{3})$/", 'AM' => "/^(\d{6})$/", 'AR' => "/^([A-Z]\d{4}[A-Z]{3})$/", 'AT' => "/^(\d{4})$/", 'AU' => "/^(\d{4})$/", 'AX' => "/^(?:FI)*(\d{5})$/", 'AZ' => "/^(?:AZ)*(\d{4})$/", 'BA' => "/^(\d{5})$/", 'BB' => "/^(?:BB)*(\d{5})$/", 'BD' => "/^(\d{4})$/", 'BE' => "/^(\d{4})$/", 'BG' => "/^(\d{4})$/", 'BH' => "/^(\d{3}\d?)$/", 'BM' => "/^([A-Z]{2}\d{2})$/", 'BN' => "/^([A-Z]{2}\d{4})$/", 'BR' => "/^(\d{8}|\d{5}-\d{3})$/", 'BY' => "/^(\d{6})$/", 'CA' => "/^([ABCEGHJKLMNPRSTVXY]\d[ABCEGHJKLMNPRSTVWXYZ]) ?(\d[ABCEGHJKLMNPRSTVWXYZ]\d)$/", 'CH' => "/^(\d{4})$/", 'CL' => "/^(\d{7})$/", 'CN' => "/^(\d{6})$/", 'CR' => "/^(\d{4})$/", 'CS' => "/^(\d{5})$/", 'CU' => "/^(?:CP)*(\d{5})$/", 'CV' => "/^(\d{4})$/", 'CX' => "/^(\d{4})$/", 'CY' => "/^(\d{4})$/", 'CZ' => "/^(\d{5})$/", 'DE' => "/^(\d{5})$/", 'DK' => "/^(\d{4})$/", 'DO' => "/^(\d{5})$/", 'DZ' => "/^(\d{5})$/", 'EC' => "/^([a-zA-Z]\d{4}[a-zA-Z])$/", 'EE' => "/^(\d{5})$/", 'EG' => "/^(\d{5})$/", 'ES' => "/^(\d{5})$/", 'ET' => "/^(\d{4})$/", 'FI' => "/^(?:FI)*(\d{5})$/", 'FM' => "/^(\d{5})$/", 'FO' => "/^(?:FO)*(\d{3})$/", 'FR' => "/^(\d{5})$/", 'GB' => '/^([Gg][Ii][Rr] 0[Aa]{2})|((([A-Za-z][0-9]{1,2})|(([A-Za-z][A-Ha-hJ-Yj-y][0-9]{1,2})|(([A-Za-z][0-9][A-Za-z])|([A-Za-z][A-Ha-hJ-Yj-y][0-9]?[A-Za-z])))) [0-9][A-Za-z]{2})$/', 'GE' => "/^(\d{4})$/", 'GF' => "/^((97|98)3\d{2})$/", 'GG' => "/^(([A-Z]\d{2}[A-Z]{2})|([A-Z]\d{3}[A-Z]{2})|([A-Z]{2}\d{2}[A-Z]{2})|([A-Z]{2}\d{3}[A-Z]{2})|([A-Z]\d[A-Z]\d[A-Z]{2})|([A-Z]{2}\d[A-Z]\d[A-Z]{2})|(GIR0AA))$/", 'GL' => "/^(\d{4})$/", 'GP' => "/^((97|98)\d{3})$/", 'GR' => "/^(\d{5})$/", 'GT' => "/^(\d{5})$/", 'GU' => "/^(969\d{2})$/", 'GW' => "/^(\d{4})$/", 'HN' => "/^([A-Z]{2}\d{4})$/", 'HR' => "/^(?:HR)*(\d{5})$/", 'HT' => "/^(?:HT)*(\d{4})$/", 'HU' => "/^(\d{4})$/", 'ID' => "/^(\d{5})$/", 'IL' => "/^(\d{5})$/", 'IM' => "/^(([A-Z]\d{2}[A-Z]{2})|([A-Z]\d{3}[A-Z]{2})|([A-Z]{2}\d{2}[A-Z]{2})|([A-Z]{2}\d{3}[A-Z]{2})|([A-Z]\d[A-Z]\d[A-Z]{2})|([A-Z]{2}\d[A-Z]\d[A-Z]{2})|(GIR0AA))$/", 'IN' => "/^(\d{6})$/", 'IQ' => "/^(\d{5})$/", 'IR' => "/^(\d{10})$/", 'IS' => "/^(\d{3})$/", 'IT' => "/^(\d{5})$/", 'JE' => "/^(([A-Z]\d{2}[A-Z]{2})|([A-Z]\d{3}[A-Z]{2})|([A-Z]{2}\d{2}[A-Z]{2})|([A-Z]{2}\d{3}[A-Z]{2})|([A-Z]\d[A-Z]\d[A-Z]{2})|([A-Z]{2}\d[A-Z]\d[A-Z]{2})|(GIR0AA))$/", 'JO' => "/^(\d{5})$/", 'JP' => "/^(\d{7})$/", 'KE' => "/^(\d{5})$/", 'KG' => "/^(\d{6})$/", 'KH' => "/^(\d{5})$/", 'KP' => "/^(\d{6})$/", 'KR' => "/^(?:SEOUL)*(\d{6})$/", 'KW' => "/^(\d{5})$/", 'KZ' => "/^(\d{6})$/", 'LA' => "/^(\d{5})$/", 'LB' => "/^(\d{4}(\d{4})?)$/", 'LI' => "/^(\d{4})$/", 'LK' => "/^(\d{5})$/", 'LR' => "/^(\d{4})$/", 'LS' => "/^(\d{3})$/", 'LT' => "/^(?:LT)*(\d{5})$/", 'LU' => "/^(\d{4})$/", 'LV' => "/^(?:LV)*(\d{4})$/", 'MA' => "/^(\d{5})$/", 'MC' => "/^(\d{5})$/", 'MD' => "/^(?:MD)*(\d{4})$/", 'ME' => "/^(\d{5})$/", 'MG' => "/^(\d{3})$/", 'MK' => "/^(\d{4})$/", 'MM' => "/^(\d{5})$/", 'MN' => "/^(\d{6})$/", 'MQ' => "/^(\d{5})$/", 'MT' => "/^([A-Z]{3}\d{2}\d?)$/", 'MV' => "/^(\d{5})$/", 'MX' => "/^(\d{5})$/", 'MY' => "/^(\d{5})$/", 'MZ' => "/^(\d{4})$/", 'NC' => "/^(\d{5})$/", 'NE' => "/^(\d{4})$/", 'NF' => "/^(\d{4})$/", 'NG' => "/^(\d{6})$/", 'NI' => "/^(\d{7})$/", 'NL' => "/^(\d{4}[A-Z]{2})$/", 'NO' => "/^(\d{4})$/", 'NP' => "/^(\d{5})$/", 'NZ' => "/^(\d{4})$/", 'OM' => "/^(\d{3})$/", 'PF' => "/^((97|98)7\d{2})$/", 'PG' => "/^(\d{3})$/", 'PH' => "/^(\d{4})$/", 'PK' => "/^(\d{5})$/", 'PL' => "/^(\d{5})$/", 'PM' => '/^(97500)$/', 'PR' => "/^(\d{9})$/", 'PT' => "/^(\d{7})$/", 'PW' => '/^(96940)$/', 'PY' => "/^(\d{4})$/", 'RE' => "/^((97|98)(4|7|8)\d{2})$/", 'RO' => "/^(\d{6})$/", 'RS' => "/^(\d{6})$/", 'RU' => "/^(\d{6})$/", 'SA' => "/^(\d{5})$/", 'SD' => "/^(\d{5})$/", 'SE' => "/^(?:SE)*(\d{5})$/", 'SG' => "/^(\d{6})$/", 'SH' => '/^(STHL1ZZ)$/', 'SI' => "/^(?:SI)*(\d{4})$/", 'SK' => "/^(\d{5})$/", 'SM' => "/^(4789\d)$/", 'SN' => "/^(\d{5})$/", 'SO' => "/^([A-Z]{2}\d{5})$/", 'SV' => "/^(?:CP)*(\d{4})$/", 'SZ' => "/^([A-Z]\d{3})$/", 'TC' => '/^(TKCA 1ZZ)$/', 'TH' => "/^(\d{5})$/", 'TJ' => "/^(\d{6})$/", 'TM' => "/^(\d{6})$/", 'TN' => "/^(\d{4})$/", 'TR' => "/^(\d{5})$/", 'TW' => "/^(\d{5})$/", 'UA' => "/^(\d{5})$/", 'US' => "/^\d{5}(-\d{4})?$/", 'UY' => "/^(\d{5})$/", 'UZ' => "/^(\d{6})$/", 'VA' => "/^(\d{5})$/", 'VE' => "/^(\d{4})$/", 'VI' => "/^\d{5}(-\d{4})?$/", 'VN' => "/^(\d{6})$/", 'WF' => "/^(986\d{2})$/", 'YT' => "/^(\d{5})$/", 'ZA' => "/^(\d{4})$/", 'ZM' => "/^(\d{5})$/"];
        $regex = '/^$/';
        if (!empty($parameters['postalCode']))
            if (isset($postalCodes[strtoupper($parameters['postalCode'])]))
                $regex = $postalCodes[strtoupper($parameters['postalCode'])];
        if (preg_match($regex, self::$request[$param])) return true;
        return self::$response[$param]['postalCode'] = 'The postal code format is incorrect';
    }

    /**
     * @param $param
     * @param $parameters
     * @return bool
     */
    public static function equal($param, $parameters)
    {
        if (!empty($parameters['equal'])) {
            $params = explode(',', $parameters['equal']);
            if (count($params) > 1) {
                switch($params[1]) {
                    case 'password_verify' :
                        if (password_verify(self::$request[$param], $params[0])) return true;
                        else return self::$response[$param]['equal'] = '"' . $param . '" value is not equal to "' . $params[0] . '"';
                    break;
                    default :
                        if ($params[1](self::$request[$param]) == $params[0]) return true;
                        else return self::$response[$param]['equal'] = '"' . $param . '" value is not equal to "' . $params[0] . '"';
                    break;
                }
            } else if (count($params) == 1)
                if (self::$request[$param] == $parameters['equal']) return true;
        }
        return self::$response[$param]['equal'] = '"' . $param . '" value is not equal to "' .$parameters['equal'] . '"';
    }

    /**
     * @param $param
     * @param $parameters
     * @return bool
     */
    public static function values($param, $parameters)
    {
        if (!empty($parameters['values'])) {
            $params = explode(',', $parameters['values']);
            foreach ($params as $value)
                if (self::$request[$param] == $value) return true;
        }
        return self::$response[$param]['values'] = '"' . $param . '" must contain one of these values : "' . $parameters['values'] . '"';
    }


    /**
     * @param $param
     * @param null $parameters
     * @return array|bool
     */
    public static function same($param, $parameters)
    {
        if (self::$request[$param] == self::$request[$parameters['same']]) return true;
        return self::$response[$param]['same'] = '"' . $param . '" and "' . $parameters['same'] . '" are not the same';
    }

    /**
     * @param $param
     * @param $parameters
     * @return bool
     */
    public static function length($param, $parameters)
    {
        $params = explode(',', $parameters['length']);
        if (count($params) == 2) {
            if (strlen(self::$request[$param]) > intval($params[0]) && strlen(self::$request[$param]) < intval($params[1])) return true;
            return self::$response[$param]['length'] = '"' . $param . '" must have a length between "' . $params[0] . '" and "' . $params[1] . '"';
        }
        if ($params[0][0] == '<' || $params[0][0] == '>') {
            if (self::operate($params[0][0], strlen(self::$request[$param]), intval(substr($params[0], 1)))) return true;
            return self::$response[$param]['length'] = '"' . $param . '" must have a length ' . $params[0][0] . ' than "' . substr($params[0], 1) . '"';
        }
        if (strlen(self::$request[$param]) === intval($parameters['length'])) return true;
        return self::$response[$param]['length'] = '"' . $param . '" length is not equal to "' . $parameters['length'] . '"';
    }

    /**
     * @param $param
     * @return bool
     */
    public static function image($param)
    {
        $format = ['png', 'jpeg', 'jpg', 'gif', 'svg', 'bmp'];
        $extension = pathinfo(self::$request[$param]['name'], PATHINFO_EXTENSION);
        if (in_array(strtolower($extension), $format)) {
            $infoImg = getimagesize(self::$request[$param]['tmp_name']);
            if ($infoImg[2] >= 1 && $infoImg[2] <= 14) {
                return true;
            }
        }
        return self::$response[$param]['image'] = '"' . $param . '" image format is incorrect';
    }

    /**
     * @param $param
     * @param null $parameters
     * @return bool
     */
    public static function mimes($param, $parameters = null)
    {
        if (!empty($parameters['mimes'])) {
            $extension = pathinfo(self::$request[$param]['name'], PATHINFO_EXTENSION);
            if (in_array(strtolower($extension), explode(',', $parameters['mimes']))) {
                return true;
            }
        }
        return self::$response[$param]['mimes'] = '"' . $param . '" file format is incorrect';
    }

    /**
     * @param $param
     * @param null $parameters
     * @return bool
     */
    public static function size($param, $parameters = null)
    {
        if (!empty($parameters['size']) || is_string($param)) {
            $size = explode(',', $parameters['size']);
            $image = is_string($param) ? filesize($param) : filesize(self::$request[$param]['tmp_name']);
            if (count($size) == 1) {
                $operator = $size[0][0];
                $value = substr($size[0], 1);
                if (self::operate($operator, $image, $value)) return true;
                self::$response[$param]['size'] = '"' . $param . '" size is not correct';
                return false;
            } else if ($image >= $size[0] && $image <= $size[1]) return true;
        }
        return self::$response[$param]['size'] = '"' . $param . '" size is not correct';
    }

    /**
     * @param $param
     * @param null $parameters
     * @return bool
     */
    public static function height($param, $parameters = null)
    {
        if (!empty($parameters['height'])) {
            $height = explode(',', $parameters['height']);
            $infoImg = getimagesize(self::$request[$param]['tmp_name']);
            if (count($height) == 1) {
                $operator = $height[0][0];
                $value = substr($height[0], 1);
                if (self::operate($operator, $infoImg[1], $value)) return true;
                self::$response[$param]['height'] = '"' . $param . '" height is not correct';
                return false;
            } else if ($infoImg[1] >= $height[0] && $infoImg[1] <= $height[1]) return true;
        }
        return self::$response[$param]['height'] = '"' . $param . '" height is not correct';

    }

    /**
     * @param $param
     * @param null $parameters
     * @return bool
     */
    public static function width($param, $parameters = null)
    {
        if (!empty($parameters['width'])) {
            $width = explode(',', $parameters['width']);
            $infoImg = getimagesize(self::$request[$param]['tmp_name']);
            if (count($width) == 1) {
                $operator = $width[0][0];
                $value = substr($width[0], 1);
                if (self::operate($operator, $infoImg[0], $value)) return true;
                self::$response[$param]['width'] = '"' . $param . '" width is not correct';
                return false;
            } else if ($infoImg[0] >= $width[0] && $infoImg[0] <= $width[1]) return true;
        }
        return self::$response[$param]['width'] = '"' . $param . '" width is not correct';
    }

    /**
     * @param $operator
     * @param $param
     * @param $value
     * @return bool
     */
    private static function operate($operator, $param, $value)
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

    /*
      |------------------------------------------------|
     */

    /**
     * @description the input must be set
     * @param $param
     * @return array|bool
     */
    public static function set($param)
    {
        if (isset(self::$request[$param])) return true;
        else if (!empty(self::$request[$param]['name'])) return true;
        return self::$response[$param]['set'] = '"' . $param . '" is not set';
    }


    /**
     * @description the input must be set and not empty
     * @param $param
     * @return array|bool
     */
    public static function required($param)
    {
        if (isset(self::$request[$param]) && !empty(self::$request[$param])) return true;
        else if (!empty(self::$request[$param]['name'])) return true;
        return self::$response[$param]['required'] = '"' . $param . '" is required';
    }

    /**
     * @description the input is required if it validate the conditions above
     * @param $param
     * @param null $parameters
     * @return array|bool
     */
    public static function requiredIf($param, $parameters = null)
    {
        if (!empty($parameters['requiredIf'])) {
            $params = explode(',', $parameters['requiredIf']);
            switch ($params[0]) {
                case 'field':
                    foreach ($params as $key => $value)
                        if ($key > 0)
                            if (isset(self::$request[$value]) && !empty(self::$request[$value]))
                                return self::required($param);
                    break;
                case 'empty_field':
                    foreach ($params as $key => $value)
                        if ($key > 0) {
                            if (isset(self::$request[$value]) && empty(self::$request[$value]))
                                return self::required($param);
                        }
                    break;
                case 'field_set':
                    foreach ($params as $key => $value)
                        if ($key > 0)
                            if (isset(self::$request[$value]))
                                return self::required($param);
                    break;
                case 'field_not_set':
                    foreach ($params as $key => $value)
                        if ($key > 0)
                            if (!isset(self::$request[$value]))
                                return self::required($param);
                    break;
                case 'field_value':
                    if (isset(self::$request[$params[1]]) && !empty(self::$request[$params[1]]) && self::$request[$params[1]] == $params[2])
                        return self::required($param);
                    break;
                case 'field_value_not':
                    if (isset(self::$request[$params[1]]) && !empty(self::$request[$params[1]]) && self::$request[$params[1]] != $params[2])
                        return self::required($param);
                    break;
                default:
                    if ($params[0] == $params[1])
                        return self::required($param);
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
    public static function requiredWith($param, $parameters = null)
    {
        if (isset(self::$request[$param]) && !empty(self::$request[$param])) {
            if (!empty($parameters['requiredWith'])) {
                $params = explode(',', $parameters['requiredWith']);
                foreach ($params as $field) {
                    if (!isset(self::$request[$field]) || empty(self::$request[$field]))
                        return self::$response[$param]['requiredWith'] = 'All of the following input(s) must not be empty : ' . $parameters['requiredWith'];
                }
                return true;
            }
        }
        return self::$response[$param]['requiredWith'] = '"' . $param . '" is required';
    }

    /**
     * @description the input is required with one of the following inputs
     * @param $param
     * @param null $parameters
     * @return bool
     */
    public static function requiredOneOf($param, $parameters = null)
    {
        if (isset(self::$request[$param]) && !empty(self::$request[$param])) {
            if (!empty($parameters['requiredOneOf'])) {
                $params = explode(',', $parameters['requiredOneOf']);
                foreach ($params as $field)
                    if (isset(self::$request[$field]) && !empty(self::$request[$field])) return true;
                return self::$response[$param]['requiredOneOf'] = 'At least one of the following input(s) must not be empty : ' . $parameters['requiredOneOf'];
            }
        }
        return self::$response[$param]['requiredOneOf'] = '"' . $param . '" is required';
    }

    /**
     * @description the input is optional but the followings input must not be empty
     * @param $param
     * @param null $parameters
     * @return bool
     */
    public static function with($param, $parameters = null)
    {
        if (isset(self::$request[$param]) && !empty(self::$request[$param])) {
            if (!empty($parameters['with'])) {
                $params = explode(',', $parameters['with']);
                foreach ($params as $field)
                    if (!isset(self::$request[$field]) || empty(self::$request[$field]))
                        return self::$response[$param]['with'] = 'All of the following input(s) must not be empty : ' . $parameters['with'];
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
    public static function oneOf($param, $parameters = null)
    {
        if (isset(self::$request[$param]) && !empty(self::$request[$param])) {
            if (!empty($parameters['oneOf'])) {
                $params = explode(',', $parameters['oneOf']);
                foreach ($params as $field)
                    if (isset(self::$request[$field]) && !empty(self::$request[$field])) return true;
                return self::$response[$param]['oneOf'] = 'At least one of the following input must not be empty : ' . $parameters['oneOf'];
            }
        }
        return true;
    }

    /**
     * @description the input is optional and the following rules are not execute if the input is empty
     * @param $param
     * @return bool
     */
    public static function optional($param)
    {
        if (isset(self::$request[$param]) && !empty(self::$request[$param])) return true;
        if (!empty(self::$request[$param]['name'])) return true;
        self::$skip = true;
    }


    /**
     * @description the input is optional if it validate the condition above
     * @param $param
     * @param null $parameters
     * @return bool
     */
    public static function optionalIf($param, $parameters = null)
    {
        if (!empty($parameters['optionalIf'])) {
            $params = explode(',', $parameters['optionalIf']);
            switch ($param[0]) {
                case 'field':
                    if (isset(self::$request[$params[1]]) && !empty(self::$request[$params[1]]))
                        return self::optional($param);
                    break;
                case 'empty_field':
                    if (isset(self::$request[$params[1]]) && empty(self::$request[$params[1]]))
                        return self::optional($param);
                    break;
                case 'field_set':
                    if (isset(self::$request[$params[1]]))
                        return self::optional($param);
                    break;
                case 'field_not_set':
                    if (!isset(self::$request[$params[1]]))
                        return self::optional($param);
                    break;
                case 'field_value':
                    if (isset(self::$request[$params[1]]) && !empty(self::$request[$params[1]]) && self::$request[$params[1]] == $params[2])
                        return self::optional($param);
                    break;
                case 'field_value_not':
                    if (isset(self::$request[$params[1]]) && !empty(self::$request[$params[1]]) && self::$request[$params[1]] != $params[2])
                        return self::optional($param);
                    break;
                default:
                    if ($params[0] == $params[1])
                        return self::optional($param);
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
    public static function skipIf($param, $parameters = null)
    {
        if (!empty($parameters['skipIf'])) {
            $params = explode(',', $parameters['skipIf']);
            if ($params[0] == $params[1]) self::$skip = true;
        }
        return true;
    }

    /*
      |------------------------------------------------|
      | Assignation and Overriding                     |
      |------------------------------------------------|
     */

    /**
     * @param $param
     * @param $parameters
     */
    public static function add($param, $parameters)
    {
        if (!empty($parameters['add'])) {
            $params = explode(',', $parameters['add']);
            switch ($params[0]) {
                case 'end':
                    self::$request[$param] = self::$request[$param] . $params[1];
                    break;
                case 'begin':
                    self::$request[$param] = $params[1] . self::$request[$param];
                    break;
            }
        }
    }

    /**
     * @param $param
     * @param null $parameters
     */
    public static function assign($param, $parameters = null)
    {
        if (!empty($parameters['assign'])) {
            $params = explode(',', $parameters['assign']);
            if (count($params) > 1) {
                switch ($params[0]) {
                    case 'crypt':
                        if (isset(self::$request[$param]) && !empty(self::$request[$param]))
                            self::crypt($param, $params);
                        break;
                    case 'value':
                        self::$request[$param] = $params[1];
                        break;
                    case 'field':
                        if (isset(self::$request[$param]) && !empty(self::$request[$param]) && isset(self::$request[$params[1]]) && !empty(self::$request[$params[1]]))
                            self::$request[$param] = self::$request[$params[1]];
                        break;
                    case 'file':
                        if (!empty(self::$request[$param]['name']))
                            self::$request[$param] = $_FILES[$param]['name'];
                        break;
                    case 'this':
                        if (isset(self::$request[$param]) && !empty(self::$request[$param]))
                            self::$request[$params[1]] = self::$request[$param];
                        break;
                }
                if (isset($params[2]) && $params[2] == 'file') {
                    if (!empty($_FILES[$param]['name'])) {
                        $extension = pathinfo($_FILES[$param]['name'], PATHINFO_EXTENSION);
                        self::$request[$param] = self::$request[$param] . '.' . $extension;
                    }
                }
            } else
                self::$request[$param] = $params[0];
        }
    }

    /**
     * @param $param
     * @param null $parameters
     */
    public static function assignIf($param, $parameters = null)
    {
        if (!empty($parameters['assignIf'])) {
            $params = explode(',', $parameters['assignIf']);
            switch ($params[0]) {
                // if input is not equal to argument , we assign to request input the
                case 'not':
                    if (self::$request[$param] != $params[1])
                        self::$request[$param] = self::clean(self::$request[$param]);
                    else
                        self::$request[$param] = $params[1];
                    break;
                case 'file':
                    if (!empty(self::$request[$param]['name']))
                        self::$request[$param] = self::clean($_FILES[$param]['name']);
                    break;
                case 'empty':
                    if (!isset(self::$request[$param]) || empty(self::$request[$param]))
                        self::$request[$param] = self::clean($params[1]);
                    break;
                case 'not_set':
                    if (!isset(self::$request[$param]))
                        self::$request[$param] = self::clean($params[1]);
                    break;
                case 'empty_file':
                    if (empty(self::$request[$param]['name']))
                        self::$request[$param] = self::clean($params[1]);
                    break;
                case 'empty_value':
                    if (empty(self::$request[$param]))
                        self::$request[$param] = self::clean($params[1]);
                    break;
                default:
                    if ($params[0] == $params[1])
                        self::$request[$param] = self::clean($params[1]);
                    break;
            }
        }
    }

    /**
     * @param $param
     * @param $params
     */
    public static function crypt($param, $params)
    {
        $value = '';
        if (isset(self::$request[$param]) && !empty(self::$request[$param]))
            $value = self::clean(self::$request[$param]);
        else if (!empty(self::$request[$param]['name']))
            $value = self::clean(self::$request[$param]['name']);
        switch ($params[1]) {
            case 'password_hash':
                self::$request[$param] = password_hash($value, PASSWORD_BCRYPT);
                break;
            default:
                self::$request[$param] = $params[1]($value);
                break;
        }
    }


    /**
     * @param $name
     * @param $params
     * @return bool
     */
    public static function __callStatic($name, $params)
    {
        if(strpos($name, 'is') !== false) {
            $name = lcfirst(str_replace('is', '', $name));
            foreach ($params as $param) {
                if (isset($_POST[$param]) && !empty($_POST[$param])) self::$request[$param] = $_POST[$param];
                else if (!empty($_FILES[$param]['name'])) self::$request[$param] = $_FILES[$param];
                else if (isset($_GET[$param]) && !empty($_GET[$param])) self::$request[$param] = $_GET[$param];
                else self::$request[$param] = $param;
                if (method_exists(get_class(),$name) && self::$name($param) !== true) return self::$response[$param];
            }
            return true;
        }
    }

    /**
     * @param $value
     * @return string
     */
    private static function clean($value)
    {
        if (!is_array($value))
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
        return $value;
    }
}