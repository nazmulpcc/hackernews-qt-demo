<?php

namespace Nazmulpcc\HnPhpQt;

use Nazmulpcc\HnPhpQt\Timer as QtTimer;
use Qt\Core\QObject;
use React\EventLoop\LoopInterface;
use React\EventLoop\SignalsHandler;
use React\EventLoop\Tick\FutureTickQueue;
use React\EventLoop\Timer\Timer;
use React\EventLoop\Timer\Timers;
use React\EventLoop\TimerInterface;

final class QtEventLoop implements LoopInterface
{
    /** @internal */
    const MICROSECONDS_PER_SECOND = 1000000;

    private $futureTickQueue;
    private $timers;
    private $readStreams = array();
    private $readListeners = array();
    private $writeStreams = array();
    private $writeListeners = array();
    private $running;
    private $pcntl = false;
    private $pcntlPoll = false;
    private $signals;
    private QtTimer $timer;

    public function __construct()
    {
        $this->futureTickQueue = new FutureTickQueue();
        $this->timers = new Timers();
        $this->pcntl = \function_exists('pcntl_signal') && \function_exists('pcntl_signal_dispatch');
        $this->pcntlPoll = $this->pcntl && !\function_exists('pcntl_async_signals');
        $this->signals = new SignalsHandler();

        // prefer async signals if available (PHP 7.1+) or fall back to dispatching on each tick
        if ($this->pcntl && !$this->pcntlPoll) {
            \pcntl_async_signals(true);
        }
    }

    public function addReadStream($stream, $listener)
    {
        $key = (int) $stream;

        if (!isset($this->readStreams[$key])) {
            $this->readStreams[$key] = $stream;
            $this->readListeners[$key] = $listener;
        }
    }

    public function addWriteStream($stream, $listener)
    {
        $key = (int) $stream;

        if (!isset($this->writeStreams[$key])) {
            $this->writeStreams[$key] = $stream;
            $this->writeListeners[$key] = $listener;
        }
    }

    public function removeReadStream($stream)
    {
        $key = (int) $stream;

        unset(
            $this->readStreams[$key],
            $this->readListeners[$key]
        );
    }

    public function removeWriteStream($stream)
    {
        $key = (int) $stream;

        unset(
            $this->writeStreams[$key],
            $this->writeListeners[$key]
        );
    }

    public function addTimer($interval, $callback)
    {
        $timer = new Timer($interval, $callback, false);

        $this->timers->add($timer);

        return $timer;
    }

    public function addPeriodicTimer($interval, $callback)
    {
        $timer = new Timer($interval, $callback, true);

        $this->timers->add($timer);

        return $timer;
    }

    public function cancelTimer(TimerInterface $timer)
    {
        $this->timers->cancel($timer);
    }

    public function futureTick($listener)
    {
        $this->futureTickQueue->add($listener);
    }

    public function addSignal($signal, $listener)
    {
        if ($this->pcntl === false) {
            throw new \BadMethodCallException('Event loop feature "signals" isn\'t supported by the "StreamSelectLoop"');
        }

        $first = $this->signals->count($signal) === 0;
        $this->signals->add($signal, $listener);

        if ($first) {
            \pcntl_signal($signal, array($this->signals, 'call'));
        }
    }

    public function removeSignal($signal, $listener)
    {
        if (!$this->signals->count($signal)) {
            return;
        }

        $this->signals->remove($signal, $listener);

        if ($this->signals->count($signal) === 0) {
            \pcntl_signal($signal, \SIG_DFL);
        }
    }

    public function run()
    {
        $this->running = true;

        $this->timer = new QtTimer();

        $this->timer->setCallback(function () {
            $this->futureTickQueue->tick();

            $this->timers->tick();

            // Future-tick queue has pending callbacks ...
            if (!$this->running || !$this->futureTickQueue->isEmpty()) {
                $timeout = 0;

                // There is a pending timer, only block until it is due ...
            } elseif ($scheduledAt = $this->timers->getFirst()) {
                $timeout = $scheduledAt - $this->timers->getTime();
                if ($timeout < 0) {
                    $timeout = 0;
                } else {
                    // Convert float seconds to int microseconds.
                    // Ensure we do not exceed maximum integer size, which may
                    // cause the loop to tick once every ~35min on 32bit systems.
                    $timeout *= self::MICROSECONDS_PER_SECOND;
                    $timeout = $timeout > \PHP_INT_MAX ? \PHP_INT_MAX : (int)$timeout;
                }

                // The only possible event is stream or signal activity, so wait forever ...
            } elseif ($this->readStreams || $this->writeStreams || !$this->signals->isEmpty()) {
                $timeout = null;

                // There's nothing left to do ...
            } else {
                return;
            }

            $this->waitForStreamActivity(0);
        });

        $this->timer->startTimer(50, 1);
    }

