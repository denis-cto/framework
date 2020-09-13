<?php

use PHPUnit\Framework\TestCase;
use Pina\App;
use Pina\Components\Registry;
use Pina\Components\ListData;
use Pina\Components\TableComponent;
use Pina\Components\ListComponent;
use Pina\Components\Schema;

class DataTest extends TestCase
{

    public function test()
    {
        $schema = new Schema();
        $schema->add('id', '#');
        $schema->add('event', 'Событие');
        $schema->add('created_at', 'Дата создания');

        $data = [
            ['id' => 1, 'event' => 'order.paid', 'created_at' => '2020-01-02 03:04:05'],
            ['id' => 2, 'event' => 'order.canceled', 'created_at' => '2020-01-02 04:05:06'],
            ['id' => 3, 'event' => 'order.returned', 'created_at' => '2020-01-02 05:06:07'],
        ];
        
        $expectedHtml = '<table>'
            . '<tr><th>#</th><th>Событие</th><th>Дата создания</th></tr>'
            . '<tr><td>1</td><td>order.paid</td><td>2020-01-02 03:04:05</td></tr>'
            . '<tr><td>2</td><td>order.canceled</td><td>2020-01-02 04:05:06</td></tr>'
            . '<tr><td>3</td><td>order.returned</td><td>2020-01-02 05:06:07</td></tr>'
            . '</table>';

        $list = new ListData();
        $list->load($data, $schema);
        $html = TableComponent::instance()->basedOn($list)->draw();
        $this->assertEquals($expectedHtml, $html);

        $html = ListComponent::instance()->basedOn(TableComponent::instance()->basedOn($list))->select('event')->draw();
        $this->assertEquals(
            '<ul>'
            . '<li>order.paid</li>'
            . '<li>order.canceled</li>'
            . '<li>order.returned</li>'
            . '</ul>', $html
        );
        $this->assertEquals(
            ListComponent::instance()->basedOn(TableComponent::instance()->basedOn($list))->select('event')->draw(), ListComponent::instance()->basedOn($list)->select('event')->draw()
        );
        $this->assertEquals(
            ListComponent::instance()->basedOn(TableComponent::instance()->basedOn($list))->select('id')->draw(), ListComponent::instance()->basedOn($list)->select('id')->draw()
        );

        $html = $list->turnTo("table")->draw();
        $this->assertEquals($expectedHtml, $html);
    }

}
