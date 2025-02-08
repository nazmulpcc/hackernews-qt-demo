<?php

namespace Nazmulpcc\HnPhpQt\Components;

use Qt\Widgets\QLabel;
use Qt\Widgets\QLayout;
use Qt\Widgets\QVBoxLayout;
use Qt\Widgets\QWidget;

class NewsItem extends QWidget
{
    protected QLayout $layout;

    /**
     * @param array{by: string, descendants: int, id: int, kids: int[], score: int, time: int, title: string, type: string, url: string} $item
     */
    public function __construct(protected array $item)
    {
        parent::__construct();
        $this->setObjectName($id = "news-{$this->item['id']}");
        $this->layout = new QVBoxLayout();
        $this->setLayout($this->layout);
        $this->setStyleSheet("#{$id}{
            border: 1px solid #ccc;
            border-radius: 5px;
        }");
        $this->layout->addWidget(new QLabel($this->item['title']));
        $this->layout->addWidget(new QLabel(sprintf('By: %s', $this->item['by'] ?? 'Unknown')));
        $this->layout->addWidget(new QLabel(sprintf('Score: %d', $this->item['score'] ?? 0)));
    }
}