    public function stop()
    {
        $this->running = false;
    }

    /**
     * Wait/check for stream activity, or until the next timer is due.
     *
     * @param integer|null $timeout Activity timeout in microseconds, or null to wait forever.
     */
    private function waitForStreamActivity($timeout)
    {
        $read  = $this->readStreams;
        $write = $this->writeStreams;

        $available = $this->streamSelect($read, $write, $timeout);
        if ($this->pcntlPoll) {
            \pcntl_signal_dispatch();
        }
        if (false === $available) {
            // if a system call has been interrupted,
            // we cannot rely on it's outcome
            return;
        }

        foreach ($read as $stream) {
            $key = (int) $stream;

            if (isset($this->readListeners[$key])) {
                \call_user_func($this->readListeners[$key], $stream);
            }
        }

        foreach ($write as $stream) {
            $key = (int) $stream;

            if (isset($this->writeListeners[$key])) {
                \call_user_func($this->writeListeners[$key], $stream);
            }
        }
    }

    /**
     * Emulate a stream_select() implementation that does not break when passed
     * empty stream arrays.
     *
     * @param array    $read    An array of read streams to select upon.
     * @param array    $write   An array of write streams to select upon.
     * @param int|null $timeout Activity timeout in microseconds, or null to wait forever.
     *
     * @return int|false The total number of streams that are ready for read/write.
     *     Can return false if stream_select() is interrupted by a signal.
     */
    private function streamSelect(array &$read, array &$write, $timeout)
    {
        if ($read || $write) {
            // We do not usually use or expose the `exceptfds` parameter passed to the underlying `select`.
            // However, Windows does not report failed connection attempts in `writefds` passed to `select` like most other platforms.
            // Instead, it uses `writefds` only for successful connection attempts and `exceptfds` for failed connection attempts.
            // We work around this by adding all sockets that look like a pending connection attempt to `exceptfds` automatically on Windows and merge it back later.
            // This ensures the public API matches other loop implementations across all platforms (see also test suite or rather test matrix).
            // Lacking better APIs, every write-only socket that has not yet read any data is assumed to be in a pending connection attempt state.
            // @link https://docs.microsoft.com/de-de/windows/win32/api/winsock2/nf-winsock2-select
            $except = null;
            if (\DIRECTORY_SEPARATOR === '\\') {
                $except = array();
                foreach ($write as $key => $socket) {
                    if (!isset($read[$key]) && @\ftell($socket) === 0) {
                        $except[$key] = $socket;
                    }
                }
            }

            /** @var ?callable $previous */
            $previous = \set_error_handler(function ($errno, $errstr) use (&$previous) {
                // suppress warnings that occur when `stream_select()` is interrupted by a signal
                // PHP defines `EINTR` through `ext-sockets` or `ext-pcntl`, otherwise use common default (Linux & Mac)
                $eintr = \defined('SOCKET_EINTR') ? \SOCKET_EINTR : (\defined('PCNTL_EINTR') ? \PCNTL_EINTR : 4);
                if ($errno === \E_WARNING && \strpos($errstr, '[' . $eintr . ']: ') !== false) {
                    return;
                }

                // forward any other error to registered error handler or print warning
                return ($previous !== null) ? \call_user_func_array($previous, \func_get_args()) : false;
            });

            try {
                $ret = \stream_select($read, $write, $except, $timeout === null ? null : 0, $timeout);
                \restore_error_handler();
            } catch (\Throwable $e) { // @codeCoverageIgnoreStart
                \restore_error_handler();
                throw $e;
            } catch (\Exception $e) {
                \restore_error_handler();
                throw $e;
            } // @codeCoverageIgnoreEnd

            if ($except) {
                $write = \array_merge($write, $except);
            }
            return $ret;
        }

        if ($timeout > 0) {
            \usleep($timeout);
        } elseif ($timeout === null) {
            // wait forever (we only reach this if we're only awaiting signals)
            // this may be interrupted and return earlier when a signal is received
            \sleep(PHP_INT_MAX);
        }

        return 0;
    }
}
