<?php

namespace Pina\Data;

class FieldSet
{
    /** @var Schema */
    protected $schema;
    /** @var Field[] */
    protected $fields = [];

    public function __construct($schema)
    {
        $this->schema = $schema;
    }

    public function add(Field $field)
    {
        $this->fields[] = $field;
    }

    public function count()
    {
        return count($this->fields);
    }

    public function calc($callable, $fieldKey, $fieldTitle, $fieldType = 'string')
    {
        $this->schema->pushDataProcessor(function ($item) use ($callable, $fieldKey) {
            $data = [];
            foreach ($this->fields as $f) {
                if (!isset($item[$f->getKey()])) {
                    continue;
                }
                $data[] = $item[$f->getKey()];
            }
            $item[$fieldKey] = $callable($data);
            return $item;
        });
        return $this->schema->add($fieldKey, $fieldTitle, $fieldType);
    }

    public function join($callable, $fieldKey, $fieldTitle, $fieldType = 'string')
    {
        foreach ($this->fields as $f) {
            $this->schema->forgetField($f->getKey());
        }
        return $this->calc($callable, $fieldKey, $fieldTitle, $fieldType);
    }

    public function printf($pattern, $fieldKey, $fieldTitle, $fieltType = 'string')
    {
        return $this->calc(function ($a) use ($pattern) {
            return vsprintf($pattern, $a);
        }, $fieldKey, $fieldTitle, $fieltType);
    }

    public function setNullable($nullable = true, $default = null)
    {
        foreach ($this->fields as $f) {
            $f->setNullable($nullable, $default);
        }
        return $this;
    }

    public function setMandatory($mandatory = true)
    {
        foreach ($this->fields as $f) {
            $f->setMandatory($mandatory);
        }
        return $this;
    }

    public function setStatic($static = true)
    {
        foreach ($this->fields as $f) {
            $f->setStatic($static);
        }
        return $this;
    }

    public function setHidden($hidden = true)
    {
        foreach ($this->fields as $f) {
            $f->setHidden($hidden);
        }
        return $this;
    }

    public function setAlias($key, $alias) {
        foreach ($this->fields as $f) {
            if ($f->getKey() == $key) {
                $f->setAlias($alias);
            }
        }
        return $this;
    }

    public function makeSchema()
    {
        $schema = new Schema();
        foreach ($this->fields as $f) {
            $schema->add(clone $f);
        }
        return $schema;
    }
}