<?php
namespace Funseco\Widgets\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\Commands\Command;
use Funseco\Core\SharedKernel\Commands\DeclaresCommand;
use Funseco\Core\SharedKernel\Commands\Described;
use Funseco\Core\SharedKernel\Enums\Role;
use Funseco\Widgets\Support\Text;
use Funseco\Widgets\Visibility;
use Funseco\Widgets\Windows\HelpWindow;
use Funseco\Widgets\Windows\WindowManager;

/** /eyepiece [hide|show|payouts]: stored widget preference, or the help window. */
class EyepieceCommand implements Command, Described
{
    use DeclaresCommand;

    protected string $name = 'eyepiece';
    protected string $description = 'Displays the help for the record widgets (see: /eyepiece)';
    protected string $usage = '[hide|show]';

    public function handle(Player $player, array $args): void
    {
        switch (strtolower($args[0] ?? '')) {
            case 'hide':
                app(Visibility::class)->remember($player, false);
                break;
            case 'show':
                app(Visibility::class)->remember($player, true);
                break;
            case 'payouts':
                // winning payouts pay coppers and are not built: staff only get the "off" notice
                if ($player->effectiveRole()->allows(Role::Operator)) {
                    Text::tell($player, Text::get('payouts_disabled'));
                }
                break;
            default:
                if (!WindowManager::refuseAtScore($player)) {
                    app(HelpWindow::class)->open($player);
                }
        }
    }
}
