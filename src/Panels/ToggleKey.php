<?php
namespace Funseco\Widgets\Panels;

use Funseco\Core\Models\Player;
use Funseco\Widgets\Support\Ids;
use Funseco\Widgets\Support\Panel;

/** The invisible quad bound to action key 3 (F7), sent once per player (spec 13.1). */
class ToggleKey extends Panel
{
    public function id(): string
    {
        return 'toggle_key';
    }

    protected function xml(?Player $player): ?string
    {
        return '<quad posn="70 70 1" sizen="0 0" actionkey="3" action="'.Ids::action('toggle').'"/>';
    }
}
