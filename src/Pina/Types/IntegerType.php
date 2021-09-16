<?php

namespace Pina\Types;

use Pina\App;
use Pina\Controls\FormInput;
use Pina\Data\Field;

use function Pina\__;

class IntegerType implements TypeInterface
{

    public function setContext($context)
    {
        return $this;
    }

    public function makeControl(Field $field, $value)
    {
        $star = $field->isMandatory() ? ' *' : '';
        return $this->makeInput()
            ->setName($field->getKey())
            ->setTitle($field->getTitle() . $star)
            ->setValue($value)
            ->setType('text');
    }

    public function format($value)
    {
        return is_null($value) ? '-' : $value;
    }

    public function getSize()
    {
        return 11;
    }

    public function getDefault()
    {
        return 0;
    }

    public function isNullable()
    {
        return false;
    }

    public function getVariants()
    {
        return [];
    }

    public function normalize($value, $isMandatory)
    {
        if (strval(intval($value)) != strval($value)) {
            throw new ValidateException(__("Укажите целое число"));
        }

        return intval($value);
    }

    public function getSQLType()
    {
        return "int(" . $this->getSize() . ")";
    }

    /**
     * @return FormInput
     */
    protected function makeInput()
    {
        return App::make(FormInput::class);
    }

}