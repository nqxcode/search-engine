<?php
namespace SearchEngine;

/**
 * Class Attribute
 *
 * Описывает пару 'название поля' - 'значение поля' для индексируемой модели
 *
 * @package Search
 */
class Attribute
{
    private $fieldName;
    private $value;

    private $encoding;

    /**
     * @param string $name поле модели
     * @param string $value значение поля модели
     * @param string $encoding кодировка для значений полей модели
     */
    public function __construct($name, $value, $encoding = 'utf-8')
    {
        $this->fieldName = $name;
        $this->value = $value;
        $this->encoding = $encoding;
    }

    /**
     * Получить название поля
     *
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * Получить значение поля
     *
     * @return mixed
     */
    public function getValue()
    {
        return mb_convert_encoding($this->value, 'utf-8', $this->encoding);
    }
}
