<?php
namespace Funseco\Widgets;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\Visits;
use Funseco\Widgets\Models\PlayerStats;

/** Counts and persists visits; the Visits contract for the scoreboard. */
class VisitCounter implements Visits
{
    /** One more visit; returns the new total. */
    public function add(Player $player): int
    {
        $stats = PlayerStats::of($player);
        $stats->increment('visits');

        return (int) $stats->visits;
    }

    public function top(int $limit): array
    {
        return PlayerStats::query()
            ->join('players', 'players.id', '=', 'widget_player_stats.player_id')
            ->where('widget_player_stats.visits', '>', 0)
            ->orderByDesc('widget_player_stats.visits')->orderBy('players.id')
            ->limit(max(0, $limit))
            ->pluck('widget_player_stats.visits', 'players.login')
            ->map(fn ($v) => (int) $v)->all();
    }
}
