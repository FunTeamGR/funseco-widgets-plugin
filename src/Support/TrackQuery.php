<?php
namespace Funseco\Widgets\Support;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\JukeboxQueue;

/** Filters and sorts of the track list (spec 8.3). */
final class TrackQuery
{
    public const ENV_NAMES = ['stadium' => 'Stadium', 'bay' => 'Bay', 'coast' => 'Coast', 'desert' => 'Desert', 'island' => 'Island', 'rally' => 'Rally', 'alpine' => 'Alpine'];

    /** Options an /elist keyword can select (besides env/mood buttons and searches). */
    public const KEYWORDS = ['jukebox', 'author', 'norecent', 'onlyrecent', 'norank', 'onlyrank', 'nomulti', 'onlymulti', 'noauthor', 'nogold',
        'nosilver', 'nobronze', 'nofinish', 'best', 'worst', 'shortest', 'longest', 'newest', 'oldest', 'track', 'sortauthor', 'bestkarma', 'worstkarma'];

    private const TITLES = [
        'norank' => '(Filter: Not Ranked Tracks)', 'onlyrank' => '(Filter: Only Ranked Tracks)', 'noauthor' => '(Filter: No Author Time)',
        'nogold' => '(Filter: No Gold Time)', 'nosilver' => '(Filter: No Silver Time)', 'nobronze' => '(Filter: No Bronze Time)',
        'norecent' => '(Filter: No Recent)', 'onlyrecent' => '(Filter: Only Recent)', 'jukebox' => '(Filter: Only Jukebox)',
        'onlymulti' => '(Filter: Only Multilap)', 'nomulti' => '(Filter: No Multilap)', 'nofinish' => '(Filter: Not Finished Tracks)',
        'best' => '(Sorting: Best Player Rank)', 'worst' => '(Sorting: Worst Player Rank)', 'shortest' => '(Sorting: Shortest Author Time)',
        'longest' => '(Sorting: Longest Author Time)', 'newest' => '(Sorting: Newest Tracks First)', 'oldest' => '(Sorting: Oldest Tracks First)',
        'track' => '(Sorting: By Track Name)', 'sortauthor' => '(Sorting: By Author Name)', 'bestkarma' => '(Sorting: Karma Best Tracks)',
        'worstkarma' => '(Sorting: Karma Worst Tracks)',
    ];

    /** The option for an /elist argument: a keyword, or a search text. @return array{0: string, 1: ?string} */
    public static function parse(string $arg): array
    {
        $lower = strtolower($arg);

        return in_array($lower, self::KEYWORDS, true) ? [$lower, null] : ['search', $arg];
    }

