<?php
namespace Funseco\Widgets\Panels;

use Funseco\Core\Models\Player;
use Funseco\Widgets\Support\Gamemodes;
use Funseco\Widgets\Support\Panel;
use Funseco\Widgets\Support\StatusBar;
use Funseco\Widgets\Support\Ui;

/** Current gamemode and its limit (spec 6). */
class GamemodeWidget extends Panel
{
    public function id(): string
    {
        return 'gamemode';
    }

    public function enabled(): bool
    {
        return Ui::enabled('gamemode_widget');
    }

    protected function xml(?Player $player): ?string
    {
        $mode = Gamemodes::mode();
        $limit = Gamemodes::limit($mode, Gamemodes::info());
        $body = StatusBar::icon('Icons128x32_1', Gamemodes::ICONS[$mode] ?? 'RT_TimeAttack', '0.7 -0.3 0.002', '2.9 2.9')
            .($limit !== null ? StatusBar::value($limit, 'textsize="1"') : '')
            .StatusBar::caption('gamemode_widget', Gamemodes::NAMES[$mode] ?? '');

        return StatusBar::for('gamemode_widget', $body);
    }
}
