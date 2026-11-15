<?php
namespace Funseco\Widgets\Support;

use Funseco\Core\Services\Cache\CurrentPlayers;
use Funseco\Core\Services\Cache\CurrentSpectators;

/** In-memory widget state (state() store, reset on every restart). */
final class State
{
    private const P = 'widgets.';

    public static function get(string $key, mixed $default = null): mixed
    {
        return state()->get(self::P.$key, $default);
    }

    public static function put(string $key, mixed $value): void
    {
        state()->forever(self::P.$key, $value);
    }

    public static function forget(string $key): void
    {
        state()->forget(self::P.$key);
    }

    /** The race is over: score widgets are showing. */
    public static function atScore(): bool
    {
        return (bool) self::get('score', false);
    }

    public static function setScore(bool $score): void
    {
        self::put('score', $score);
    }

    public static function warmup(): bool
    {
        return (bool) self::get('warmup', false);
    }

    // ---- nice mode (spec 1.7) ----

    public static function nice(): bool
    {
        return (bool) self::get('nice', false);
    }

    public static function setNice(bool $nice): void
    {
        self::put('nice', $nice);
    }

    /** Upper/lower limit logic: on at >= upper, off at <= lower. Returns true when it changed. */
    public static function updateNice(): bool
    {
        $before = self::nice();
        $enabled = Ui::bool(config('manialinks.nicemode.enabled', 'true'));
        $force = Ui::bool(self::get('force_nice', config('manialinks.nicemode.force', 'false')));
        $count = CurrentPlayers::all()->count();
        $upper = (int) self::get('nice_upper', config('manialinks.nicemode.limits.upper_limit', 25));
        $lower = (int) self::get('nice_lower', config('manialinks.nicemode.limits.lower_limit', 24));

        $now = match (true) {
            !$enabled => false,
            $force => true,
            $count >= $upper => true,
            $count <= $lower => false,
            default => $before,
        };
        self::setNice($now);

        return $now !== $before;
    }

    // ---- record widgets hidden per player (F7 / preference) ----

    public static function hidden(string $login): bool
    {
        $all = (array) self::get('hidden', []);

        return isset($all[$login]);
    }

    public static function setHidden(string $login, bool $hidden): void
    {
        $all = (array) self::get('hidden', []);
        $hidden ? $all[$login] = true : $all = array_diff_key($all, [$login => 1]);
        self::put('hidden', $all);
    }

    // ---- counts ----

    public static function playerCount(): int
    {
        return CurrentPlayers::all()->count() - CurrentSpectators::all()->filter(fn ($p) => CurrentPlayers::has($p->login))->count();
    }

    public static function spectatorCount(): int
    {
        return CurrentSpectators::all()->filter(fn ($p) => CurrentPlayers::has($p->login))->count();
    }
}
