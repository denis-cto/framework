<?php

namespace Pina\Components;

class Field
{

    protected $key = '';
    protected $title = '';
    protected $type = '';
    protected $isMandatory = '';

    /**
     * Создает экземпляр поля
     * @param type $key
     * @param type $title
     * @param type $type
     * @param type $default
     * @return \static
     */
    public static function make($key, $title, $type = 'string', $isMandatory = false)
    {
        $field = new static;
        $field->key = $key;
        $field->title = $title;
        $field->type = $type;
        $field->isMandatory = $isMandatory;
        return $field;
    }

    /**
     * Получает ключ поля
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Получить наименование поля
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Получить тип поля
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Получить значение по умолчанию
     * @return string
     */
    public function getDefault()
    {
        return $this->default;
    }
    
    public function isMandatory()
    {
        return $this->isMandatory;
    }

    /**
     * Отрисовать данные в поле в соответствие с настройками поля 
     * @param array $line
     * @return string
     */
    public function draw($line)
    {
        if (!isset($line[$this->key]) || $line[$this->key] === '') {
            return $this->getDefault();
        }

        return $line[$this->key];
    }

}
