<?php
namespace Funseco\Widgets\Windows;

use React\EventLoop\Loop;
use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\ServerInfo;
use Funseco\Core\SharedKernel\ServerRanks;
use Funseco\Widgets\Support\Format;
use Funseco\Widgets\Support\Ui;

/** Welcome window for joining players (spec 20); off in the owner's configuration. */
class WelcomeWindow
{
    public function enabled(): bool
    {
        return Ui::enabled('welcome_window');
    }

    public function show(Player $player): void
    {
        if (!$this->enabled()) {
            return;
        }
        if (Ui::bool(Ui::cfg('welcome_window', 'hide.ranked_player', 'false')) && app(ServerRanks::class)->positionOf($player) !== null) {
            return;
        }
        $server = Format::stripColors((string) (ServerInfo::get()['server_name'] ?? ''));
        $message = strtr((string) Ui::cfg('welcome_window', 'message', ''), [
            '{br}' => "\n", '{server}' => $server, '{player}' => $player->nickname.'$Z',
        ]);
        $img = Ui::cfg('welcome_window', 'image.normal') ?: config('widgets.branding.image.normal');
        $focus = Ui::cfg('welcome_window', 'image.focus') ?: config('widgets.branding.image.focus') ?: $img;
        $link = Ui::cfg('welcome_window', 'image.link') ?: config('widgets.branding.image.link');
        $content = ($img ? Ui::quad('56.5 -0.3 0.11', '18 46.5', 'image="'.Ui::esc($img).'" imagefocus="'.Ui::esc($focus).'"'.($link ? ' url="'.Ui::esc($link).'"' : '')) : '')
            .Ui::label('0.5 -0.3 0.04', ($img ? '54' : '72.4').' 0', $message, 'textsize="1" textcolor="FF0F" autonewline="1"');
        $icon = [(string) Ui::cfg('welcome_window', 'icon_style', 'Icons128x128_1'), (string) Ui::cfg('welcome_window', 'icon_substyle', 'Credits')];

        WindowManager::put($player, ['type' => 'welcome', 'page' => 1, 'pages' => 1]);
        Ui::sendTo($player, Frame::wrapMain(Frame::main('Welcome to '.$server.'!', $icon, $content)));

        $seconds = (int) Ui::cfg('welcome_window', 'autohide', 0);
        if ($seconds > 0) {
            Loop::get()->addTimer($seconds, function () use ($player) {
                if ((WindowManager::get($player)['type'] ?? null) === 'welcome') {
                    WindowManager::close($player);
                }
            });
        }
    }
}
