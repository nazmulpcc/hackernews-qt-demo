<?php

namespace Nazmulpcc\HnPhpQt\Pages;

use Nazmulpcc\HnPhpQt\Application;
use Nazmulpcc\HnPhpQt\Components\NewsItem;
use Nazmulpcc\HnPhpQt\HackerNewsClient;
use Qt\Widgets\QHBoxLayout;
use Qt\Widgets\QLabel;
use Qt\Widgets\QLayout;
use Qt\Widgets\QLineEdit;
use Qt\Widgets\QMainWindow;
use Qt\Widgets\QPushButton;
use Qt\Widgets\QScrollArea;
use Qt\Widgets\QVBoxLayout;
use Qt\Widgets\QWidget;
use function React\Promise\all;

class HomePage extends Page
{
    protected QLayout $layout;

    public function __construct(?QWidget $parent = null, int $windowFlags = 0)
    {
        parent::__construct($parent, $windowFlags);
        $this->setObjectName('HomePage');
        $this->layout = new QVBoxLayout();
        $this->setLayout($this->layout);
    }

    public function layout(): ?QLayout
    {
        return $this->layout;
    }

    public function render(QMainWindow $window): void
    {
        $window->setFixedSize(800, 600);
        $window->setWindowTitle('Hacker News Reader');
        $scrollArea = new QScrollArea();
        $scrollArea->setWidgetResizable(true);
        $scrollArea->setWidget($this);
        $window->setCentralWidget($scrollArea);

        $client = new HackerNewsClient();
        $client->getTopStories()
            ->then(function (array $data) use ($window, $client) {
                $data = array_slice($data, 0, 10);
                $promises = [];
                foreach ($data as $id) {
                    $promises[] = $client->getItem($id)->then(function (array $item) {
                        $this->layout->addWidget(new NewsItem($item));
                    });
                }
            });
    }
}