    /**
     * @param  array<int, array> $rows from TrackIndex::load
     * @return array{rows: array, title: string, empty: bool}
     */
    public static function apply(Player $player, array $rows, string $option, ?string $param = null): array
    {
        $plain = fn (array $t) => strtolower(Format::stripColors($t['name']));
        $stunts = Gamemodes::stunts();
        $me = TrackIndex::personal($player);
        $rank = fn (array $t) => ($r = $me['ranks'][$t['id']] ?? null) !== null && $r >= 1 && $r <= (int) config('widgets.max_records', 50) ? $r : null;
        $not = fn (array $t, string $medal) => ($b = $me['best'][$t['id']] ?? null) !== null && $t[$medal] > 0
            && ($stunts ? $b <= $t[$medal] : $b > $t[$medal]);
        $jukebox = app(JukeboxQueue::class);
        $recent = array_flip($jukebox->history());
        $title = '';
        $sort = fn (array $list, callable $key, bool $desc = false) => (function () use ($list, $key, $desc) {
            usort($list, fn ($a, $b) => $desc ? $key($b) <=> $key($a) : $key($a) <=> $key($b));

            return $list;
        })();

        $list = self::defaultOrder($rows);
        switch (true) {
            case $option === 'all':
                break;
            case str_starts_with($option, 'env_'):
                $env = substr($option, 4);
                $list = array_values(array_filter($list, fn ($t) => (Environments::key($t['env']) ?? '') === ($env === 'alpine' ? 'snow' : $env)));
                $title = '(Filter: Only env. '.(self::ENV_NAMES[$env] ?? ucfirst($env)).')';
                break;
            case str_starts_with($option, 'mood_'):
                $mood = substr($option, 5);
                $list = array_values(array_filter($list, fn ($t) => strtolower($t['mood']) === $mood));
                $title = '(Filter: Only mood '.ucfirst($mood).')';
                break;
            case $option === 'author':
                $list = array_values(array_filter($list, fn ($t) => $t['author'] === $param));
                $title = '(Filter: Only Tracks by '.$param.')';
                break;
            case $option === 'norank':
                $list = array_values(array_filter($list, fn ($t) => $rank($t) === null));
                $title = self::TITLES[$option];
                break;
            case $option === 'onlyrank':
                $list = array_values(array_filter($list, fn ($t) => $rank($t) !== null));
                $title = self::TITLES[$option];
                break;
            case in_array($option, ['noauthor', 'nogold', 'nosilver', 'nobronze'], true):
                $medal = ['noauthor' => 'author_time', 'nogold' => 'gold', 'nosilver' => 'silver', 'nobronze' => 'bronze'][$option];
                $list = array_values(array_filter($list, fn ($t) => $not($t, $medal)));
                $title = self::TITLES[$option];
                break;
            case $option === 'norecent':
                $list = array_values(array_filter($list, fn ($t) => !isset($recent[$t['uid']])));
                $title = self::TITLES[$option];
                break;
            case $option === 'onlyrecent':
                $byUid = array_column($rows, null, 'uid');
                $list = array_values(array_filter(array_map(fn ($uid) => $byUid[$uid] ?? null, $jukebox->history())));
                $title = self::TITLES[$option];
                break;
            case $option === 'jukebox':
                $byUid = array_column($rows, null, 'uid');
                $list = array_values(array_filter(array_map(fn ($e) => $byUid[$e['uid']] ?? null, $jukebox->upcoming())));
                $title = self::TITLES[$option];
                break;
            case $option === 'onlymulti' || $option === 'nomulti':
                $want = $option === 'onlymulti';
                $list = array_values(array_filter($list, fn ($t) => $t['multilap'] === $want));
                $title = self::TITLES[$option];
                break;
            case $option === 'nofinish':
                $list = array_values(array_filter($list, fn ($t) => !isset($me['finished'][$t['id']])));
                $title = self::TITLES[$option];
                break;
            case $option === 'best' || $option === 'worst':
                $list = $sort(array_values(array_filter($list, fn ($t) => $rank($t) !== null)), $rank, $option === 'worst');
                $title = self::TITLES[$option];
                break;
            case $option === 'shortest' || $option === 'longest':
                $list = $sort($list, fn ($t) => $t['author_time'], $option === 'longest');
                $title = self::TITLES[$option];
                break;
            case $option === 'newest' || $option === 'oldest':
                // tracks not in the database yet count as newer than all others
                $list = $sort($list, fn ($t) => $t['id'] ?? PHP_INT_MAX, $option === 'newest');
                $title = self::TITLES[$option];
                break;
            case $option === 'track':
                $list = $sort($list, $plain);
                $title = self::TITLES[$option];
                break;
            case $option === 'sortauthor':
                $list = $sort($list, fn ($t) => strtolower($t['author']));
                $title = self::TITLES[$option];
                break;
            case $option === 'bestkarma' || $option === 'worstkarma':
                $karma = TrackIndex::karma();
                $list = $sort($list, fn ($t) => $karma[$t['id']] ?? 0, $option === 'bestkarma');
                $title = self::TITLES[$option];
                break;
            default:   // search
                $needle = strtolower((string) $param);
                $list = array_values(array_filter($list, fn ($t) => str_contains(strtolower($t['author']), $needle)
                    || str_contains($plain($t), $needle) || str_contains(strtolower($t['file']), $needle)));
                $title = "(Search: '".$param."')";
        }

        return ['rows' => array_slice($list, 0, TrackIndex::LIMIT), 'title' => $title, 'empty' => $list === []];
    }

    /** features.tracklist.sorting: author (then name), track, or the server's order. */
    public static function defaultOrder(array $rows): array
    {
        $plain = fn (array $t) => strtolower(Format::stripColors($t['name']));
        $mode = strtolower((string) config('manialinks.features.tracklist.sorting', 'author'));
        if ($mode === 'author') {
            usort($rows, fn ($a, $b) => [strtolower($a['author']), $plain($a)] <=> [strtolower($b['author']), $plain($b)]);
        } elseif ($mode === 'track') {
            usort($rows, fn ($a, $b) => $plain($a) <=> $plain($b));
        }

        return $rows;
    }
}
