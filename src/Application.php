<?php

namespace Nazmulpcc\HnPhpQt;

use Nazmulpcc\HnPhpQt\Pages\Page;
use Qt\Widgets\QApplication;
use Qt\Widgets\QMainWindow;
use React\EventLoop\Loop;

class Application
{
    protected static self $instance;

    protected QApplication $app;

    protected QMainWindow $window;

    public static function instance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected function __construct()
    {
        if(! extension_loaded('qt')) {
            throw new \Exception('The Qt extension is not loaded.');
        }

        $this->app = new QApplication();
        $this->window = new QMainWindow();
        Loop::set(new QtEventLoop());
    }

    public function window(): QMainWindow
    {
        return $this->window;
    }

    public function setPage(Page $page): void
    {
        $page->render($this->window);
    }

    public function run(): int
    {
        $this->window->show();
        return $this->app->exec();
    }
}