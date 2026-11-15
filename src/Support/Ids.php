<?php
namespace Funseco\Widgets\Support;

use Funseco\Core\Services\ManiaLink\Facades\ManiaLink;

/** Registered manialink ids and click actions of this plugin (never hardcoded numbers). */
final class Ids
{
    public const MANIALINKS = [
        'challenge', 'next_track', 'clock', 'gamemode', 'next_env', 'next_mode', 'trackcount', 'visitors',
        'ladder', 'players', 'ranking', 'cpcount', 'tmx', 'favorite', 'logo', 'donation', 'round_score',
        'toggle_key', 'custom_ui', 'window', 'subwindow', 'preload',
    ];

    public const MAX_PLACEMENTS = 25;
    public const CARDS = 20;
    public const PICKS = 80;
    public const DONATIONS = 10;

    /** Track list filter/sort options (also the /elist keywords, except the env/mood ones). */
    public const OPTIONS = [
        'all', 'jukebox', 'author', 'norecent', 'onlyrecent', 'norank', 'onlyrank', 'nomulti', 'onlymulti',
        'noauthor', 'nogold', 'nosilver', 'nobronze', 'nofinish', 'best', 'worst', 'shortest', 'longest',
        'newest', 'oldest', 'track', 'sortauthor', 'bestkarma', 'worstkarma',
        'env_stadium', 'env_bay', 'env_coast', 'env_desert', 'env_island', 'env_rally', 'env_alpine',
        'mood_sunrise', 'mood_day', 'mood_sunset', 'mood_night',
    ];

    /** @return string[] click action names (handled by Actions\ClickHandler) */
    public static function actions(): array
    {
        $actions = [
            'toggle', 'open_overview', 'open_timezone', 'open_tracklist', 'open_nations', 'open_tmx', 'open_help', 'open_ranking',
            'window_close', 'sub_close', 'tl_filter', 'tl_sort', 'tl_clear', 'tl_clear_yes', 'tz_back',
            'page_first', 'page_prev5', 'page_prev', 'page_next', 'page_next5', 'page_last',
        ];
        foreach (self::OPTIONS as $option) {
            $actions[] = 'opt_'.$option;
        }
        foreach (range(0, self::CARDS - 1) as $i) {
            $actions[] = 'card_'.$i;
        }
        foreach (range(0, self::PICKS - 1) as $i) {
            $actions[] = 'pick_'.$i;
        }
        foreach (range(0, 19) as $i) {
            $actions[] = 'tzgroup_'.$i;
        }
        foreach (range(0, self::MAX_PLACEMENTS - 1) as $i) {
            $actions[] = 'chat_'.$i;
        }
        foreach (range(0, self::DONATIONS - 1) as $i) {
            $actions[] = 'donate_'.$i;
        }

        return array_map(fn ($a) => 'widgets_'.$a, $actions);
    }

    /** @return string[] manialink id names */
    public static function manialinks(): array
    {
        $names = self::MANIALINKS;
        foreach (range(0, self::MAX_PLACEMENTS - 1) as $i) {
            $names[] = 'placement_'.$i;
        }

        return array_map(fn ($n) => 'widgets_ml_'.$n, $names);
    }

    public static function ml(string $name): int
    {
        return (int) ManiaLink::getAction('widgets_ml_'.$name);
    }

    public static function action(string $name): int
    {
        return (int) ManiaLink::getAction('widgets_'.$name);
    }
}
