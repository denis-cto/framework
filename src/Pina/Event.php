<?php

namespace Pina;

class Event extends Request
{
    
    const PRIORITY_HIGH = 0;
    const PRIORITY_NORMAL = 1;
    const PRIORITY_LOW = 2;

    private static $syncHandlers = [];
    private static $asyncHandlers = [];
    
    public static function data()
    {
        return static::top()->data(); 
    }
    
    public static function subscribe($module, $event, $script = '', $priority = Event::PRIORITY_NORMAL)
    {
        $handler = new Events\ModuleEventHandler($module->getNamespace(), $script);
        App::events()->subscribe($event, $handler, $priority);
    }

    public static function subscribeSync($module, $event, $script = '', $priority = Event::PRIORITY_NORMAL)
    {
        $handler = new Events\ModuleEventHandler($module->getNamespace(), $script);
        App::events()->subscribeSync($event, $handler, $priority);
    }

    public static function trigger($event, $data = '')
    {
        App::events()->trigger($event, $data);
    }

}
