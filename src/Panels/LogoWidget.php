<?php
namespace Funseco\Widgets\Panels;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\UI\WidgetRegistry;
use Funseco\Widgets\Support\Panel;
use Funseco\Widgets\Support\State;
use Funseco\Widgets\Support\StatusBar;
use Funseco\Widgets\Support\Ui;

/** The controller's logo: a small image at race, a status-bar button at score (spec 13.5). */
class LogoWidget extends Panel
{
    public function id(): string
    {
        return 'logo';
    }

    public function enabled(): bool
    {
        return Ui::bool(Ui::cfg('eyepiece_widget', State::atScore() ? 'score.enabled' : 'race.enabled', 'true'));
    }

    protected function xml(?Player $player): ?string
    {
        $url = (string) config('widgets.branding.url', '');
        $link = $url !== '' ? ' url="'.Ui::esc($url).'"' : '';
        $logo = config('widgets.branding.logo.normal');
        $focus = config('widgets.branding.logo.focus') ?: $logo;
        $name = (string) config('widgets.branding.name', 'Funseco');

        if (!State::atScore()) {
            $placement = app(WidgetRegistry::class)->placement('branding_logo');
            $x = $placement !== null ? $placement->x : Ui::cfg('eyepiece_widget', 'race.pos_x', -62);
            $y = $placement !== null ? $placement->y : Ui::cfg('eyepiece_widget', 'race.pos_y', 47);
            if (!$logo) {
                return '<frame posn="'.$x.' '.$y.' 0">'.Ui::label('0 0 0.01', '9 2', $name, 'textsize="1" scale="0.8" textcolor="FFF8"'.$link).'</frame>';
            }

            return '<frame posn="'.$x.' '.$y.' 0">'
                .Ui::quad('0 0 0.01', '2.11 2.2', 'image="'.Ui::esc($logo).'" imagefocus="'.Ui::esc($focus).'"'.$link).'</frame>';
        }

        [$one, $two] = array_pad((array) config('widgets.branding.caption', ['TM', 'FUNSECO']), 2, '');
        $body = $logo
            ? Ui::quad('0.365 -0.3 0.002', '3.87 4.03', 'image="'.Ui::esc($logo).'" imagefocus="'.Ui::esc($focus).'"')
            : Ui::label('2.3 -1.6 0.002', '4.4 2', $name, 'halign="center" textsize="1" scale="0.6" textcolor="FFFF"');

        return StatusBar::frame([
            'x' => Ui::cfg('eyepiece_widget', 'score.pos_x'), 'y' => Ui::cfg('eyepiece_widget', 'score.pos_y'),
            'style' => Ui::cfg('eyepiece_widget', 'score.background_style', 'BgsPlayerCard'),
            'substyle' => Ui::cfg('eyepiece_widget', 'score.background_substyle', 'BgRacePlayerName'),
            'url' => $url !== '' ? $url : null,
            'body' => $body.StatusBar::caption2('eyepiece_widget', $one, $two),
        ]);
    }
}
