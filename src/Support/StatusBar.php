<?php
namespace Funseco\Widgets\Support;

use Funseco\Core\SharedKernel\UI\Theme\ThemeKit;
use Funseco\Core\SharedKernel\UI\WidgetRegistry;


/** The "status-bar button" template of spec 1.3 (4.6 x 6.5). */
final class StatusBar
{
    /**
     * @param array $o x, y, style, substyle, action (int|null), url, manialink, addplayerid,
     *                 body (inner XML), caption lines are added by the helpers below
     */
    public static function frame(array $o): string
    {
        $link = '';
        if (!empty($o['action'])) {
            $link = ' action="'.$o['action'].'"';
        } elseif (!empty($o['url'])) {
            $link = ' url="'.Ui::esc($o['url']).'"';
        } elseif (!empty($o['manialink'])) {
            $link = ' manialink="'.Ui::esc($o['manialink']).'"'.(!empty($o['addplayerid']) ? ' addplayerid="1"' : '');
        }
        return '<frame posn="'.$o['x'].' '.$o['y'].' 0"><format textsize="1"/>'
            .ThemeKit::current()->surface('status_button', 4.6, 6.5, $o['style'], $o['substyle'], ltrim($link))
            .($o['body'] ?? '').'</frame>';
    }

    /** Frame for a config widget: position and background from manialinks.<widget> (optionally .<sub>). */
    public static function for(string $widget, string $body, ?string $sub = null, ?int $action = null, array $extra = []): string
    {
        $key = $sub ? "$widget.$sub" : $widget;
        $grid = app(WidgetRegistry::class);
        $placed = $sub === null && $grid->has($widget) ? $grid->placement($widget) : null;
        $x = $placed !== null ? $placed->x : (float) config("manialinks.$key.pos_x");
        $y = $placed !== null ? $placed->y : (float) config("manialinks.$key.pos_y");

        return self::frame([
            'x'         => $x,
            'y'         => $y,
            'style'     => config("manialinks.$key.background_style", 'BgsPlayerCard'),
            'substyle'  => config("manialinks.$key.background_substyle", 'ProgressBar'),
            'action'    => $action,
            'body'      => $body,
        ] + $extra);
    }

    public static function value(string $text, string $attrs = '', string $pos = '2.3 -3.4 0.1', string $size = '3.65 2'): string
    {
        return '<label posn="'.$pos.'" sizen="'.$size.'" halign="center" '.$attrs.' text="'.Ui::esc($text).'"/>';
    }

    public static function caption(string $widget, string $text, string $pos = '2.3 -4.9 0.1', ?string $colour = null): string
    {
        $colour ??= (string) config("manialinks.$widget.text_color", 'FC0F');

        return '<label posn="'.$pos.'" sizen="6.35 2" halign="center" scale="0.6" textcolor="'.$colour.'" text="'.Ui::esc($text).'"/>';
    }

    /** Two-line caption (next environment, next gamemode, eyepiece score logo). */
    public static function caption2(string $widget, string $one, string $two): string
    {
        return self::caption($widget, $one, '2.3 -4.2 0.002').self::caption($widget, $two, '2.3 -5.2 0.002');
    }

    public static function icon(string $style, string $substyle, string $pos, string $size, string $attrs = ''): string
    {
        return '<quad posn="'.$pos.'" sizen="'.$size.'" style="'.$style.'" substyle="'.$substyle.'" '.$attrs.'/>';
    }

    public static function action(string $name): int
    {
        return Ids::action($name);
    }
}
