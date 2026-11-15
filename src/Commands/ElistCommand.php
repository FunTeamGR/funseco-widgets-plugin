<?php
namespace Funseco\Widgets\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\Commands\Command;
use Funseco\Core\SharedKernel\Commands\DeclaresCommand;
use Funseco\Core\SharedKernel\Commands\Described;
use Funseco\Widgets\Support\TrackQuery;
use Funseco\Widgets\Windows\TrackListWindow;
use Funseco\Widgets\Windows\TrackToolsWindows;
use Funseco\Widgets\Windows\WindowManager;

/** /elist [param]: the track list window with a filter, a sort or a search (spec 8.6). */
class ElistCommand implements Command, Described
{
    use DeclaresCommand;

    protected string $name = 'elist';
    protected string $description = 'Lists tracks currently on the server (see: /eyepiece)';
    protected string $usage = '[PARAMETER]';

    public function handle(Player $player, array $args): void
    {
        if (WindowManager::refuseAtScore($player)) {
            return;
        }
        $arg = trim(implode(' ', $args));
        if ($arg === '') {
            app(TrackListWindow::class)->open($player);

            return;
        }
        [$option, $param] = TrackQuery::parse($arg);
        if ($option === 'author') {
            app(TrackToolsWindows::class)->authors($player);

            return;
        }
        app(TrackListWindow::class)->open($player, $option, $param);
    }
}
