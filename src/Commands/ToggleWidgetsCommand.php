<?php
namespace Funseco\Widgets\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\Commands\Command;
use Funseco\Core\SharedKernel\Commands\DeclaresCommand;
use Funseco\Core\SharedKernel\Commands\Described;
use Funseco\Widgets\Visibility;

/** /togglewidgets: hide or show the record widgets for this session (same as F7). */
class ToggleWidgetsCommand implements Command, Described
{
    use DeclaresCommand;

    protected string $name = 'togglewidgets';
    protected string $description = 'Toggle the display of the record widgets (see: /eyepiece)';

    public function handle(Player $player, array $args): void
    {
        app(Visibility::class)->toggle($player);
    }
}
