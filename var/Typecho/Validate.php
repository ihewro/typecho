<?php
// vim: set et sw=4 ts=4 sts=4 fdm=marker ff=unix fenc=utf8
/**
 * Typecho Blog Platform
 *
 * 验证类
 * <code>
 * $test = "hello";
 * $Validation  = new TypechoValidation();
 * $Validation->form($test, array("alpha" => "不是字符");
 * var_dump($Validation->getErrorMsg());
 * </code>
 *
 * @copyright  Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license    GNU General Public License 2.0
 * @version    $Id: Validation.php 106 2008-04-11 02:23:54Z magike.net $
 */

/**
 * 验证类
 *
 * @package Validate
 */
class Typecho_Validate
{
    /**
     * 内部数据
     *
     * @access private
     * @var array
     */
    private $_data;

    /**
     * 当前验证指针
     *
     * @access private
     * @var string
     */
    private $_key;

    /**
     * 验证规则数组
     *
     * @access private
     * @var array
     */
    private $_rules = [];

    /**
     * 中断模式,一旦出现验证错误即抛出而不再继续执行
     *
     * @access private
     * @var boolean
     */
    private $_break = false;

    /**
     * 最小长度
     *
     * @access public
     *
     * @param string $str 待处理的字符串
     * @param integer $length 最小长度
     *
     * @return boolean
     */
    public static function minLength(string $str, int $length): bool
    {
        return (Typecho_Common::strLen($str) >= $length);
    }

    /**
     * 枚举类型判断
     *
     * @access public
     *
     * @param string $str 待处理的字符串
     * @param array $params 枚举值
     *
     * @return bool
     */
    public static function enum(string $str, array $params): bool
    {
        $keys = array_flip($params);
        return isset($keys[$str]);
    }

    /**
     * Max Length
     *
     * @param string $str
     * @param int $length
     *
     * @return bool
     */
    public static function maxLength(string $str, int $length): bool
    {
        return (Typecho_Common::strLen($str) < $length);
    }

    /**
     * Valid Email
     *
     * @access public
     *
     * @param string $str
     *
     * @return boolean
     */
    public static function email(string $str): bool
    {
        return preg_match("/^[_a-z0-9-\.+]+@([-a-z0-9]+\.)+[a-z]{2,}$/i", $str);
    }

    /**
     * 验证是否为网址
     *
     * @access public
     *
     * @param string $str
     *
     * @return boolean
     */
    public static function url(string $str): bool
    {
        $parts = @parse_url($str);
        if (!$parts) {
            return false;
        }

        return isset($parts['scheme']) &&
            in_array($parts['scheme'], ['http', 'https', 'ftp']) &&
            !preg_match('/(\(|\)|\\\|"|<|>|[\x00-\x08]|[\x0b-\x0c]|[\x0e-\x19])/', $str);
    }

    /**
     * Alpha
     *
     * @access public
     *
     * @param string
     *
     * @return boolean
     */
    public static function alpha(string $str): bool
    {
        return preg_match("/^([a-z])+$/i", $str) ? true : false;
    }

    /**
     * Alpha-numeric
     *
     * @access public
     *
     * @param string
     *
     * @return boolean
     */
    public static function alphaNumeric(string $str): bool
    {
        return preg_match("/^([a-z0-9])+$/i", $str);
    }

    /**
     * Alpha-numeric with underscores and dashes
     *
     * @access public
     *
     * @param string
     *
     * @return boolean
     */
    public static function alphaDash(string $str): bool
    {
        return preg_match("/^([_a-z0-9-])+$/i", $str) ? true : false;
    }

    /**
     * 对xss字符串的检测
     *
     * @access public
     *
     * @param string $str
     *
     * @return boolean
     */
    public static function xssCheck(string $str): bool
    {
        $search = 'abcdefghijklmnopqrstuvwxyz';
        $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $search .= '1234567890!@#$%^&*()';
        $search .= '~`";:?+/={}[]-_|\'\\';

        for ($i = 0; $i < strlen($search); $i++) {
            // ;? matches the ;, which is optional
            // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars

            // &#x0040 @ search for the hex values
            $str = preg_replace('/(&#[xX]0{0,8}' . dechex(ord($search[$i])) . ';?)/i', $search[$i], $str); // with a ;
            // &#00064 @ 0{0,7} matches '0' zero to seven times
            $str = preg_replace('/(&#0{0,8}' . ord($search[$i]) . ';?)/', $search[$i], $str); // with a ;
        }

        return !preg_match('/(\(|\)|\\\|"|<|>|[\x00-\x08]|[\x0b-\x0c]|[\x0e-\x19]|' . "\r|\n|\t" . ')/', $str);
    }

    /**
     * Numeric
     *
     * @access public
     *
     * @param mixed $str
     *
     * @return boolean
     */
    public static function isFloat($str): bool
    {
        return preg_match("/^[0-9\.]+$/", $str);
    }

    /**
     * Is Numeric
     *
     * @access public
     *
     * @param mixed $str
     *
     * @return boolean
     */
    public static function isInteger($str): bool
    {
        return is_numeric($str);
    }

    /**
     * 增加验证规则
     *
     * @access public
     *
     * @param string $key 数值键值
     * @param string|array $rule 规则名称
     * @param string $message 错误字符串
     *
     * @return $this
     */
    public function addRule(string $key, $rule, string $message): Typecho_Validate
    {
        if (func_num_args() <= 3) {
            $this->_rules[$key][] = [$rule, $message];
        } else {
            $params = func_get_args();
            $params = array_splice($params, 3);
            $this->_rules[$key][] = array_merge([$rule, $message], $params);
        }

        return $this;
    }

    /**
     * 设置为中断模式
     *
     * @access public
     * @return void
     */
    public function setBreak()
    {
        $this->_break = true;
    }

    /**
     * Run the Validator
     * This function does all the work.
     *
     * @access    public
     *
     * @param array $data 需要验证的数据
     * @param array|null $rules 验证数据遵循的规则
     *
     * @return    array
     */
    public function run(array $data, array $rules = null): array
    {
        $result = [];
        $this->_data = $data;
        $rules = empty($rules) ? $this->_rules : $rules;

        // Cycle through the rules and test for errors
        foreach ($rules as $key => $rules) {
            $this->_key = $key;
            $data[$key] = (is_array($data[$key]) ? 0 == count($data[$key])
                : 0 == strlen($data[$key])) ? null : $data[$key];

            foreach ($rules as $params) {
                $method = $params[0];

                if ('required' != $method && 'confirm' != $method && 0 == strlen($data[$key])) {
                    continue;
                }

                $message = $params[1];
                $params[1] = $data[$key];
                $params = array_slice($params, 1);

                if (!call_user_func_array(is_array($method) ? $method : [$this, $method], $params)) {
                    $result[$key] = $message;
                    break;
                }
            }

            /** 开启中断 */
            if ($this->_break && $result) {
                break;
            }
        }

        return $result;
    }

    /**
     * 验证输入是否一致
     *
     * @access public
     *
     * @param string|null $str 待处理的字符串
     * @param string $key 需要一致性检查的键值
     *
     * @return boolean
     */
    public function confirm(?string $str, string $key): bool
    {
        return !empty($this->_data[$key]) ? ($str == $this->_data[$key]) : empty($str);
    }

    /**
     * 是否为空
     *
     * @access public
     *
     * @param string|null $str 待处理的字符串
     *
     * @return boolean
     */
    public function required(?string $str): bool
    {
        return !empty($this->_data[$this->_key]);
    }
}
