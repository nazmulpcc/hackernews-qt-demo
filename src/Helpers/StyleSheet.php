<?php

namespace Nazmulpcc\HnPhpQt\Helpers;

use Qt\Widgets\QWidget;

class StyleSheet
{
    public static function apply(QWidget $widget, array $styles): void
    {
        if (empty($widget->objectName())) {
            $widget->setObjectName('widget-' . uniqid());
        }
        $id = $widget->objectName();
        $style = "#{$id}{";
        foreach ($styles as $property => $value) {
            $style .= "{$property}: {$value};";
        }
        $style .= "}";
        $widget->setStyleSheet($style);
    }
}