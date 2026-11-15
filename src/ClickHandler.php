<?php
namespace Funseco\Widgets;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Chat\Facades\Chat;
use Funseco\Widgets\Panels\DonationWidget;
use Funseco\Widgets\Panels\PlacementWidgets;
use Funseco\Widgets\Windows as W;

/** Routes the clicks on widget buttons and window controls ("widgets_<name>" actions). */
class ClickHandler
{
    public function handle(Player $player, string $action): void
    {
        $name = substr($action, strlen('widgets_'));

        match (true) {
            $name === 'toggle'         => app(Visibility::class)->toggle($player),
            $name === 'open_overview'  => app(W\TrackOverviewWindow::class)->open($player),
            $name === 'open_timezone'  => app(W\TimezoneWindow::class)->open($player),
            $name === 'open_tracklist' => app(W\TrackListWindow::class)->open($player),
            $name === 'open_nations'   => app(W\NationsWindow::class)->open($player),
            $name === 'open_tmx'       => app(W\TmxInfoWindow::class)->open($player),
            $name === 'open_help'      => W\WindowManager::refuseAtScore($player) || app(W\HelpWindow::class)->open($player),
            $name === 'open_ranking'   => W\WindowManager::refuseAtScore($player) || \Chat::has('liverankings') && Chat::run($player, 'liverankings') ?: null,
            $name === 'window_close'   => W\WindowManager::close($player),
            $name === 'sub_close'      => W\WindowManager::closeSub($player),
            $name === 'tl_filter'      => app(W\TrackToolsWindows::class)->filter($player),
            $name === 'tl_sort'        => app(W\TrackToolsWindows::class)->sort($player),
            $name === 'tl_clear'       => app(W\TrackListWindow::class)->confirmClear($player),
            $name === 'tl_clear_yes'   => app(W\TrackListWindow::class)->clear($player),
            $name === 'tz_back'        => app(W\TimezoneWindow::class)->back($player),
            str_starts_with($name, 'page_') => $this->page($player, $name),
            $name === 'opt_author'     => app(W\TrackToolsWindows::class)->authors($player),
            str_starts_with($name, 'opt_') => app(W\TrackListWindow::class)->open($player, substr($name, 4)),
            str_starts_with($name, 'card_')    => app(W\TrackListWindow::class)->card($player, (int) substr($name, 5)),
            str_starts_with($name, 'pick_')    => $this->pick($player, (int) substr($name, 5)),
            str_starts_with($name, 'tzgroup_') => app(W\TimezoneWindow::class)->group($player, (int) substr($name, 8)),
            str_starts_with($name, 'chat_')    => $this->chat($player, (int) substr($name, 5)),
            str_starts_with($name, 'donate_') => $this->donate($player, (int) substr($name, 7)),
            default => null,
        };
    }

    /** A donation button: the same /donate command (and billing) as typed chat. */
    private function donate(Player $player, int $index): void
    {
        $amount = app(DonationWidget::class)->amounts()[$index] ?? null;
        if ($amount !== null) {
            Chat::run($player, 'donate', [(string) $amount]);
        }
    }

    private function page(Player $player, string $action): void
    {
        match (W\WindowManager::get($player)['type'] ?? null) {
            'tracklist' => app(W\TrackListWindow::class)->turn($player, $action),
            'authors'   => app(W\TrackToolsWindows::class)->turnAuthors($player, $action),
            'help'      => app(W\HelpWindow::class)->turn($player, $action),
            default     => null,
        };
    }

    private function pick(Player $player, int $index): void
    {
        $type = W\WindowManager::get($player)['type'] ?? null;
        if ($type === 'timezone_zones') {
            app(W\TimezoneWindow::class)->pick($player, $index);
        } elseif ($type === 'authors' && ($author = app(W\TrackToolsWindows::class)->pickAuthor($player, $index)) !== null) {
            app(W\TrackListWindow::class)->open($player, 'author', $author);
        }
    }

    /** A chat-command placement: run the command as if the player typed it. */
    private function chat(Player $player, int $index): void
    {
        if ($command = app(PlacementWidgets::class)->chatCommand($index)) {
            Chat::run($player, $command[0], $command[1]);
        }
    }
}
