<?php
namespace Funseco\Widgets\Panels;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\UI\Theme\ThemeKit;
use Funseco\Core\Services\Cache\CurrentMap;
use Funseco\Core\SharedKernel\TrackExchange;
use Funseco\Widgets\Support\Gamemodes;
use Funseco\Widgets\Support\Panel;
use Funseco\Widgets\Support\ReplayLinks;
use Funseco\Widgets\Support\StatusBar;
use Funseco\Widgets\Support\TrackCard;
use Funseco\Widgets\Support\Ui;

/** "Current Track" (spec 4.1); opens the track overview. */
class ChallengeWidget extends Panel
{
    public function id(): string
    {
        return 'challenge';
    }

    public function enabled(): bool
    {
        return Ui::enabled('challenge_widget');
    }

    protected function xml(?Player $player): ?string
    {
        $map = CurrentMap::get();
        if (!$map) {
            return null;
        }
        $track = TrackCard::fromMap($map);
        $x = (float) Ui::cfg('challenge_widget', 'race.pos_x', 49.05);
        $y = Ui::cfg('challenge_widget', 'race.pos_y', 48);
        $w = (float) Ui::cfg('challenge_widget', 'width', 15.5);
        $bg = Ui::style('race');
        $stunts = Gamemodes::stunts();
        $set = $x >= 0 ? 'left' : 'right';
        $extra = $w - 15.5;
        [$iconX, $titleX, $openX, $align, $openKey] = $set === 'left'
            ? [0.6, 3.2, -0.3, 'left', 'widget_open_left']
            : [12.5 + $extra, 12.4 + $extra, 12.2 + $extra, 'right', 'widget_open_right'];
        $open = Ui::image($openKey);
        $author = TrackCard::fromMap($map)->authorTime;

        return '<frame posn="'.$x.' '.$y.' 0"><format textsize="1" textcolor="'.Ui::colour('race', 'default').'"/>'
            .ThemeKit::current()->surface('panel', (float) $w, 8.65, $bg['background_style'], $bg['background_substyle'], 'action="'.StatusBar::action('open_overview').'"', 0, 0, 0.01)
            .($open ? Ui::quad($openX.' -5.35 0.03', '3.5 3.5', 'image="'.Ui::esc($open).'"') : '')
            .ThemeKit::current()->titleBar($w - 0.8, 0.02, $bg['title_style'], $bg['title_substyle'])
            .StatusBar::icon('Icons128x128_1', 'Challenge', $iconX.' 0 0.04', '2.5 2.5')
            .Ui::label($titleX.' -0.55 0.04', '10.2 0', (string) Ui::cfg('challenge_widget', 'title.current_track', 'Current Track'), 'textsize="1" halign="'.$align.'"')
            .Ui::label('1 -2.7 0.04', '13.55 2', $track->name, 'scale="1"')
            .Ui::label('1 -4.5 0.04', '14.85 2', 'by '.$track->author, 'scale="0.9"')
            .StatusBar::icon('BgRaceScore2', 'ScoreReplay', '0.7 -6.25 0.04', '1.7 1.7')
            .Ui::label('2.7 -6.55 0.04', '6 2', $track->time($author, $stunts), 'scale="0.75"')
            .ReplayLinks::icons(app(TrackExchange::class)->current(), $w - 0.6, -6.25)
            .'</frame>';
    }
}
