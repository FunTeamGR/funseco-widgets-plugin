<?php
namespace Funseco\Widgets\Panels;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentPlayers;
use Funseco\Core\SharedKernel\RoundsPoints;
use Funseco\Core\SharedKernel\UI\WidgetRegistry;
use Funseco\Widgets\Models\PlayerStats;
use Funseco\Widgets\Support\Format;
use Funseco\Widgets\Support\Gamemodes;
use Funseco\Widgets\Support\Panel;
use Funseco\Widgets\Support\State;
use Funseco\Widgets\Support\Ui;

/**
 * Round score list for rounds, team, cup and laps (spec 18): who finished (or how far each is,
 * in laps), best first, with the points each position would win next to it.
 */
class RoundScoreWidget extends Panel
{
    public function id(): string
    {
        return 'round_score';
    }

    private function mode(): string
    {
        return WidgetRegistry::GAMEMODES[Gamemodes::mode()] ?? 'time_attack';
    }

    /** The widget's config for the current gamemode and phase, or null when it is off. */
    public function settings(): ?array
    {
        $mode = $this->mode();
        if (!Ui::bool(config("manialinks.round_score.gamemode.$mode.enabled", 'false'))) {
            return null;
        }
        $phase = State::warmup() ? 'warmup' : 'race';
        $s = (array) config("manialinks.round_score.gamemode.$mode.$phase", []);

        return isset($s['pos_x'], $s['pos_y']) ? $s : null;
    }

    public function enabled(): bool
    {
        return $this->settings() !== null && !State::atScore();
    }

    // ---- data ----

    /** @return array<string, array{nick: string, time: int, cp: int}> */
    private function rows(): array
    {
        return (array) State::get('rs.rows', []);
    }

    public function reset(): void
    {
        State::put('rs.rows', []);
    }

    public function clearAll(): void
    {
        $this->reset();
        State::put('rs.points', []);
    }

    public function finished(Player $player, int $time): void
    {
        $rows = $this->rows();
        if (!isset($rows[$player->login])) {
            $rows[$player->login] = ['nick' => (string) $player->nickname, 'time' => $time, 'cp' => 0];
            State::put('rs.rows', $rows);
        }
        State::put('rs.best.'.$player->login, min($time, (int) State::get('rs.best.'.$player->login, PHP_INT_MAX)));
    }

    public function checkpoint(Player $player, int $cp, int $time): void
    {
        $rows = $this->rows();
        $rows[$player->login] = ['nick' => (string) $player->nickname, 'time' => $time, 'cp' => $cp];
        State::put('rs.rows', $rows);
    }

    /** @return array<int, array{login: string, nick: string, time: int, cp: int}> ordered, uncapped */
    public function ordered(): array
    {
        $join = array_flip(CurrentPlayers::all()->keys()->all());
        $laps = Gamemodes::mode() === 3;
        $list = [];
        foreach ($this->rows() as $login => $row) {
            $list[] = ['login' => $login] + $row;
        }
        usort($list, function ($a, $b) use ($join, $laps) {
            $best = fn ($l) => (int) State::get('rs.best.'.$l, PHP_INT_MAX);

            return $laps
                ? [$b['cp'], $a['time'], $join[$a['login']] ?? 0] <=> [$a['cp'], $b['time'], $join[$b['login']] ?? 0]
                : [$a['time'], $best($a['login']), $join[$a['login']] ?? 0] <=> [$b['time'], $best($b['login']), $join[$b['login']] ?? 0];
        });

        return $list;
    }

    /** Points per position (rounds/cup): new rules 1,0; rounds feature; default list. */
    public function pointsList(): array
    {
        if (Ui::bool(Gamemodes::info()['RoundsUseNewRules'] ?? false)) {
            return [1, 0];
        }

        return app(RoundsPoints::class)->points() ?: (array) config('widgets.default_rounds_points', [10, 6, 4, 3, 2, 1]);
    }

