<?php
namespace Funseco\Widgets\Listeners;

use Funseco\Core\Services\Cache\CurrentPlayers;
use Funseco\Core\SharedKernel\Events\RecordWidgetsToggled;
use Funseco\Core\SharedKernel\Events\ThemeChanged;
use Funseco\Core\SharedKernel\RecordWidgetVisibility;
use Funseco\Core\SharedKernel\UI\Theme\ThemeRegistry;
use Funseco\Widgets\Support\State;

/**
 * A theme choice changed: send the widgets again to the players it affects (one player for their own
 * choice, everybody without an own choice for a new server default), race or score set as the game is.
 * Same ids replace the old manialinks; widgets disabled in this gamemode are sent empty.
 */
class ThemeRerender
{
    public function handle(ThemeChanged $e): void
    {
        $themes = app(ThemeRegistry::class);
        $players = $e->login !== null
            ? array_filter([CurrentPlayers::get($e->login)])
            : CurrentPlayers::all()->filter(fn ($p) => !$themes->hasOwn($p))->all();

        foreach ($players as $player) {
            app(Lifecycle::class)->sendRaceTo($player);
            // The record lists belong to other plugins; they redraw on this event. Not at the score screen,
            // where they are hidden.
            State::atScore() || event(new RecordWidgetsToggled($player, !app(RecordWidgetVisibility::class)->hidden($player)));
        }
    }
}
