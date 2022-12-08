<?php


namespace Pina\Controls;

use Pina\App;
use Pina\Html;

class EditableRecordRow extends RecordRow
{
    protected $name = '';

    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function drawInner()
    {
        $content = '';
        $data = $this->record->getData();
        foreach ($this->record->getSchema()->getIterator() as $field) {
            if ($field->isHidden()) {
                continue;
            }
            $type = $field->getType();
            $name = $field->getKey();
            $value = isset($data[$name]) ? $data[$name] : null;
            $pk = $this->record->getSchema()->getPrimaryKey();
            $id = !empty($pk[0]) ? ($data[$pk[0]] ?? 0) : 0;

            $cell = App::type($type)->setContext($data)->makeControl($field, $value);
            $cell->setName($this->name . '[' . $id . '][' . $name . ']');
            $cell->setCompact();
            $content .= Html::tag('td', $cell);
        }
        return $content;
    }
}