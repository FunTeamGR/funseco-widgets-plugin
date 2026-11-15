<?php
namespace Funseco\Widgets\Panels;

use Funseco\Core\Models\Player;
use Funseco\Widgets\Support\Gamemodes;
use Funseco\Widgets\Support\Panel;
use Funseco\Widgets\Support\State;
use Funseco\Widgets\Support\StatusBar;
use Funseco\Widgets\Support\Ui;

/**
 * The player's current rank on the server (spec 14). Fed by the periodic GetCurrentRanking
 * (State 'ranking'); hidden for players who hid the record widgets.
 */
class CurrentRankingWidget extends Panel
{
    public function id(): string
    {
        return 'ranking';
    }

    public function enabled(): bool
    {
        return Ui::enabled('current_ranking_widget');
    }

    protected function perPlayer(): bool
    {
        return true;
    }

    /** Re-read the server ranking and send everybody their line. */
    public function refresh(): void
    {
        \Server::query()->GetCurrentRanking(300, 0, function ($result) {
            $rows = is_array($result[0] ?? null) ? $result[0] : [];
            State::put('ranking', array_values(array_filter($rows, 'is_array')));
            State::atScore() || $this->showAll();   // a late answer must not draw over the score screen
        });
    }

    /** Everybody at "0/n" (map start). */
    public function reset(): void
    {
        State::put('ranking', []);
        $this->showAll();
    }

    /** @return array{0: string, 1: string} value, caption */
    public function lineFor(Player $player): ?array
    {
        $rows = (array) State::get('ranking', []);
        if (Gamemodes::mode() === 2) {
            [$blue, $red] = $this->teamScores($rows);
            if ($blue === 0 && $red === 0) {
                return ['---', 'FIRST'];
            }

            return $blue === $red ? ['TEAM', 'DRAW'] : [$blue > $red ? 'BLUE' : 'RED', 'FIRST'];
        }
        if ($rows === []) {
            return ['0/'.\Funseco\Core\Services\Cache\CurrentPlayers::all()->count(), 'RANKING'];
        }
        foreach ($rows as $row) {
            if (($row['Login'] ?? null) === $player->login) {
                $has = (int) ($row['BestTime'] ?? 0) > 0 || (int) ($row['Score'] ?? 0) > 0;

                return [($has ? (int) ($row['Rank'] ?? 0) : 0).'/'.count($rows), 'RANKING'];
            }
        }

        return null;   // not listed: no update
    }

    /**
     * Team scores from the ranking: the two team entries (no login) by team id (PlayerId 0 blue,
     * 1 red); without usable ids the first two team entries in order.
     *
     * @return array{0: int, 1: int} blue, red
     */
    private function teamScores(array $rows): array
    {
        $teams = array_values(array_filter($rows, fn ($r) => ($r['Login'] ?? '') === ''));
        $byId = [];
        foreach ($teams as $team) {
            if (isset($team['PlayerId']) && in_array((int) $team['PlayerId'], [0, 1], true)) {
                $byId[(int) $team['PlayerId']] = (int) ($team['Score'] ?? 0);
            }
        }
        if (count($byId) === 2) {
            return [$byId[0], $byId[1]];
        }

        return [(int) ($teams[0]['Score'] ?? 0), (int) ($teams[1]['Score'] ?? 0)];
    }

    public function show(Player $player): void
    {
        if ($this->enabled() && !State::hidden($player->login) && $this->lineFor($player) === null) {
            return;   // not in the ranking: nothing new to say
        }
        parent::show($player);
    }

    protected function xml(?Player $player): ?string
    {
        if (!$player || State::hidden($player->login)) {
            return null;
        }
        $line = $this->lineFor($player);
        if ($line === null) {
            return null;
        }
        $body = StatusBar::icon('Icons128x128_1', 'Rankings', '0.7 -0.3 0.003', '3.35 3')
            .StatusBar::value($line[0], 'textcolor="FFFF"')
            .StatusBar::caption('current_ranking_widget', $line[1]);

        return StatusBar::for('current_ranking_widget', $body, null, StatusBar::action('open_ranking'));
    }
}
