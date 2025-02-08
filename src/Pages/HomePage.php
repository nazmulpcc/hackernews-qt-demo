<?php

namespace Nazmulpcc\HnPhpQt\Pages;

use Nazmulpcc\HnPhpQt\Application;
use Nazmulpcc\HnPhpQt\HackerNewsClient;
use Qt\Widgets\QHBoxLayout;
use Qt\Widgets\QLabel;
use Qt\Widgets\QLayout;
use Qt\Widgets\QLineEdit;
use Qt\Widgets\QMainWindow;
use Qt\Widgets\QPushButton;
use Qt\Widgets\QVBoxLayout;
use Qt\Widgets\QWidget;

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
        $client = new HackerNewsClient();
        $client->getTopStories()->then(function (array $data) use ($client) {
            $data = array_slice($data, 0, 10);
            echo "Data count: " . count($data) . PHP_EOL;
            foreach ($data as $id) {
                $client->getItem($id)->then(function (array $item) {
                    $this->layout()->addWidget(new QLabel($item['title']));
                });
            }
        });
    }
}