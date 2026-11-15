<?php
namespace Funseco\Widgets\Windows;

use Funseco\Widgets\Support\Ids;
use Funseco\Widgets\Support\Ui;

/** The eyepiece window frame: main window (z 18.5) and sub-window dialog (z 21.5) of spec 3. */
final class Frame
{
    /** @param array{0: string, 1: string} $icon style, substyle */
    public static function main(string $title, array $icon, string $content, string $buttons = ''): string
    {
        $bg = Ui::window('window_bgcolor');
        $head = Ui::window('headline_bgcolor');
        $headText = Ui::window('headline_textcolor');
        $lightbox = Ui::bool(config('manialinks.style.window.lightbox.enabled'))
            ? Ui::quad('-64 48 18.49', '128 96', 'bgcolor="'.config('manialinks.style.window.lightbox.bgcolor').'"') : '';
        $footer = (string) config('widgets.branding.footer', '');

        return $lightbox.'<frame posn="-40.1 30.45 18.50">'
            .Ui::quad('0.8 -0.8 0.01', '78.4 53.7', 'bgcolor="'.$bg.'"')
            .Ui::styled('-0.2 0.2 0.04', '80.4 55.7', 'Bgs1InRace', 'BgCard3')
            .Ui::quad('0.8 -1.3 0.02', '78.4 3', 'bgcolor="'.$head.'"')
            .Ui::quad('0.8 -4.3 0.03', '78.4 0.1', 'bgcolor="FFF9"')
            .Ui::styled('1.8 -1 0.04', '3.2 3.2', $icon[0], $icon[1])
            .Ui::label('5.5 -1.9 0.04', '74 0', $title, 'textsize="2" scale="0.9" textcolor="'.$headText.'"')
            .Ui::quad('2.7 -54.1 0.04', '11 1', 'bgcolor="0000" action="'.Ids::action('open_help').'"')
            .Ui::label('2.7 -54.1 0.04', '30 1', $footer, 'textsize="1" scale="0.7" textcolor="000F"')
            .self::closeButton('77.4 1.3 0.05', 'window_close')
            .'<frame posn="52.2 -53.2 0.05">'.$buttons.'</frame>'
            .'<frame posn="2.5 -5.7 0.05">'.$content.'</frame>'
            .'</frame>';
    }

    public static function sub(string $title, array $icon, string $content): string
    {
        return '<frame posn="-19.8 15 21.5">'
            .Ui::quad('0.8 -0.8 0.01', '38 26', 'bgcolor="'.Ui::window('window_bgcolor').'"')
            .Ui::styled('-0.2 0.2 0.04', '39.7 27.85', 'Bgs1InRace', 'BgCard3')
            .Ui::quad('0.8 -1.3 0.02', '38 3', 'bgcolor="'.Ui::window('headline_bgcolor').'"')
            .Ui::quad('0.8 -4.3 0.03', '38 0.1', 'bgcolor="FFF9"')
            .Ui::styled('1.8 -1.6 0.04', '2.2 2.2', $icon[0], $icon[1])
            .Ui::label('4.5 -1.9 0.04', '37 0', $title, 'textsize="2" scale="0.9" textcolor="'.Ui::window('headline_textcolor').'"')
            .self::closeButton('36.8 1.3 0.05', 'sub_close')
            .$content.'</frame>';
    }

    public static function wrapMain(string $xml): string
    {
        return Ui::manialink('window', $xml);
    }

    public static function wrapSub(string $xml): string
    {
        return Ui::manialink('subwindow', $xml);
    }

    private static function closeButton(string $pos, string $action): string
    {
        return '<frame posn="'.$pos.'">'
            .Ui::styled('0 0 0.01', '4 4', 'Icons64x64_1', 'ArrowDown')
            .Ui::quad('1.1 -1.35 0.02', '1.8 1.75', 'bgcolor="EEEF"')
            .Ui::styled('0.65 -0.7 0.03', '2.6 2.6', 'Icons64x64_1', 'Close', 'action="'.Ids::action($action).'"')
            .'</frame>';
    }

    /** Dialog button: a quad 8 x 3.5 with a centred label. */
    public static function dialogButton(string $text, string $pos, string $action): string
    {
        [$x, $y] = array_map('floatval', explode(' ', $pos));

        return Ui::styled($x.' '.$y.' 0.02', '8 3.5', 'Bgs1InRace', 'BgButtonBig', 'action="'.Ids::action($action).'"')
            .Ui::label(($x + 4).' '.($y - 1).' 0.03', '', '$000$O'.$text, 'halign="center" textsize="1"');
    }

    /** A paging / tool button; a disabled one is two stacked gold stars with no action. */
    public static function tool(string $substyle, float $x, ?string $action): string
    {
        if ($action === null) {
            return Ui::styled("$x 0 0", '3.2 3.2', 'Icons64x64_1', 'StarGold').Ui::styled("$x 0 0.01", '3.2 3.2', 'Icons64x64_1', 'StarGold');
        }

        return Ui::styled("$x 0 0", '3.2 3.2', 'Icons64x64_1', $substyle, 'action="'.Ids::action($action).'"');
    }

    /** The six paging buttons for a page of pages (x offsets 6.6 …). */
    public static function paging(int $page, int $pages): string
    {
        $first = $page > 1;
        $last = $page < $pages;

        return self::tool('ArrowFirst', 6.6, $first ? 'page_first' : null)
            .self::tool('ArrowFastPrev', 9.9, $first ? 'page_prev5' : null)
            .self::tool('ArrowPrev', 13.2, $first ? 'page_prev' : null)
            .self::tool('ArrowNext', 16.5, $last ? 'page_next' : null)
            .self::tool('ArrowFastNext', 19.8, $last ? 'page_next5' : null)
            .self::tool('ArrowLast', 23.1, $last ? 'page_last' : null);
    }

    /** The page after a paging action; -5/+5 stop at the ends. */
    public static function turn(string $action, int $page, int $pages): int
    {
        return max(1, min($pages, match ($action) {
            'page_first' => 1,
            'page_prev5' => $page - 5,
            'page_prev'  => $page - 1,
            'page_next'  => $page + 1,
            'page_next5' => $page + 5,
            'page_last'  => $pages,
            default      => $page,
        }));
    }
}
