<?php

namespace Nazmulpcc\HnPhpQt;

use Qt\Core\QObject;

class Timer extends QObject
{
    protected $callback;

    public function setCallback(callable $callback): void
    {
        $this->callback = $callback;
    }

    protected function timerEvent(int $timerId): void
    {
        call_user_func($this->callback);
    }
}
