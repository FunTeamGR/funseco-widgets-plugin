<?php
namespace Funseco\Widgets\Panels;

use Funseco\Core\Models\Player;
use Funseco\Widgets\Support\Gamemodes;
use Funseco\Widgets\Support\Panel;
use Funseco\Widgets\Support\State;
use Funseco\Widgets\Support\StatusBar;
use Funseco\Widgets\Support\Ui;

/** Gamemode of the next map, shown at score only (spec 7.2). */
class NextGamemodeWidget extends Panel
{
    public function id(): string
    {
        return 'next_mode';
    }

    public function enabled(): bool
    {
        return Ui::enabled('next_gamemode_widget') && State::atScore();
    }

    protected function xml(?Player $player): ?string
    {
        $mode = (int) State::get('next_mode', Gamemodes::mode());
        $body = StatusBar::icon('Icons128x32_1', Gamemodes::ICONS[$mode] ?? 'RT_TimeAttack', '0.7 -0.3 0.002', '2.9 2.9')
            .StatusBar::caption2('next_gamemode_widget', 'UPCOMING', 'GAMEMODE');

        return StatusBar::for('next_gamemode_widget', $body);
    }
}
