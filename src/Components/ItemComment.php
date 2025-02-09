<?php

namespace Nazmulpcc\HnPhpQt\Components;

use Carbon\Carbon;
use Nazmulpcc\HnPhpQt\Helpers\StyleSheet;
use Nazmulpcc\HnPhpQt\Models\Item;
use Qt\TextFormat;
use Qt\TextInteractionFlag;
use Qt\Widgets\QLabel;
use Qt\Widgets\QSizePolicy;
use Qt\Widgets\QVBoxLayout;
use Qt\Widgets\QWidget;

class ItemComment extends QWidget
{
    public function __construct(protected Item $comment, protected int $depth = 0)
    {
        parent::__construct();
        $this->setSizePolicy(QSizePolicy::Expanding, QSizePolicy::Expanding);
        $leftMargin = $this->depth * 10;
        StyleSheet::apply($this, [
            'border-top' => '1px solid #555',
            'margin-top' => '5px',
            'padding-top' => '5px',
            'margin-left' => "{$leftMargin}px",
        ]);
        $layout = new QVBoxLayout();
        $this->setLayout($layout);

        $author = new QLabel(sprintf('%s - %s', $this->comment->by ?? '', Carbon::parse($this->comment->time)->diffForHumans()));
        StyleSheet::apply($author, [
            'color' => '#cecece',
            'font-weight' => 'bold'
        ]);
        $layout->addWidget($author);

        $layout->addWidget($comment = new QLabel($this->comment->text ?? ''));
        $comment->setWordWrap(true);
        $comment->setOpenExternalLinks(true);
        $comment->setTextInteractionFlags(TextInteractionFlag::TextBrowserInteraction);
        $comment->setTextFormat(TextFormat::RichText);

        $replies = $this->comment->comments();

        if (count($replies) > 0) {
            foreach ($replies as $reply) {
                $layout->addWidget(new ItemComment($reply, $this->depth + 1));
            }
        }
    }
}