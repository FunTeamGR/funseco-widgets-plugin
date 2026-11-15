<?php
namespace Funseco\Widgets\Support;

use Funseco\Core\Models\Map;
use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentPlayers;
use Funseco\Core\SharedKernel\FinishHistory;
use Funseco\Core\SharedKernel\KarmaStats;
use Funseco\Core\SharedKernel\RecordBook;

/** The server's tracks joined with the database (medal times, age, multilap) and per-player data. */
final class TrackIndex
{
    public const LIMIT = 5000;

    /**
     * @param callable(array $rows): void $done
     * rows: uid, file, name, author, env, mood, multilap, id, author_time, gold, silver, bronze
     */
    public static function load(callable $done): void
    {
        if (($cached = State::get('tracks')) !== null) {
            $done($cached);

            return;
        }
        \Server::query()->GetChallengeList(self::LIMIT, 0, function ($result) use ($done) {
            $server = is_array($result[0] ?? null) ? $result[0] : [];
            $maps = Map::whereIn('uid', array_column($server, 'UId'))->get()->keyBy('uid');
            $rows = [];
            foreach ($server as $t) {
                if (!is_array($t)) {
                    continue;
                }
                $map = $maps[$t['UId'] ?? ''] ?? null;
                $rows[] = [
                    'uid'         => (string) ($t['UId'] ?? ''),
                    'file'        => (string) ($t['FileName'] ?? ''),
                    'name'        => (string) ($t['Name'] ?? ''),
                    'author'      => (string) ($t['Author'] ?? $map?->author ?? ''),
                    'env'         => (string) ($t['Environnement'] ?? $map?->environment ?? ''),
                    'mood'        => (string) ($map?->mood ?? ''),
                    'multilap'    => (bool) $map?->lap_race,
                    'id'          => $map?->id,
                    'author_time' => (int) ($map?->author_time ?? -1),
                    'gold'        => (int) ($map?->gold_time ?? -1),
                    'silver'      => (int) ($map?->silver_time ?? -1),
                    'bronze'      => (int) ($map?->bronze_time ?? -1),
                ];
            }
            State::put('tracks', $rows);
            $done($rows);
        });
    }

    /** @return array{ranks: array<int,int>, finished: array<int,int>, best: array<int,int>} */
    public static function personal(Player $player): array
    {
        $key = 'personal.'.$player->login;
        if (($cached = State::get($key)) !== null) {
            return $cached;
        }
        $history = app(FinishHistory::class);
        $data = [
            'ranks'    => app(RecordBook::class)->ranksFor($player),
            'finished' => array_flip($history->finishedMapIds($player)),
            'best'     => $history->bestScores($player),
        ];
        State::put($key, $data);

        return $data;
    }

    public static function forgetPersonal(): void
    {
        foreach (CurrentPlayers::all() as $player) {
            State::forget('personal.'.$player->login);
        }
    }

    /** Karma per map id: tmkarma percentage or rasp sum (spec 8.2). @return array<int,int> */
    public static function karma(): array
    {
        $rasp = config('manialinks.features.karma.calculation_method') === 'rasp';
        $out = [];
        foreach (app(KarmaStats::class)->votesByMap() as $mapId => $votes) {
            $total = array_sum($votes);
            if ($total <= 0) {
                continue;
            }
            if ($rasp) {
                $out[$mapId] = (int) array_sum(array_map(fn ($score, $n) => $score * $n, array_keys($votes), $votes));
                continue;
            }
            $weight = [3 => 100, 2 => 80, 1 => 60, -1 => 40, -2 => 20, -3 => 0];
            $sum = 0;
            foreach ($votes as $score => $n) {
                $sum += ($weight[$score] ?? 0) * $n;
            }
            $out[$mapId] = (int) floor($sum / $total);
        }

        return $out;
    }

    /** Local record rank text: "07." padded to the digits of max records, or "$ZNone". */
    public static function rankText(?int $rank): string
    {
        $max = (int) config('widgets.max_records', 50);
        if ($rank === null || $rank < 1 || $rank > $max) {
            return '$ZNone';
        }

        return str_pad((string) $rank, strlen((string) $max), '0', STR_PAD_LEFT).'.';
    }
}
