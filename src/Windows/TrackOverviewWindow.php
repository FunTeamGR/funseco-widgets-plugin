<?php
namespace Funseco\Widgets\Windows;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\TrackExchange;
use Funseco\Core\SharedKernel\Values\TmxTrack;
use Funseco\Widgets\Support\Gamemodes;
use Funseco\Widgets\Support\NextTrack;
use Funseco\Widgets\Support\ReplayLinks;
use Funseco\Widgets\Support\State;
use Funseco\Widgets\Support\TrackCard;
use Funseco\Widgets\Support\Ui;
use function React\Promise\all;
use function React\Promise\resolve;

/** Last, current and next track side by side (spec 4.3). Opened by the challenge widget. */
class TrackOverviewWindow
{
    /** Info rows shared with the TMX info window: [label, TmxTrack field] */
    public const TMX_ROWS = [['Type:', 'type'], ['Style:', 'style'], ['Difficult:', 'difficulty'], ['Routes:', 'routes'], ['Awards:', 'awards'], ['Section:', 'section']];

    public function open(Player $player): void
    {
        if (WindowManager::refuseAtScore($player)) {
            return;
        }
        NextTrack::load(function (TrackCard $next) use ($player) {
            $last = TrackCard::fromArray(State::get('last'));
            $current = TrackCard::fromArray(State::get('current_card'));
            $exchange = app(TrackExchange::class);
            all([
                resolve($exchange->current()),
                $last->uid !== '' ? $exchange->forUid($last->uid) : resolve(null),
                $next->uid !== '' ? $exchange->forUid($next->uid) : resolve(null),
            ])->then(function (array $tmx) use ($player, $last, $current, $next) {
                $content = $this->panel(0, 'Last Track', ['Icons128x128_1', 'NewTrack'], $last, $tmx[1])
                    .$this->panel(25.45, 'Current Track', ['Icons128x128_1', 'Challenge'], $current, $tmx[0])
                    .$this->panel(50.85, 'Next Track', ['Icons128x128_1', 'LoadTrack'], $next, $tmx[2]);
                WindowManager::put($player, ['type' => 'overview', 'page' => 1, 'pages' => 1]);
                Ui::sendTo($player, Frame::wrapMain(Frame::main('Track overview', ['Icons128x128_1', 'Browse'], $content)).Ui::clear('subwindow'));
            });
        });
    }

    /** Times / environment / mood block shared with the TMX info window (frame content, origin at its posn). */
    public static function timesBlock(TrackCard $t): string
    {
        $stunts = Gamemodes::stunts();
        $rows = [
            [7.2, 6.9, 'BgRaceScore2', 'ScoreReplay', 2.2, $t->time($t->authorTime, $stunts)],
            [4.8, 4.6, 'MedalsBig', 'MedalGold', 2, $t->time($t->gold, $stunts)],
            [2.5, 2.3, 'MedalsBig', 'MedalSilver', 2, $t->time($t->silver, $stunts)],
            [0.2, 0, 'MedalsBig', 'MedalBronze', 2, $t->time($t->bronze, $stunts)],
            [-1.8, -2.3, 'Icons128x128_1', 'Advanced', 2.6, $t->env],
            [-4.1, -4.6, 'Icons128x128_1', 'Manialink', 2.6, $t->mood],
        ];
        $xml = '';
        foreach ($rows as [$iy, $vy, $style, $sub, $size, $text]) {
            $xml .= Ui::styled("0 $iy 0.04", "$size $size", $style, $sub, 'halign="right"')
                .Ui::label("0.5 $vy 0.04", '8 2', $text, 'scale="0.9"');
        }

        return $xml;
    }

    private function panel(float $x, string $title, array $icon, TrackCard $t, ?TmxTrack $tmx): string
    {
        $shot = $tmx?->imageUrl ?: Ui::image('no_screenshot');
        $xml = '<frame posn="'.$x.' 0 0"><format textsize="1" textcolor="'.Ui::colour('race', 'default').'"/>'
            .Ui::styled('0 0 0.02', '24.05 47', 'BgsPlayerCard', 'BgRacePlayerName')
            .Ui::styled('0.4 -0.36 0.03', '23.25 2', 'BgsPlayerCard', 'ProgressBar')
            .Ui::styled('0.6 0 0.04', '2.5 2.5', $icon[0], $icon[1])
            .Ui::label('3.2 -0.55 0.04', '23.6 0', $title, 'textsize="1"')
            .Ui::quad('1.4 -3.6 0.03', '21.45 16.29', 'bgcolor="FFF9"')
            .Ui::label('12.1 -11 0.04', '', 'Press DEL if can not see an Image here!', 'halign="center" scale="0.6"')
            .($shot ? Ui::quad('1.5 -3.7 0.50', '21.25 16.09', 'image="'.Ui::esc($shot).'"') : '')
            .Ui::label('1.4 -21 0.04', '21 3', '$S'.$t->name, 'textsize="2"')
            .Ui::label('1.4 -23.3 0.04', '', 'by '.$t->author, 'textsize="1"')
            .ReplayLinks::icons($tmx, 23.6, -21.2, 2.2)
            .'<frame posn="3.2 -33 0">'.self::timesBlock($t).'</frame>';
        if ($tmx) {
            $xml .= '<frame posn="10.6 -33 0">'.self::tmxInfo($tmx, '10.5 2').'</frame>'
                .'<frame posn="1.6 -40.5 0"><format textsize="1" style="TextCardScores2"/>'
                .Ui::label('0 -0.3 0.04', '24 2', '$FFF» Visit Track Page', 'scale="0.5" url="'.Ui::esc(ReplayLinks::clientUrl((string) $tmx->pageUrl)).'"')
                .Ui::label('0 -2.2 0.04', '24 2', '$FFF» Download Track', 'scale="0.5" url="'.Ui::esc(ReplayLinks::clientUrl((string) $tmx->downloadUrl)).'"')
                .($tmx->replayUrl ? Ui::label('0 -4.1 0.04', '24 2', '$FFF» Download Replay', 'scale="0.5" url="'.Ui::esc(ReplayLinks::clientUrl((string) $tmx->replayUrl)).'"') : '')
                .'</frame>';
        }

        return $xml.'</frame>';
    }

    /** Type / Style / Difficult / Routes / Awards / Section rows (labels x 0, values x 5.1). */
    public static function tmxInfo(TmxTrack $tmx, string $valueSize): string
    {
        $xml = '';
        foreach (self::TMX_ROWS as $i => [$label, $field]) {
            $y = 6.9 - 2.3 * $i;
            $xml .= Ui::label("0 $y 0.04", '5 2', $label)
                .Ui::label("5.1 $y 0.04", $valueSize, ' '.$tmx->$field);
        }

        return $xml;
    }
}
