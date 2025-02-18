<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 虚拟域帮手类
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 虚拟域帮手类
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Typecho_Widget_Helper_Form_Element_Fake extends Typecho_Widget_Helper_Form_Element
{
    /**
     * 构造函数
     *
     * @access public
     * @param string $name 表单输入项名称
     * @param mixed $value 表单默认值
     * @return void
     */
    public function __construct($name, $value)
    {
        $this->name = $name;
        self::$uniqueId ++;

        /** 运行自定义初始函数 */
        $this->init();

        /** 初始化表单项 */
        $this->input = $this->input($name);

        /** 初始化表单值 */
        if (null !== $value) {
            $this->value($value);
        }
    }

    /**
     * 自定义初始函数
     *
     * @access public
     * @return void
     */
    public function init()
    {
    }

    /**
     * 初始化当前输入项
     *
     * @access public
     * @param string $name 表单元素名称
     * @param array $options 选择项
     * @return Typecho_Widget_Helper_Layout
     */
    public function input($name = null, array $options = null)
    {
        $input = new Typecho_Widget_Helper_Layout('input');
        $this->inputs[] = $input;
        return $input;
    }

    /**
     * 设置表单项默认值
     *
     * @access protected
     * @param string $value 表单项默认值
     * @return void
     */
    protected function _value($value)
    {
        $this->input->setAttribute('value', $value);
    }
}

