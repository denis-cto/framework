<?php

use PHPUnit\Framework\TestCase;
use Pina\Components\Schema;
use Pina\Components\Field;

class SchemaTest extends TestCase
{

    public function testException()
    {
        
        $schema = new Schema();
        $schema->add(Field::make('order_id', 'Заказ'));
        $schema->add(Field::make('name', 'ФИО'));
        $schema->pushProcessor(function($item) {
            $item['order_id'] = '<a href="/orders/'.$item['order_id'].'">'.$item['order_id'].' at '.$item['date'].'</a>';
            return $item;
        });
        
        $line = ['order_id' => '12', 'date' => '12.12.2020', 'name' => 'Ivan Ivanov'];
        $actual = $schema->makeFlatLine($schema->process($line));
        $this->assertEquals(['<a href="/orders/12">12 at 12.12.2020</a>', 'Ivan Ivanov'], $actual);
        
        $schema->forgetField('order_id');
        $actual = $schema->makeFlatLine($line);
        $this->assertEquals(['Ivan Ivanov'], $actual);
        
        $schema->forgetField('123');
        $actual = $schema->makeFlatLine($line);
        $this->assertEquals(['Ivan Ivanov'], $actual);
        
        $schema->forgetField('name');
        $actual = $schema->makeFlatLine($line);
        $this->assertEquals([], $actual);
        
        
    }
    
    public function testValidate()
    {
        $schema = new Schema();
        $schema->add('order_id', 'Номер заказа', 'string');
        $schema->add('name', 'ФИО', 'string');
        
        list($errors, $record) = $schema->validate([
            'order_id' => 12,
            'name' => str_repeat('A', 512),
        ]);
        
        $this->assertEquals([], $errors);
        $this->assertEquals(str_repeat('A', 512), $record['name']);
        
        list($errors, $record) = $schema->validate([
            'order_id' => 12,
            'name' => str_repeat('A', 513),
        ]);
        
        $this->assertEquals([['Укажите значение короче. Максимальная длина 512 символов', 'name']], $errors);
    }
}