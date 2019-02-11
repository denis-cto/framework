<?php

namespace Pina;

use Pina\DB\StructureParser;
use Pina\DB\Structure;

/*
 * Базовый класс для работы с таблицами, содержит мета-информацию о таблицах 
 * и базовые методы, наследуется от конструктора запросов
 * 
 * @author Alex Yashin
 * @copyright 2015
 */

class TableDataGateway extends SQL
{

    const LOAD_BUFFER_LIMIT = 1024000;

    protected static $table = "";
    protected static $fields = false;
    protected static $indexes = [];
    protected static $engine = "ENGINE=InnoDB DEFAULT CHARSET=utf8";
    protected $orderBy = "";
    protected $context = array();

    public function getTriggers()
    {
        return array();
    }

    public function getConstraints()
    {
        return array();
    }

    public function __construct()
    {
        parent::__construct($this->getTable());
    }

    public function getTable()
    {
        return static::$table;
    }

    public function getFields()
    {
        return static::$fields;
    }

    public function getIndexes()
    {
        return static::$indexes;
    }

    public function getEngine()
    {
        return static::$engine;
    }

    public function getUpgrades()
    {
        if (empty(static::$fields)) {
            return array();
        }

        $r = array();
        if (!in_array(static::$table, $this->db->col("SHOW TABLES"))) {
            $r[] = $this->getStructure()->makeCreateTable();
        } else {
            $path = $this->getStructure()->makeAlterTable($this->getTable(), $this->getExistedStructure());
            if (!empty($path)) {
                $r[] = $path;
            }
        }
        return $r;
    }

    public function getStructure()
    {
        $parser = new StructureParser;
        $structure = new Structure;
        $structure->setFields($parser->parseGatewayFields($this->getFields()));
        $structure->setIndexes($parser->parseGatewayIndexes($this->getIndexes()));
        $structure->setConstraints($this->getConstraints());
        return $structure;
    }

    public function getExistedStructure()
    {
        $parser = new StructureParser;
        $parser->parse(
                $this->db->one("SHOW CREATE TABLE `" . $this->getTable() . "`")
        );
        return $parser->getStructure();
    }

    public function getEnumVariants($field)
    {
        if (empty(static::$fields[$field])) {
            return [];
        }

        $meta = static::$fields[$field];
        if (!preg_match('/enum\((.*)\)/si', $meta, $matches)) {
            return [];
        }

        $fields = explode(',', $matches[1]);
        array_walk($fields, function(&$s) {
            $s = trim(trim($s, "'\""));
        });
        return $fields;
    }

    /*
     * Возвращает экземпляр конкретного класса
     * @return TableDataGateway
     */

    public static function instance()
    {
        $cl = get_called_class();
        return new $cl();
    }

    public function context($field, $value)
    {
        $this->context[$field] = $value;
        return $this->whereBy($field, $value);
    }

    public function hasField($field)
    {
        return isset(static::$fields[$field]);
    }

    public function find($id)
    {
        return $this->whereId($id)->first();
    }

    public function id()
    {
        return $this->value($this->primaryKey());
    }

    protected function adjustDataAndFields(&$data, &$fields)
    {
        if (!empty($fields)) {
            $fields = array_intersect($fields, array_keys(static::$fields));
        } else {
            $fields = array_keys(static::$fields);
        }

        foreach ($this->context as $field => $value) {
            if (!isset($data[0])) {
                $data[$field] = $value;
            } else {
                foreach ($data as $k => $v) {
                    $data[$k][$field] = $value;
                }
            }
        }
    }

    protected function primaryKey()
    {
        if (empty(static::$indexes['PRIMARY KEY'])) {
            return '';
        }

        if (is_array(static::$indexes['PRIMARY KEY'])) {
            return static::$indexes['PRIMARY KEY'][0];
        }

        return static::$indexes['PRIMARY KEY'];
    }

    protected function getOnDuplicateKeys($keys)
    {
        $primaryKeys = !empty($this->indexes['PRIMARY KEY']) ? $this->indexes['PRIMARY KEY'] : array();
        if (!is_array($primaryKeys)) {
            $primaryKeys = array($primaryKeys);
        }

        return array_diff($keys, $primaryKeys);
    }

    public function makeInsert($data = array(), $fields = false, $cmd = 'INSERT')
    {
        $this->adjustDataAndFields($data, $fields);

        return parent::makeInsert($data, $fields, $cmd);
    }

    public function makePut($data, $fields = false)
    {
        $this->adjustDataAndFields($data, $fields);
        return parent::makePut($data, $fields);
    }

    public function update($data, $fields = false)
    {
        if (empty($data)) {
            return false;
        }

        $this->adjustDataAndFields($data, $fields);
        return parent::update($data, $fields);
    }

