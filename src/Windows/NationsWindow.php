<?php
namespace Funseco\Widgets\Windows;

use Funseco\Core\Models\Player;
use Funseco\Widgets\Support\Format;
use Funseco\Widgets\Support\State;
use Funseco\Widgets\Support\Ui;

/** Players per nation over everybody ever seen (spec 9.2); refreshed whenever score starts. */
class NationsWindow
{
    /** @return array<string, int> nation code => players, most first */
    public static function counts(): array
    {
        return \Funseco\Core\Services\Stats\NationCounts::byFlag();
    }

    public function open(Player $player): void
    {
        if (WindowManager::refuseAtScore($player)) {
            return;
        }
        $counts = self::counts();
        if ($counts === []) {
            return;
        }
        $names = require __DIR__.'/../../resources/nations.php';
        $icon = (array) config('manialinks.scoretable_lists.top_nations', []);
        $xml = '<frame posn="0 -0.8 0"><format textsize="1" textcolor="'.Ui::colour('race', 'default').'"/>';
        foreach ([0, 19.05, 38.1, 57.15] as $x) {
            $xml .= Ui::styled("$x 0 0.01", '17.75 46.88', 'BgsPlayerCard', 'BgRacePlayerName');
        }
        $i = 0;
        foreach ($counts as $code => $n) {
            $col = intdiv($i, 25);
            $line = $i % 25;
            $x = 19.05 * $col;
            $y = -1.83 * $line;
            $flag = $code === 'OTH' ? 'other' : $code;
            $xml .= Ui::label(($x + 2.75).' '.($y - 0.2).' 0.04', '2.5 1.7', Format::number($n), 'halign="right" scale="0.9" textcolor="'.Ui::colour('race', 'scores').'"')
                .Ui::quad(($x + 3.5).' '.($y + 0.3).' 0.04', '2 2', 'image="tmtp://Skins/Avatars/Flags/'.Ui::esc($flag).'.dds"')
                .Ui::label(($x + 6.2).' '.($y - 0.2).' 0.04', '11.2 1.7', $names[$code] ?? $code, 'scale="0.9"');
            $i++;
        }
        WindowManager::put($player, ['type' => 'nations', 'page' => 1, 'pages' => 1]);
        $title = (string) ($icon['title'] ?? 'Top Nations');
        $style = [(string) ($icon['icon_style'] ?? 'Icons64x64_1'), (string) ($icon['icon_substyle'] ?? 'ToolLeague1')];
        Ui::sendTo($player, Frame::wrapMain(Frame::main($title, $style, $xml.'</frame>')).Ui::clear('subwindow'));
    }
}
