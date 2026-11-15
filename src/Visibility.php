<?php
namespace Funseco\Widgets;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\Events\RecordWidgetsToggled;
use Funseco\Core\SharedKernel\RecordWidgetVisibility;
use Funseco\Widgets\Models\PlayerStats;
use Funseco\Widgets\Panels\CurrentRankingWidget;
use Funseco\Widgets\Panels\RoundScoreWidget;
use Funseco\Widgets\Panels\TmxWidget;
use Funseco\Widgets\Support\State;
use Funseco\Widgets\Support\Text;
use Funseco\Widgets\Windows\WindowManager;

/**
 * Record widgets shown or hidden per player: F7 / /togglewidgets (this session) and
 * /eyepiece hide|show (stored). Record list plugins consult it and re-render on
 * RecordWidgetsToggled; this plugin also hides its own ranking and TMX widgets, and brings
 * them back on show (fixing the original, where they stayed away).
 */
class Visibility implements RecordWidgetVisibility
{
    public function hidden(Player $player): bool
    {
        return State::hidden($player->login);
    }

    /** F7 or /togglewidgets. */
    public function toggle(Player $player): void
    {
        if (State::atScore()) {
            return;
        }
        if (State::nice()) {
            Text::tell($player, Text::get('toggling_disabled'));

            return;
        }
        $hide = !$this->hidden($player);
        $this->apply($player, $hide);
        Text::tell($player, Text::get($hide ? 'widgets_disabled' : 'widgets_enabled'));
    }

    /** /eyepiece hide|show: same effect, stored for the next visit. Works at score too. */
    public function remember(Player $player, bool $show): void
    {
        $stats = PlayerStats::of($player);
        $stats->display_widgets = $show;
        $stats->save();
        if ($this->hidden($player) === $show) {
            $this->apply($player, !$show);
        }
        Text::tell($player, Text::get($show ? 'widgets_preference_enabled' : 'widgets_preference_disabled'));
    }

    /** Apply the state: events for the record lists, own widgets, open windows. */
    public function apply(Player $player, bool $hide): void
    {
        State::setHidden($player->login, $hide);
        if ($hide) {
            WindowManager::close($player);
        }
        if (State::atScore()) {
            return;
        }
        event(new RecordWidgetsToggled($player, !$hide));
        app(CurrentRankingWidget::class)->show($player);
        app(TmxWidget::class)->show($player);
        if (!$hide) {
            app(RoundScoreWidget::class)->show($player);
        }
    }
}
