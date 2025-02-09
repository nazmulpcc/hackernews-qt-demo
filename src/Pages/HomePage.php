<?php

namespace Nazmulpcc\HnPhpQt\Pages;

use Nazmulpcc\HnPhpQt\Components\ItemComment;
use Nazmulpcc\HnPhpQt\Components\NewsItem;
use Nazmulpcc\HnPhpQt\HackerNewsClient;
use Nazmulpcc\HnPhpQt\Models\Item;
use Qt\Widgets\QHBoxLayout;
use Qt\Widgets\QLabel;
use Qt\Widgets\QMainWindow;
use Qt\Widgets\QScrollArea;
use Qt\Widgets\QSplitter;
use Qt\Widgets\QStackedWidget;
use Qt\Widgets\QVBoxLayout;
use Qt\Widgets\QWidget;
use function React\Promise\all;

class HomePage extends Page
{
    protected QWidget $noContent;

    protected QScrollArea $itemView;

    protected QStackedWidget $contentArea;

    protected QVBoxLayout $itemListLayout;

    protected QScrollArea $itemListWidget;

    protected QVBoxLayout $itemViewLayout;

    public function __construct(?QWidget $parent = null, int $windowFlags = 0)
    {
        parent::__construct($parent, $windowFlags);
        $this->setObjectName('HomePage');
        $this->setLayout(new QVBoxLayout());
    }

    public function render(QMainWindow $window): void
    {
        $window->setMinimumHeight(800);
        $window->setMinimumWidth(1200);
        $window->setWindowTitle('Hacker News Reader');

        $this->createItemListWidget();
        $this->createContentAreaWidget();

        $splitter = new QSplitter();
        $splitter->addWidget($this->itemListWidget);
        $splitter->addWidget($this->contentArea);
        $window->setCentralWidget($splitter);

        $client = new HackerNewsClient();
        $client->getTopStories()
            ->then(function (array $data) use ($window, $client) {
                $data = array_slice($data, 0, 10);
                $promises = [];
                foreach ($data as $id) {
                    $promises[] = $client->getItem($id)->then(function (Item $item) {
                        $this->itemListLayout->addWidget(new NewsItem($item, $this));
                    });
                }
                all($promises)->then(fn() => $this->itemListLayout->addStretch(1));
            });

        $window->dumpObjectTree();
    }

    public function setItem(Item $item): void
    {
        $rendering = false;
        $item->on('updated', function () use ($item, &$rendering) {
            if ($rendering) {
                return;
            }
            $rendering = true;
            if (isset($this->itemView)) {
                $this->contentArea->removeWidget($this->itemView);
                unset($this->itemView); // Remove the previous item view if it exists
            }
            $this->createItemViewWidget($item);
            $this->contentArea->addWidget($this->itemView);
            $this->contentArea->setCurrentWidget($this->itemView);
            $rendering = false;
        });
        $item->loadChildren();
    }

    protected function createItemListWidget(): void
    {
        $widget = new QWidget();
        $widget->setLayout($this->itemListLayout = new QVBoxLayout());
        $this->itemListWidget = new QScrollArea();
        $this->itemListWidget->setFixedWidth(400);
        $this->itemListWidget->setWidgetResizable(true);
        $this->itemListWidget->setWidget($widget);
    }

    protected function createNoContentWidget(): void
    {
        $this->noContent = new QWidget();
        $layout = new QHBoxLayout();
        $layout->addWidget(new QLabel('<h1 style="font-weight: normal; color: #aaa">No content available</h1>'));
        $this->noContent->setLayout($layout);
    }

    protected function createItemViewWidget(Item $item): void
    {
        $widget = new QWidget();
        $this->itemView = new QScrollArea();
        $widget->setLayout($this->itemViewLayout = new QVBoxLayout());
        $this->itemViewLayout->addWidget(new QLabel("<h2><a href='{$item->url}'>{$item->title}</a></h2>"));
        $this->itemViewLayout->addWidget(new QLabel(sprintf(
            '<span style="color: #aaa"><b>%s</b> | %s comments | %s</span>',
            $item->by,
            $item->commentCount(),
            date('M d, Y', $item->time),
        )));
        $this->itemViewLayout->addSpacing(20);

        foreach ($item->comments() as $comment) {
            $this->itemViewLayout->addWidget(new ItemComment($comment));
        }
        $this->itemView->setWidget($widget);
    }

    protected function createContentAreaWidget(): void
    {
        $this->createNoContentWidget();
        $this->contentArea = new QStackedWidget();
        $this->contentArea->addWidget($this->noContent);
    }
}