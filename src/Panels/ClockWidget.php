<?php
namespace Funseco\Widgets\Panels;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentPlayers;
use Funseco\Widgets\Models\PlayerStats;
use Funseco\Widgets\Support\Panel;
use Funseco\Widgets\Support\State;
use Funseco\Widgets\Support\StatusBar;
use Funseco\Widgets\Support\Ui;

/** Clock in the player's timezone plus the Swatch beat; opens the timezone window (spec 5). */
class ClockWidget extends Panel
{
    public function id(): string
    {
        return 'clock';
    }

    public function enabled(): bool
    {
        return Ui::enabled('clock_widget');
    }

    protected function perPlayer(): bool
    {
        return true;
    }

    /** Time format without seconds, microseconds and Swatch letters. */
    public static function format(): string
    {
        return preg_replace('/[suB]/', '', (string) config('manialinks.clock_widget.timeformat', 'H:i')) ?: 'H:i';
    }

    /** Swatch Internet Time "beat" (000-999, BMT = UTC+1). */
    public static function beat(?int $now = null): string
    {
        $now ??= time();

        return sprintf('%03d', intdiv((($now + 3600) % 86400) * 10, 864));
    }

    public function showAll(): void
    {
        // one render per timezone, not per player
        $byZone = [];
        foreach (CurrentPlayers::all() as $player) {
            $byZone[PlayerStats::of($player)->zone()][] = $player;
        }
        foreach ($byZone as $players) {
            $xml = $this->render($players[0]);
            foreach ($players as $player) {
                Ui::sendTo($player, $xml);
            }
        }
    }

    protected function xml(?Player $player): ?string
    {
        $zone = $player ? PlayerStats::of($player)->zone() : (string) config('manialinks.clock_widget.default_timezone', 'Europe/Berlin');
        try {
            $time = new \DateTimeImmutable('now', new \DateTimeZone($zone));
        } catch (\Throwable) {
            $time = new \DateTimeImmutable('now');
        }
        $abbr = $time->format('T');
        $abbr = $abbr === '' || preg_match('/^[+-]/', $abbr) ? 'UNKNOWN' : $abbr;
        $score = State::atScore();
        $sub = $score ? 'score' : 'race';

        $body = StatusBar::value($time->format(self::format()), 'textsize="1"', '2.3 -0.6 0.1')
            .StatusBar::caption('clock_widget', $abbr, '2.3 -2.1 0.1')
            .StatusBar::value(self::beat())
            .StatusBar::caption('clock_widget', 'BEAT');

        return StatusBar::for('clock_widget', $body, $sub, $score ? null : StatusBar::action('open_timezone'));
    }
}
