<?php
namespace Funseco\Widgets\Panels;

use Funseco\Core\Models\Player;
use Funseco\Widgets\Support\Environments;
use Funseco\Widgets\Support\Panel;
use Funseco\Widgets\Support\State;
use Funseco\Widgets\Support\StatusBar;
use Funseco\Widgets\Support\TrackCard;
use Funseco\Widgets\Support\Ui;

/** Environment of the next track, shown at score only (spec 7.1). */
class NextEnvironmentWidget extends Panel
{
    public function id(): string
    {
        return 'next_env';
    }

    public function enabled(): bool
    {
        return Ui::enabled('next_environment_widget') && State::atScore();
    }

    protected function xml(?Player $player): ?string
    {
        $track = TrackCard::fromArray(State::get('next'));

        return StatusBar::for('next_environment_widget', Environments::nextQuad($track->env)
            .StatusBar::caption2('next_environment_widget', 'UPCOMING', 'ENVIRONMENT'));
    }
}