    public function whereId($id)
    {
        return $this->whereBy($this->primaryKey(), $id);
    }

    public function whereNotId($id)
    {
        return $this->whereNotBy($this->primaryKey(), $id);
    }

    public function selectAllExcept($field)
    {
        $excludedFields = is_array($field) ? $field : explode(",", $field);
        array_walk($excludedFields, 'trim');
        $selectedFields = array_diff(array_keys(static::$fields), $excludedFields);
        foreach ($selectedFields as $selectedField) {
            $this->select($selectedField);
        }
        return $this;
    }

    public function enabled()
    {
        $prefix = str_replace("cody_", "", $this->table);

        return $this->whereBy($prefix . "_enabled", 'Y');
    }

    public function getSorting($s)
    {

        if (empty($this->sorts) || empty($s)) {
            return '';
        }

        $order = '';
        $ss = explode(',', $s);
        foreach ($ss as $k => $v) {
            $v = trim($v);

            $isAsc = true;
            if ($v[0] == '-') {
                $isAsc = false;
                $v = trim(substr($v, 1));
            }

            if (empty($this->sorts[$v])) {
                continue;
            }

            if (!empty($order)) {
                $order .= ',';
            }
            $order .= $this->sorts[$v] . ' ' . ($isAsc ? 'asc' : 'desc');
        }

        if (empty($order)) {
            return '';
        }

        return $order;
    }

    public function sort($s)
    {
        return $this->orderBy($this->getSorting($s));
    }

    public function reorder($ids, $field = 'order')
    {
        if (!isset(static::$fields[$field])) {
            return;
        }

        $gw = clone($this);

        $orders = $gw->whereId($ids)->orderBy($field, 'asc')->column($field);

        $max = max($orders);

        $last = null;
        $diff = 0;
        foreach ($orders as $k => $v) {
            $orders[$k] = intval($v + $diff);
            if ($last !== null && $orders[$k] == $last) {
                $diff ++;
                $orders[$k] ++;
            }
            $last = $orders[$k];
        }

        if ($diff > 0) {
            $gw = clone($this);
            $gw->whereBetween($field, $max, 2147483647 - $diff - 1)->increment($field, $diff + 1);
        }
        $i = 0;
        foreach ($ids as $id) {
            $order = $orders[$i++];
            $gw = clone($this);
            $gw->whereId($id)->update([$field => intval($order)]);
        }
    }

    /* Проверяет поля массива $data
     * на соответствие размерности полей БД mysql.
     * При несоответствии помещает сообщения в Request::error
     */

    public function validate($data)
    {
        $errors = [];
        foreach ($data as $k => $v) {
            $matches = array();
            if (preg_match("/(varchar|decimal)\((\d+)(,(\d+))?\)/i", static::$fields[$k], $matches)) {
                $length = 0;
                $type = strtolower($matches[1]);
                $maxLength = strtolower($matches[2]);
                switch ($type) {
                    case 'varchar':
                        $length = strlen($v);
                        break;
                    case 'decimal':
                        $length = strlen(floor(abs($v)));
                        break;
                }
                if ($maxLength >= $length) {
                    continue;
                }
                $errors[] = [
                    'length',
                    $k,
                    $maxLength,
                    $length,
                ];
            } else if ($variants = $this->getEnumVariants($k)) {
                if (!in_array($v, $variants)) {
                    $errors[] = [
                        'enum',
                        $k,
                        $variants,
                        $v,
                    ];
                }
            }
        }
        return $errors;
    }

    /*
     * $schema = array("file_field" => "table_field");
     */

    public function load($schema, $reader)
    {
        $cnt = 0;
        $buffer = '';
        $data = $reader->fetch();
        foreach ($data as $line) {
            $prepared = [];
            foreach ($schema as $sourceKey => $targetKey) {
                if (isset($line[$sourceKey])) {
                    $prepared[$targetKey] = $line[$sourceKey];
                }
            }
            list($ks, $valueCondition) = $this->getKeyValuesCondition($prepared, $schema);
            if (strlen($values) > self::LOAD_BUFFER_LIMIT) {
                $this->db->query("REPLACE INTO `" . $this->from . "` " . join($schema) . " VALUES " . $buffer);
                $cnt += $this->db->affectedRows();
            }
            $buffer .= $valueCondition;
        }
        if (!empty($buffer)) {
            $this->db->query("REPLACE INTO `" . $this->from . "` " . join($schema) . " VALUES " . $buffer);
            $cnt += $this->db->affectedRows();
        }
        return $cnt;
    }

    public function loadCSV($schema, $path)
    {
        $csv = \League\Csv\Reader::createFromPath($path);
        return $this->load($schema, $csv);
    }

}
