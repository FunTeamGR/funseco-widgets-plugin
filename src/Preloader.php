<?php
namespace Funseco\Widgets;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentPlayers;
use Funseco\Widgets\Support\Environments;
use Funseco\Widgets\Support\State;
use Funseco\Widgets\Support\Ui;

/**
 * Image preloading (spec 23): 15 s after a player joins, then every 5 s, they get one hidden
 * manialink (a frame far off screen) per batch, so the first window using the images shows them at once.
 * Batches: plus, minus, ok buttons; environments enabled, focus, disabled; moods enabled+focus,
 * moods disabled; progress video; no-screenshot image; eyepiece logo.
 */
class Preloader
{
    public const BATCHES = 11;

    public function start(Player $player): void
    {
        if ($this->enabled()) {
            State::put('preload.'.$player->login, ['at' => time() + $this->delay('first'), 'batch' => 0]);
        }
    }

    public function forget(string $login): void
    {
        State::forget('preload.'.$login);
    }

    /** Once per second: send the due batches. */
    public function tick(): void
    {
        if (!$this->enabled()) {
            return;
        }
        foreach (CurrentPlayers::all() as $player) {
            $job = State::get('preload.'.$player->login);
            if (!$job || $job['at'] > time()) {
                continue;
            }
            $this->send($player, $job['batch']);
            $next = $job['batch'] + 1;
            $next >= self::BATCHES
                ? State::forget('preload.'.$player->login)
                : State::put('preload.'.$player->login, ['at' => time() + $this->delay('every'), 'batch' => $next]);
        }
    }

    public function send(Player $player, int $batch): void
    {
        if ($xml = $this->xml($batch)) {
            Ui::sendTo($player, $xml);
        }
    }

    /** Image URLs of one batch (empty when the images are not configured). */
    public function images(int $batch): array
    {
        $env = fn (string $state) => array_map(fn ($k) => Ui::image('environment', $k, $state), array_keys(Environments::NAMES));
        $mood = fn (string $state) => array_map(fn ($k) => Ui::image('mood', $k, $state), array_keys(Environments::MOODS));
        $urls = match ($batch) {
            0 => [Ui::image('widget_plus_normal'), Ui::image('widget_plus_focus')],
            1 => [Ui::image('widget_minus_normal'), Ui::image('widget_minus_focus')],
            2 => [Ui::image('widget_ok_normal'), Ui::image('widget_ok_focus')],
            3 => $env('enabled'),
            4 => $env('focus'),
            5 => $env('disabled'),
            6 => [...$mood('normal'), ...$mood('focus')],
            7 => $mood('disabled'),
            8 => [Ui::image('progress_indicator')],
            9 => [Ui::image('no_screenshot')],
            10 => [config('widgets.branding.logo.normal'), config('widgets.branding.logo.focus')],
            default => [],
        };

        return array_values(array_filter($urls));
    }

    public function xml(int $batch): ?string
    {
        $urls = $this->images($batch);
        if ($urls === []) {
            return null;
        }
        $quads = '';
        foreach ($urls as $url) {
            $quads .= Ui::quad('0 0 0', '1 1', 'image="'.Ui::esc($url).'"');
        }

        return Ui::manialink('preload', '<frame posn="-120 -120 0">'.$quads.'</frame>');
    }

    private function enabled(): bool
    {
        return (bool) config('widgets.preload.enabled', true);
    }

    private function delay(string $which): int
    {
        return (int) config('widgets.preload.'.$which, $which === 'first' ? 15 : 5);
    }
}
