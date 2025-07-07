<?php

namespace Nazmulpcc\HnPhpQt;

use Qt\Core\QObject;

class QtEventLoopTimer extends QObject
{
    private int $timerId;

    /**
     * @var callable
     */
    private $callback;

    public function start(int $interval = 50): void
    {
        if (isset($this->timerId)) {
            return;
        }
        $this->timerId = $this->startTimer($interval);
    }

    public function stop(): void
    {
        if (!isset($this->timerId)) {
            return;
        }
        $this->killTimer($this->timerId);
        unset($this->timerId);
    }

    public function onTick(callable $callback): void
    {
        $this->callback = $callback;
    }

    protected function timerEvent($timerId): void
    {
        call_user_func($this->callback);
    }
}