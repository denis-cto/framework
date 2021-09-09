<?php


namespace Pina\Events;

use Exception;
use \Pina\Command;

class Event
{
    /** @var Command[][] */
    protected $handlers = [];

    public function __construct()
    {
        $this->handlers[\Pina\Event::PRIORITY_HIGH] = [];
        $this->handlers[\Pina\Event::PRIORITY_NORMAL] = [];
        $this->handlers[\Pina\Event::PRIORITY_LOW] = [];
    }

    public function subscribe(Command $handler, $priority = \Pina\Event::PRIORITY_NORMAL)
    {
        if (!isset($this->handlers[$priority])) {
            throw new Exception();
        }
        $this->handlers[$priority][] = $handler;
    }

    public function trigger($data)
    {
        foreach ($this->handlers as $priority => $hs) {
            foreach ($hs as $handler) {
                $handler($data);
            }
        }
    }

}

function queue($className, $priority = \Pina\Event::PRIORITY_NORMAL)
{
    return new QueueableCommand($className, $priority);
}