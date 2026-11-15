<?php
namespace Funseco\Widgets\Support;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentPlayers;
use Funseco\Core\SharedKernel\UI\Theme\Theme;
use Funseco\Core\SharedKernel\UI\Theme\ThemeRegistry;

/**
 * One widget = one manialink. A panel builds its inner XML (or null = nothing to show) and the
 * base class sends it to one player or everybody; null/disabled sends the empty manialink.
 */
abstract class Panel
{
    /** Manialink name (Ids::MANIALINKS). */
    abstract public function id(): string;

    /** Inner XML for this player (null for shared panels), or null to hide. */
    abstract protected function xml(?Player $player): ?string;

    /** True when every player gets their own XML. */
    protected function perPlayer(): bool
    {
        return false;
    }

    public function enabled(): bool
    {
        return true;
    }

    /** XML for one player (or for $theme, when a shared panel is rendered once per theme). */
    public function render(?Player $player = null, ?Theme $theme = null): string
    {
        $themes = app(ThemeRegistry::class);
        $theme ??= $player ? $themes->forPlayer($player) : $themes->current();
        $inner = $this->enabled() ? $themes->withTheme($theme, fn () => $this->xml($player)) : null;

        return $inner === null ? Ui::clear($this->id()) : Ui::manialink($this->id(), $inner);
    }

    public function show(Player $player): void
    {
        Ui::sendTo($player, $this->render($player));
    }

    public function showAll(): void
    {
        if ($this->perPlayer()) {
            foreach (CurrentPlayers::all() as $player) {
                $this->show($player);
            }

            return;
        }
        // Shared XML: one render per distinct theme in use (at most ThemeRegistry::MAX_SELECTABLE + the
        // default), one broadcast when everybody sees the same theme.
        $groups = app(ThemeRegistry::class)->groupByTheme(CurrentPlayers::all());
        if (count($groups) <= 1) {
            $only = array_key_first($groups);
            Ui::sendAll($only === null ? $this->render() : $this->render(null, app(ThemeRegistry::class)->get($only)));

            return;
        }
        foreach ($groups as $name => $players) {
            $xml = $this->render(null, app(ThemeRegistry::class)->get($name));
            foreach ($players as $player) {
                Ui::sendTo($player, $xml);
            }
        }
    }

    public function hide(Player $player): void
    {
        Ui::sendTo($player, Ui::clear($this->id()));
    }

    public function hideAll(): void
    {
        Ui::sendAll(Ui::clear($this->id()));
    }
}