    /** @return int[] points for each ordered row (rounds/cup/team) */
    public function pointsFor(array $ordered): array
    {
        $mode = Gamemodes::mode();
        if ($mode === 2) {
            return $this->teamPoints($ordered);
        }
        $list = $this->pointsList();
        $out = [];
        foreach (array_keys($ordered) as $i) {
            $out[$i] = $list[$i] ?? (end($list) ?: 0);
        }

        return $out;
    }

    private function teamPoints(array $ordered): array
    {
        $team = fn (array $r) => State::get('team.'.$r['login'], -1);
        $out = [];
        if (Ui::bool(Gamemodes::info()['TeamUseNewRules'] ?? false)) {
            $top = min(count(CurrentPlayers::all()), (int) (Gamemodes::info()['TeamMaxPoints'] ?? PHP_INT_MAX));
            foreach (array_keys($ordered) as $i) {
                $out[$i] = max(0, $top - $i);
            }

            return $out;
        }
        $first = isset($ordered[0]) ? $team($ordered[0]) : null;
        $stop = false;
        foreach ($ordered as $i => $row) {
            $stop = $stop || $team($row) !== $first;
            $out[$i] = $stop ? 0 : 1;
        }

        return $out;
    }

    /** Add this round's points to the running totals (rounds and cup); called when a round ends. */
    public function bankRound(): void
    {
        if (!in_array(Gamemodes::mode(), [0, 5], true)) {
            return;
        }
        $ordered = $this->ordered();
        $totals = (array) State::get('rs.points', []);
        foreach ($this->pointsFor($ordered) as $i => $points) {
            $totals[$ordered[$i]['login']] = ($totals[$ordered[$i]['login']] ?? 0) + $points;
        }
        State::put('rs.points', $totals);
    }

    /** Write the banked totals to the database (score). */
    public function flushPoints(): void
    {
        foreach ((array) State::get('rs.points', []) as $login => $points) {
            if ($points > 0 && ($player = CurrentPlayers::get($login))) {
                PlayerStats::of($player)->increment('round_points', $points);
            }
        }
        State::put('rs.points', []);
    }

    // ---- view ----

    protected function xml(?Player $player): ?string
    {
        $s = $this->settings();
        if (!$s) {
            return null;
        }
        $mode = Gamemodes::mode();
        $warmup = State::warmup();
        $entries = max(1, (int) ($s['entries'] ?? 10));
        $top = (int) ($s['topcount'] ?? 3);
        $x = (float) $s['pos_x'];
        $y = (float) $s['pos_y'];
        $w = (float) config('manialinks.round_score.width', 15.5);
        $h = round(1.8 * $entries + 3.3, 2);
        $bg = Ui::style('race');
        $icon = (array) config('manialinks.round_score.'.($warmup ? 'warmup' : 'race'), []);
        $ordered = array_slice($this->ordered(), 0, $entries);
        $points = $mode === 3 ? [] : $this->pointsFor($ordered);
        $left = $x >= 0;
        $iconX = $left ? 0.6 : 12.5 + ($w - 15.5);
        $titleX = $left ? 3.2 : 12.4 + ($w - 15.5);
        $c = Ui::codes('race');

        $xml = '<frame posn="'.$x.' '.$y.' 0"><format textsize="1" textcolor="'.Ui::colour('race', 'default').'"/>'
            .Ui::styled('0 0 0.001', "$w $h", $bg['background_style'], $bg['background_substyle'])
            .Ui::quad('0.4 -2.6 0.002', '2 '.($h - 3.1), 'bgcolor="'.Ui::colour('race', 'background_rank').'"')
            .Ui::quad('2.4 -2.6 0.002', '3.65 '.($h - 3.1), 'bgcolor="'.Ui::colour('race', 'background_score').'"')
            .Ui::quad('6.05 -2.6 0.002', ($w - 6.45).' '.($h - 3.1), 'bgcolor="'.Ui::colour('race', 'background_name').'"')
            .Ui::styled('0.4 -0.36 0.003', ($w - 0.8).' 2', $bg['title_style'], $bg['title_substyle'])
            .Ui::styled($iconX.' 0 0.004', '2.5 2.5', $icon['icon_style'] ?? 'Icons64x64_1', $icon['icon_substyle'] ?? 'RestartRace')
            .Ui::label($titleX.' -0.55 0.004', '10.2 0', (string) config('manialinks.round_score.title', 'Round Score'), 'textsize="1" halign="'.($left ? 'left' : 'right').'"');
        if ($top > 0) {
            $xml .= Ui::styled('0.4 -2.6 0.004', ($w - 0.8).' '.($top * 1.8 + 0.3), $bg['top_style'], $bg['top_substyle']);
        }

        if ($ordered === []) {
            $xml .= $warmup
                ? Ui::label('2.3 -3.2 0.01', '12 0', 'No Score during Warm-Up!', 'scale="0.9" textcolor="FA0F" autonewline="1"')
                : Ui::label('2.3 -3 0.01', '2 1.7', '--.', 'halign="right" scale="0.9"')
                    .Ui::label('5.9 -3 0.01', '3.8 1.7', '-:--.--', 'halign="right" scale="0.9" textcolor="'.Ui::colour('race', 'top').'"')
                    .Ui::label('6.1 -3 0.01', '', ' Free For You!', 'scale="0.9" textcolor="FA0F"');
        }

        $leader = $ordered[0] ?? null;
        foreach ($ordered as $n => $row) {
            $ry = -(1.8 * $n + 3);
            $colour = $n < $top ? Ui::colour('race', 'top') : Ui::colour('race', 'worse');
            $time = $mode === 3 && $row['cp'] === 0 ? '' : Format::time($row['time']);
            $xml .= Ui::label('2.3 '.$ry.' 0.01', '1.7 1.7', ($n + 1).'.', 'halign="right" scale="0.9"')
                .Ui::label('5.9 '.$ry.' 0.01', '3.8 1.7', $time, 'halign="right" scale="0.9" textcolor="'.$colour.'"')
                .Ui::label('6.1 '.$ry.' 0.01', round($w * 0.6258, 2).' 1.7', $c.Format::stripColors($row['nick']), 'scale="0.9"');
            $xml .= $this->badge($mode, $left, $w, $ry, $row, $leader, $points[$n] ?? 0);
        }

        return $xml.'</frame>';
    }

