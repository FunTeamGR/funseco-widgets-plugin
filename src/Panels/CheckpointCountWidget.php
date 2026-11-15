<?php
namespace Funseco\Widgets\Panels;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentMap;
use Funseco\Widgets\Support\Gamemodes;
use Funseco\Widgets\Support\Panel;
use Funseco\Widgets\Support\State;
use Funseco\Widgets\Support\Ui;

/** "Checkpoint 3 of 7" for the driving player (spec 15). Never sent in nice mode. */
class CheckpointCountWidget extends Panel
{
    public function id(): string
    {
        return 'cpcount';
    }

    public function enabled(): bool
    {
        return Ui::enabled('checkpointcount_widget') && !State::nice() && !State::atScore() && CurrentMap::get()?->cp_count > 0;
    }

    protected function perPlayer(): bool
    {
        return true;
    }

    /** Checkpoints of the whole race including the finish. */
    public static function total(): int
    {
        $map = CurrentMap::get();
        $cps = (int) ($map?->cp_count ?? 0);
        $info = Gamemodes::info();

        return match (Gamemodes::mode()) {
            0, 2, 5 => (int) ($info['RoundsForcedLaps'] ?? 0) > 0
                ? $cps * (int) $info['RoundsForcedLaps']
                : ($map?->lap_race ? $cps * max(1, (int) $map->lap_count) : $cps),
            3 => $cps * max(1, (int) ($info['LapsNbLaps'] ?? 1)),
            default => $cps,
        };
    }

    /** Both label lines for "c" = cumulative index just passed (-1 = start). */
    public static function lines(int $c, bool $warmup): array
    {
        $last = self::total() - 1;
        if ($c + 1 < $last) {
            return ['CHECKPOINT', $warmup ? '$OWarm-Up' : '$O'.($c + 1).' $Zof$O '.$last, 'textsize="2" scale="0.9"'];
        }
        if ($c + 1 === $last) {
            return [$last === 0 ? 'WITHOUT CHECKPOINTS' : 'ALL CHECKPOINTS REACHED', 'Finish now!', 'style="TextTitle2Blink" textsize="1" scale="0.75"'];
        }

        return ['TRACK SUCCESSFULLY', '$O Finished ', 'textsize="2" scale="0.9"'];
    }

    public function showCheckpoint(Player $player, int $c): void
    {
        State::put('cp.'.$player->login, $c);
        $this->show($player);
    }

    /** Everybody at "0 of N". */
    public function resetAll(): void
    {
        foreach (\Funseco\Core\Services\Cache\CurrentPlayers::all() as $player) {
            if (!\Funseco\Core\Services\Cache\CurrentSpectators::has($player->login)) {
                $this->showCheckpoint($player, -1);
            }
        }
    }

    protected function xml(?Player $player): ?string
    {
        if (!$player) {
            return null;
        }
        [$one, $two, $attrs] = self::lines((int) State::get('cp.'.$player->login, -1), State::warmup());
        $bg = Ui::cfg('checkpointcount_widget', 'background_style', 'Bgs1InRace');
        $sub = Ui::cfg('checkpointcount_widget', 'background_substyle', 'NavButton');
        $colour = Ui::cfg('checkpointcount_widget', 'text_color', 'DDEF');

        return '<frame posn="'.Ui::cfg('checkpointcount_widget', 'pos_x').' '.Ui::cfg('checkpointcount_widget', 'pos_y').' 0"><format textsize="1"/>'
            .Ui::styled('0 0 0.001', '16 4', $bg, $sub)
            .Ui::label('8 -0.65 0.01', '', $one, 'halign="center" textsize="1" scale="0.6" textcolor="FC0F"')
            .Ui::label('8 -1.8 0.01', '', $two, 'halign="center" textcolor="'.$colour.'" '.$attrs)
            .'</frame>';
    }
}
