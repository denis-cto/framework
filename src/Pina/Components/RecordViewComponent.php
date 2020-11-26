<?php

namespace Pina\Components;

use Pina\Controls\FormStatic;

class RecordViewComponent extends RecordData //implements ComponentInterface
{

    public function build()
    {
        foreach ($this->schema as $field) {
            $title = $field->getTitle();
            $key = $field->getKey();
            $value = $field->draw($this->data);
            $static = $this->makeFormStatic()->setName($key)->setTitle($title)->setValue($value);
            $this->append($static);
        }
        
    }

    /**
     * @return \Pina\Controls\FormStatic
     */
    protected function makeFormStatic()
    {
        return $this->control(\Pina\Controls\FormStatic::class);
    }

}
