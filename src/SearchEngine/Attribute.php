<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Andrey
 * Date: 11.10.13
 * Time: 13:33
 * Attribute
 */

namespace SearchEngine;

/**
 * Class Attribute
 *
 * @package Search
 */
class Attribute
{
    private $fieldName;
    private $value;

    private $encoding;

    /**
     * @param $name поле модели
     * @param $value значение поля модели
     * @param string $encoding кодировка для значений полей модели
     */
    public function __construct($name, $value, $encoding = 'utf-8')
    {
        $this->fieldName = $name;
        $this->value = $value;
        $this->encoding = $encoding;
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return mb_convert_encoding($this->value, 'utf-8', $this->encoding);
    }
}