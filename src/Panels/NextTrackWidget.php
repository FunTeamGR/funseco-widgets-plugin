<?php
namespace Funseco\Widgets\Panels;

use Funseco\Core\Models\Player;
use Funseco\Widgets\Support\Gamemodes;
use Funseco\Widgets\Support\Panel;
use Funseco\Widgets\Support\State;
use Funseco\Widgets\Support\StatusBar;
use Funseco\Widgets\Support\TrackCard;
use Funseco\Widgets\Support\Ui;

/** "Next Track" at score (spec 4.2); same manialink as the race version. */
class NextTrackWidget extends Panel
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
        $track = TrackCard::fromArray(State::get('next'));
        $x = Ui::cfg('challenge_widget', 'score.pos_x', 45.7);
        $y = Ui::cfg('challenge_widget', 'score.pos_y', 47.5);
        $bg = Ui::style('score');
        $c = Ui::codes('score');
        $stunts = Gamemodes::stunts();
        $t = fn (int $ms) => $c.$track->time($ms, $stunts);
        $label = fn (string $pos, string $size, string $text, string $attrs = 'scale="0.9"') => Ui::label($pos, $size, $c.$text, $attrs);

        return '<frame posn="'.$x.' '.$y.' 0"><format textsize="1" textcolor="'.Ui::colour('score', 'default').'"/>'
            .Ui::styled('0 0 0.001', '17.6 14.1', $bg['background_style'], $bg['background_substyle'])
            .Ui::styled('0.4 -0.36 0.002', '16.8 2', $bg['title_style'], $bg['title_substyle'])
            .StatusBar::icon('Icons128x128_1', 'LoadTrack', '0.6 -0.15 0.004', '2.5 2.5')
            .$label('3.2 -0.55 0.004', '10.2 0', (string) Ui::cfg('challenge_widget', 'title.next_track', 'Next Track'), 'textsize="1"')
            .$label('1.35 -3 0.11', '15 2', $track->name, 'scale="1"')
            .'<frame posn="0.5 -10 0">'
            .$label('0.85 5 0.11', '14.5 2', 'by '.$track->author)
            .StatusBar::icon('Icons128x128_1', 'Advanced', '2.95 3.38 0.11', '2.5 2.5', 'halign="right"')
            .$label('3.3 2.9 0.11', '12 2', $track->env)
            .StatusBar::icon('Icons128x128_1', 'Manialink', '11.1 3.53 0.11', '2.6 2.6', 'halign="right"')
            .$label('11.3 2.9 0.11', '', $track->mood)
            .'</frame><frame posn="0.5 -14.3 0">'
            .StatusBar::icon('BgRaceScore2', 'ScoreReplay', '2.75 5.25 0.11', '2 2', 'halign="right"')
            .$label('3.3 5 0.11', '6 2', $t($track->authorTime).'')
            .StatusBar::icon('MedalsBig', 'MedalGold', '2.75 3.1 0.11', '1.9 1.9', 'halign="right"')
            .$label('3.3 2.9 0.11', '6 2', $t($track->gold))
            .StatusBar::icon('MedalsBig', 'MedalSilver', '10.75 5.1 0.11', '1.9 1.9', 'halign="right"')
            .$label('11.3 5 0.11', '6 2', $t($track->silver))
            .StatusBar::icon('MedalsBig', 'MedalBronze', '10.75 3.1 0.11', '1.9 1.9', 'halign="right"')
            .$label('11.3 2.9 0.11', '6 2', $t($track->bronze))
            .'</frame></frame>';
    }
}
