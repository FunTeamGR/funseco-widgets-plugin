<?php
namespace Funseco\Widgets\Panels;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\ServerInfo;
use Funseco\Widgets\Support\Panel;
use Funseco\Widgets\Support\State;
use Funseco\Widgets\Support\StatusBar;
use Funseco\Widgets\Support\Ui;

/** "Add to favorites" button, race and score position (spec 16). Needs the server login in config. */
class FavoriteWidget extends Panel
{
    public function id(): string
    {
        return 'favorite';
    }

    public function enabled(): bool
    {
        return Ui::enabled('favorite_widget') && (string) config('widgets.favorite.login') !== '';
    }

    /** The in-game manialink, values URL-encoded raw (the name keeps its $ codes). */
    public static function link(): string
    {
        $name = (string) (config('widgets.favorite.name') ?: (ServerInfo::get()['server_name'] ?? ''));

        return 'addfavorite?action=add&server='.rawurlencode((string) config('widgets.favorite.login'))
            .'&name='.rawurlencode($name).'&zone='.rawurlencode((string) config('widgets.favorite.zone', ''));
    }

    protected function xml(?Player $player): ?string
    {
        $body = StatusBar::icon('Icons128x128_Blink', 'ServersFavorites', '0.7 -0.2 0.002', '3.2 3.2')
            .StatusBar::value('ADD')
            .StatusBar::caption('favorite_widget', 'FAVORITE');

        return StatusBar::for('favorite_widget', $body, State::atScore() ? 'score' : 'race', extra: ['manialink' => self::link(), 'addplayerid' => true]);
    }
}
