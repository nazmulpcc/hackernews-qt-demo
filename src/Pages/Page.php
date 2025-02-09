<?php

namespace Nazmulpcc\HnPhpQt\Pages;

use Qt\Widgets\QLayout;
use Qt\Widgets\QMainWindow;
use Qt\Widgets\QWidget;

abstract class Page extends QWidget
{
    protected QLayout $layout;
    
    abstract public function render(QMainWindow $window): void;

    public function layout(): ?QLayout
    {
        return $this->layout;
    }

    public function setLayout(QLayout $layout): void
    {
        $this->layout = $layout;
        parent::setLayout($layout);
    }
}