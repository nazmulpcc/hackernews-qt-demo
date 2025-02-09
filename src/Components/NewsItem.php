<?php

namespace Nazmulpcc\HnPhpQt\Components;

use Nazmulpcc\HnPhpQt\Helpers\StyleSheet;
use Nazmulpcc\HnPhpQt\Models\Item;
use Nazmulpcc\HnPhpQt\Pages\HomePage;
use Qt\Widgets\QLabel;
use Qt\Widgets\QLayout;
use Qt\Widgets\QPushButton;
use Qt\Widgets\QSizePolicy;
use Qt\Widgets\QVBoxLayout;
use Qt\Widgets\QWidget;

class NewsItem extends QWidget
{
    protected QLayout $layout;

    protected QPushButton $action;

    public function __construct(protected Item $item, protected HomePage $parent)
    {
        parent::__construct($parent);
        $this->setObjectName($id = "news-{$this->item->id}");
        $this->layout = new QVBoxLayout();
        $this->setMaximumWidth(400);
        $this->setLayout($this->layout);
        $this->setStyleSheet("#{$id}{
            border: 1px solid #555;
            border-radius: 5px;
        }");

        $this->layout->addWidget($this->createHeadingLabel());
        $this->layout->addWidget($this->createInformationLabel());
        $this->layout->addWidget($this->createActionButton());
    }

    protected function createHeadingLabel(): QLabel
    {
        $label = new QLabel($this->item->title);
        $label->setWordWrap(true);

        StyleSheet::apply($label, [
            'font-weight' => 'bold',
        ]);

        return $label;
    }

    protected function createInformationLabel(): QLabel
    {
        $text = sprintf(
            '<b style="color: #aaa">%s</b> | %s',
            $this->item->by,
            date('M d, Y', $this->item->time),
        );

        $label = new QLabel($text);

        StyleSheet::apply($label, [
            'font-size' => '12px',
            'color' => '#777',
        ]);

        return $label;
    }

    protected function createActionButton(): QPushButton
    {
        $this->action = new QPushButton('Read More');
        $this->action->onClicked([$this, 'handleAction']);
        $this->action->setSizePolicy(QSizePolicy::Fixed, QSizePolicy::Fixed);

        return $this->action;
    }

    protected function handleAction(): void
    {
        if ($this->action->text() === 'Read More') {
            $this->parent->setItem($this->item);
            $this->setActive(true);
        } else {
            $this->parent->setItem(false);
            $this->setActive(false);
        }
    }

    public function setActive(bool $active): void
    {
        $this->action->setText(
            $active ? 'Close' : 'Read More'
        );
    }
}