<?php
namespace Funseco\Widgets\Support;

use Funseco\Core\Services\Cache\ServerInfo;

/** Gamemode names/icons (spec 1.5) and the server's game info, read at map start. */
final class Gamemodes
{
    public const NAMES = [0 => 'ROUNDS', 1 => 'TIME ATTACK', 2 => 'TEAM', 3 => 'LAPS', 4 => 'STUNTS', 5 => 'CUP'];
    public const ICONS = [0 => 'RT_Rounds', 1 => 'RT_TimeAttack', 2 => 'RT_Team', 3 => 'RT_Laps', 4 => 'RT_Stunts', 5 => 'RT_Cup'];

    public static function mode(): int
    {
        return (int) (ServerInfo::getGameMode() ?? 1);
    }

    public static function stunts(): bool
    {
        return self::mode() === 4;
    }

    /** Current game info (limits, laps, new rules …), as last read. */
    public static function info(): array
    {
        return (array) State::get('gameinfo', []);
    }

    /** Re-read GetCurrentGameInfo (and the ladder limits when asked); $done runs afterwards. */
    public static function load(?callable $done = null): void
    {
        \Server::query()->GetCurrentGameInfo(1, function ($result) use ($done) {
            if (is_array($result[0] ?? null)) {
                State::put('gameinfo', $result[0]);
                ServerInfo::setRoundsForcedLaps((int) ($result[0]['RoundsForcedLaps'] ?? 0));   // read by Dedimania
                if (isset($result[0]['GameMode']) && is_numeric($result[0]['GameMode'])) {
                    ServerInfo::setGameMode((int) $result[0]['GameMode']);
                }
            }
            $done && $done();
        });
    }

    public static function loadLadder(?callable $done = null): void
    {
        \Server::query()->GetLadderServerLimits(function ($result) use ($done) {
            if (is_array($result[0] ?? null)) {
                State::put('ladder', [
                    (int) ($result[0]['LadderServerLimitMin'] ?? 0),
                    (int) ($result[0]['LadderServerLimitMax'] ?? 0),
                ]);
            }
            $done && $done();
        });
    }

    public static function nextMode(?callable $done): void
    {
        \Server::query()->GetNextGameInfo(1, function ($result) use ($done) {
            $mode = is_array($result[0] ?? null) && isset($result[0]['GameMode']) ? (int) $result[0]['GameMode'] : self::mode();
            $done($mode);
        });
    }

    /** The limit text of the gamemode widget, or null (stunts). */
    public static function limit(int $mode, array $info): ?string
    {
        $new = fn (string $key) => Ui::bool($info[$key] ?? false);

        return match ($mode) {
            0 => ($new('RoundsUseNewRules') ? ($info['RoundsPointsLimitNewRules'] ?? 0) : ($info['RoundsPointsLimit'] ?? 0)).' pts.',
            1 => Format::short((int) ($info['TimeAttackLimit'] ?? 0)),
            2 => ($new('TeamUseNewRules') ? ($info['TeamPointsLimitNewRules'] ?? 0) : ($info['TeamPointsLimit'] ?? 0)).' pts.',
            3 => (int) ($info['LapsTimeLimit'] ?? 0) > 0 ? Format::short((int) $info['LapsTimeLimit']).' min.' : ($info['LapsNbLaps'] ?? 0).' laps',
            5 => ($info['CupPointsLimit'] ?? 0).' pts.',
            default => null,
        };
    }
}
