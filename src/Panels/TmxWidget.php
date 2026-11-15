<?php
namespace Funseco\Widgets\Panels;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Defaults\NullTrackExchange;
use Funseco\Core\SharedKernel\TrackExchange;
use Funseco\Widgets\Support\Format;
use Funseco\Widgets\Support\Gamemodes;
use Funseco\Widgets\Support\Panel;
use Funseco\Widgets\Support\State;
use Funseco\Widgets\Support\StatusBar;
use Funseco\Widgets\Support\Ui;

/**
 * TMX world record of the current track; opens the TMX info window (spec 10.1). Hidden while no
 * TrackExchange implementation is bound, and for players who hid the record widgets.
 */
class TmxWidget extends Panel
{
    public function id(): string
    {
        return 'tmx';
    }

    public function enabled(): bool
    {
        return Ui::enabled('tmexchange_widget') && !(app(TrackExchange::class) instanceof NullTrackExchange);
    }

    protected function perPlayer(): bool
    {
        return true;
    }

    protected function xml(?Player $player): ?string
    {
        if (!$player || State::hidden($player->login) || State::atScore()) {
            return null;
        }
        $track = app(TrackExchange::class)->current();
        $logo = Ui::image('tmx_logo_normal');
        $focus = Ui::image('tmx_logo_focus');
        $body = $logo
            ? Ui::quad('0.7 -0.1 0.002', '3.2 3.2', 'image="'.Ui::esc($logo).'"'.($focus ? ' imagefocus="'.Ui::esc($focus).'"' : ''))
            : StatusBar::icon('Icons128x128_1', 'LoadTrack', '0.7 -0.1 0.002', '3.2 3.2');
        if ($track) {
            $wr = $track->worldRecord();
            $value = $wr ? ($track->isStunts() || Gamemodes::stunts() ? (string) $wr->score : Format::time($wr->time)) : 'NO';
            $body .= StatusBar::value($value).StatusBar::caption('tmexchange_widget', 'WORLD-RECORD');
        } else {
            $body .= StatusBar::value('NOT AT').StatusBar::caption('tmexchange_widget', 'MANIA-EXCHANGE');
        }

        return StatusBar::for('tmexchange_widget', $body, action: $track ? StatusBar::action('open_tmx') : null);
    }
}
