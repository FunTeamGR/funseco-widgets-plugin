<?php
namespace Funseco\Widgets\Panels;

use Funseco\Core\Models\Player;
use Funseco\Widgets\Support\Panel;
use Funseco\Widgets\Support\State;
use Funseco\Widgets\Support\StatusBar;
use Funseco\Widgets\Support\Ui;

/** Server ladder limits as "50-100k" (spec 11). */
class LadderLimitWidget extends Panel
{
    public function id(): string
    {
        return 'ladder';
    }

    public function enabled(): bool
    {
        return Ui::enabled('ladderlimit_widget') && State::get('ladder') !== null;
    }

    protected function xml(?Player $player): ?string
    {
        [$min, $max] = State::get('ladder', [0, 0]);
        $text = substr((string) ($min / 1000), 0, 3).'-'.substr((string) ($max / 1000), 0, 3).'k';
        $body = StatusBar::icon('Icons128x128_1', 'LadderPoints', '0.7 -0.3 0.002', '3.35 3')
            .StatusBar::value($text, 'scale="0.9"')
            .StatusBar::caption('ladderlimit_widget', 'LADDER');

        return StatusBar::for('ladderlimit_widget', $body);
    }
}
