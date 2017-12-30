<?php

namespace Amp\SSH;

use function Amp\asyncCall;

abstract class EventEmitter
{
    /** @var \Closure[][] */
    private $closures = [];

    public function emit($event, $data): void
    {
        if (!array_key_exists($event, $this->closures)) {
            return;
        }

        foreach ($this->closures[$event] as $closure) {
            asyncCall($closure, $data);
        }
    }

    public function each($event, \Closure $callback): string
    {
        if (!array_key_exists($event, $this->closures)) {
            $this->closures[$event] = [];
        }

        $id = spl_object_hash($callback);
        $this->closures[$event][$id] = $callback;

        return $id;
    }

    public function once($event, \Closure $callback): void
    {
        $eventId = $this->each($event, function ($event) use ($callback, &$eventId) {
            if ($callback($event)) {
                $this->remove($eventId);
            }
        });
    }

    public function remove($eventId): void
    {
        if ($eventId instanceof \Closure) {
            $eventId = spl_object_hash($eventId);
        }

        foreach ($this->closures as $eventName => $closures) {
            if (array_key_exists($eventId, $closures)) {
                unset($this->closures[$eventName][$eventId]);
            }
        }
    }
}