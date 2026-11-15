<?php
namespace Funseco\Widgets\Panels;

use Funseco\Core\Models\Player;
use Funseco\Widgets\Support\Format;
use Funseco\Widgets\Support\Gamemodes;
use Funseco\Widgets\Support\Panel;
use Funseco\Widgets\Support\StatusBar;
use Funseco\Widgets\Support\Ui;

/** Number of tracks on the server; opens the track list (spec 8.1). */
class TrackCountWidget extends Panel
{
    public function id(): string
    {
        return 'trackcount';
    }

    public function enabled(): bool
    {
        return Ui::enabled('trackcount_widget');
    }

    protected function xml(?Player $player): ?string
    {
        $count = (int) (Gamemodes::info()['NbChallenge'] ?? 0);
        $body = StatusBar::icon('Icons128x128_1', 'LoadTrack', '0.4 0 0.002', '3.8 3.8')
            .StatusBar::value(Format::number($count))
            .StatusBar::caption('trackcount_widget', 'TRACKS');

        return StatusBar::for('trackcount_widget', $body, action: StatusBar::action('open_tracklist'));
    }
}
