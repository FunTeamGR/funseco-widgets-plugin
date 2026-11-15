<?php
namespace Funseco\Widgets\Windows;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentPlayers;
use Funseco\Widgets\Support\State;
use Funseco\Widgets\Support\Text;
use Funseco\Widgets\Support\Ui;

/** Which eyepiece window each player has open (one main window and one sub-window). */
final class WindowManager
{
    public static function get(Player|string $player): ?array
    {
        return State::get('win.'.(is_string($player) ? $player : $player->login));
    }

    public static function put(Player $player, array $window): void
    {
        State::put('win.'.$player->login, $window);
        event(new \Funseco\Core\SharedKernel\Events\WindowOpened($player, 'widgets'));
    }

    /** Close both windows of one player. */
    public static function close(Player $player): void
    {
        State::forget('win.'.$player->login);
        Ui::sendTo($player, Ui::clear('window').Ui::clear('subwindow'));
    }

    public static function closeSub(Player $player): void
    {
        Ui::sendTo($player, Ui::clear('subwindow'));
    }

    /** Close everybody's windows (score). */
    public static function closeAll(): void
    {
        foreach (CurrentPlayers::all() as $player) {
            State::forget('win.'.$player->login);
        }
        Ui::sendAll(Ui::clear('window'));
        Ui::sendAll(Ui::clear('subwindow'));
    }

    /** Windows do not open at score (spec 3.4). True when refused. */
    public static function refuseAtScore(Player $player): bool
    {
        if (!State::atScore()) {
            return false;
        }
        Text::tell($player, Text::get('disallow_windows_at_score'));

        return true;
    }
}
