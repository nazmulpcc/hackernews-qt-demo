<?php

namespace Nazmulpcc\HnPhpQt\Pages;

use Qt\Widgets\QMainWindow;
use Qt\Widgets\QWidget;

abstract class Page extends QWidget
{
    abstract public function render(QMainWindow $window): void;
}