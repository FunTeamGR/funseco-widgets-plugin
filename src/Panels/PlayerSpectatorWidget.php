<?php
namespace Funseco\Widgets\Panels;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\ServerInfo;
use Funseco\Widgets\Support\Panel;
use Funseco\Widgets\Support\State;
use Funseco\Widgets\Support\StatusBar;
use Funseco\Widgets\Support\Ui;

/** Players and spectators against the server's limits (spec 12). */
class PlayerSpectatorWidget extends Panel
{
    public function id(): string
    {
        return 'players';
    }

    public function enabled(): bool
    {
        return Ui::enabled('player_spectator_widget');
    }

    protected function xml(?Player $player): ?string
    {
        $info = ServerInfo::get();
        $maxPlayers = (int) ($info['max_players'] ?? 0);
        $maxSpecs = (int) ($info['max_spectators'] ?? 0);
        $players = State::playerCount();
        $specs = State::spectatorCount();
        $colour = fn (int $n, int $max) => $max > 0 && $n >= $max ? 'F00' : 'FFF';

        $body = StatusBar::value($players.'/'.$maxPlayers, 'textcolor="'.$colour($players, $maxPlayers).'"', '2.3 -0.6 0.1')
            .StatusBar::caption('player_spectator_widget', 'PLAYER', '2.3 -2.1 0.1')
            .StatusBar::value($specs.'/'.$maxSpecs, 'textcolor="'.$colour($specs, $maxSpecs).'"')
            .StatusBar::caption('player_spectator_widget', 'SPECTATOR');

        return StatusBar::for('player_spectator_widget', $body);
    }
}