    /** The points / time-difference badge beside a row. */
    private function badge(int $mode, bool $left, float $w, float $ry, array $row, ?array $leader, int $points): string
    {
        $dx = $left ? 0 : $w + ($mode === 3 ? 7.1 : 4.1);
        $by = $ry + 0.3;
        if ($mode === 0 || $mode === 5) {
            return Ui::styled(($dx - 4.1).' '.$by.' 0.01', '4 2', 'Bgs1InRace', 'BgCard1')
                .Ui::label(($dx - 0.6).' '.($ry + 0.25).' 0.02', '3 2', '$O+'.$points, 'halign="right" scale="0.9" textcolor="0B3F"');
        }
        if ($mode === 2) {
            $blue = State::get('team.'.$row['login'], 0) == 0;

            return Ui::styled(($dx - 4.1).' '.$by.' 0.01', '4 2', 'Bgs1InRace', 'BgCard1')
                .Ui::quad(($dx - 3.7).' '.($ry + 0.14).' 0.015', '3.2 1.68', 'bgcolor="'.($blue ? '03DF' : 'D30F').'"')
                .Ui::label(($dx - 0.6).' '.($ry + 0.25).' 0.02', '3 2', '$O+'.$points, 'halign="right" scale="0.9" textcolor="FFFF"');
        }
        $behind = $leader && $row['cp'] < $leader['cp'];
        $colour = $behind ? 'D02F' : '0B3F';

        return Ui::styled(($dx - 7.1).' '.$by.' 0.01', '7 2', 'BgsPlayerCard', 'ProgressBar')
            .Ui::label(($dx - 2.35).' '.($ry + 0.25).' 0.02', '4.8 2', '$O+'.Format::time(max(0, $row['time'] - ($leader['time'] ?? $row['time']))), 'halign="right" scale="0.9" textcolor="'.$colour.'"')
            .Ui::label(($dx - 0.5).' '.($ry + 0.25).' 0.02', '1.3 2', '$O'.$row['cp'], 'halign="right" scale="0.9" textcolor="'.$colour.'"');
    }
}
