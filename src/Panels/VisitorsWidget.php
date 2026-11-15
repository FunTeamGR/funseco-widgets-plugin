<?php
namespace Funseco\Widgets\Panels;

use Funseco\Core\Models\Player;
use Funseco\Core\Models\Player as PlayerModel;
use Funseco\Widgets\Support\Format;
use Funseco\Widgets\Support\Panel;
use Funseco\Widgets\Support\State;
use Funseco\Widgets\Support\StatusBar;
use Funseco\Widgets\Support\Ui;

/** Number of distinct players ever seen; opens top nations (spec 9.1). */
class VisitorsWidget extends Panel
{
    public function id(): string
    {
        return 'visitors';
    }

    public function enabled(): bool
    {
        return Ui::enabled('visitors_widget');
    }

    /** Recount: the highest player id, as the original did. */
    public static function refresh(): void
    {
        State::put('visitors', (int) PlayerModel::query()->max('id'));
    }

    protected function xml(?Player $player): ?string
    {
        if (State::get('visitors') === null) {
            self::refresh();
        }
        $body = StatusBar::icon('Icons128x128_1', 'Buddies', '0.7 -0.3 0.002', '3.2 3.2')
            .StatusBar::value(Format::number((int) State::get('visitors', 0)))
            .StatusBar::caption('visitors_widget', 'VISITORS');

        return StatusBar::for('visitors_widget', $body, action: StatusBar::action('open_nations'));
    }
}
