<?php
namespace Funseco\Widgets\Windows;

use Funseco\Core\Models\Player;
use Funseco\Widgets\Models\PlayerStats;
use Funseco\Widgets\Panels\ClockWidget;
use Funseco\Widgets\Support\Ids;
use Funseco\Widgets\Support\State;
use Funseco\Widgets\Support\Ui;

/** Timezone chooser behind the clock: a world map, then the zones of the clicked region (spec 5.2). */
class TimezoneWindow
{
    /** caption (with colour code), x, y, w, scale, group, [caption x offset, caption y offset, caption width] */
    private const REGIONS = [
        ['$05CAfrica', 36.5, -24, 4.5, 0.55, 'Africa'], ['$5BFArgentina', 22.5, -35.6, 6.5, 0.55, 'Argentina'],
        ['$05CAsia', 45.5, -19.5, 3.5, 0.55, 'Asia'], ['$5BFAsia', 58.5, -21, 3.5, 0.55, 'Asia'],
        ['$05CAustralia', 57.5, -31.5, 5.6, 0.5, 'Australia'], ['$05CBrasil', 22.3, -28, 4.2, 0.55, 'Brasil'],
        ['$05CCanada', 12.5, -11.5, 5, 0.5, 'Canada'], ['$5BFCentral America', 17.8, -23, 11.5, 0.6, 'Central America', 0.5, -0.3],
        ['$05CChina', 51.5, -18, 4.3, 0.55, 'China'], ['$05CEurope', 35.2, -15.3, 4.6, 0.5, 'Europe'],
        ['$5BFIndonesia', 52, -28.5, 5.7, 0.5, 'Indonesia'], ['$05CKazakhstan', 44, -15.4, 5.7, 0.4, 'Kazakhstan', 0.5, -0.6],
        ['$5BFMexico', 8.8, -21.8, 5.2, 0.55, 'Mexico'], ['$05CMongolia', 51, -15.4, 4.7, 0.4, 'Mongolia', 0.5, -0.6],
        ['$05CRussia', 49, -9.7, 4.5, 0.55, 'Russia', 0.5, -0.3], ['$5BFSouth America', 10.5, -28.7, 9.5, 0.55, 'South America', 0.5, -0.3],
        ['$05CUSA', 13.5, -17.5, 3.3, 0.5, 'United States of America'], ['$05CAlaska', 7.5, -7.5, 4.5, 0.5, 'United States of America'],
        ['$FA0Arctic Ocean', 33.5, -1.5, 8, 0.5, 'Arctic, Antarctica & Greenland'],
        ['$FA0Southern Ocean', 36.5, -37.5, 9.8, 0.55, 'Arctic, Antarctica & Greenland', 0.5, -0.5, 16],
        ['$05CGreenland', 25.5, -5, 6.4, 0.5, 'Arctic, Antarctica & Greenland'],
        ['$FA0Atlantic Ocean', 22.5, -18.6, 9.8, 0.55, 'Atlantic Ocean', 0.5, -0.4],
        ['$FA0Pacific Ocean', 1.5, -25, 8.7, 0.55, 'Pacific Ocean'], ['$FA0Pacific Ocean', 63.5, -17.5, 8.5, 0.55, 'Pacific Ocean'],
        ['$FA0Indian Ocean', 44.5, -26.6, 8.5, 0.55, 'Indian Ocean', 0.5, -0.4],
    ];

    /** @return array<string, array<string, string>> group => display => IANA zone */
    public static function groups(): array
    {
        static $groups = null;

        return $groups ??= require __DIR__.'/../../resources/timezones.php';
    }

    private function title(Player $player): string
    {
        $stats = PlayerStats::of($player);
        $zone = $stats->zone();
        try {
            $abbr = (new \DateTimeImmutable('now', new \DateTimeZone($zone)))->format('T');
        } catch (\Throwable) {
            $abbr = 'UNKNOWN';
        }

        return 'Adjust timezone for your personal clock. Current is $FF0'.($stats->zoneName() ?: $zone).' ('.$abbr.')';
    }

    public function open(Player $player): void
    {
        if (State::atScore() || WindowManager::refuseAtScore($player)) {
            return;
        }
        $names = array_keys(self::groups());
        $text = fn (string $pos, string $t) => Ui::label($pos, '77 0', $t, 'textsize="1" textcolor="FFFF" autonewline="1"');
        $content = $text('2 -5', 'Here you can easily adjust the time of the clock to your timezone you life in. Select in the worldmap your location and then choose in the list that appears your timezone. $FF0If you did not see a worldmap below, press DEL.')
            .$text('2 -9', 'For more information about the BEAT time that are displayed below the normal time, just visit $L[http://en.wikipedia.org/wiki/Swatch_Internet_Time]Wikipedia$L');
        $map = ($img = Ui::image('worldmap')) ? Ui::quad('0 0 0.10', '72 43.44', 'image="'.Ui::esc($img).'"') : '';
        foreach (self::REGIONS as $r) {
            [$caption, $x, $y, $w, $scale, $group] = $r;
            $group = array_search($group, $names, true);
            if ($group === false) {
                continue;
            }
            $cx = $x + ($r[6] ?? 0.5);
            $cy = $y + ($r[7] ?? -0.5);
            $map .= '<label posn="'.$x.' '.$y.' 0.11" sizen="'.$w.' 2.3" text=" " focusareacolor1="FFF0" focusareacolor2="FFFF" action="'.Ids::action('tzgroup_'.$group).'"/>'
                .'<label posn="'.$cx.' '.$cy.' 0.12" sizen="'.($r[8] ?? $w).' 0" style="TextCardScores2" textsize="1" scale="'.$scale.'" text="'.Ui::esc($caption).'"/>';
        }
        $content .= '<frame posn="3 -11 0">'.$map.'</frame>';
        WindowManager::put($player, ['type' => 'timezone', 'page' => 1, 'pages' => 1]);
        Ui::sendTo($player, Frame::wrapMain(Frame::main($this->title($player), ['Icons128x32_1', 'RT_TimeAttack'], $content,
            '<frame posn="-6.6 0 0">'.Frame::tool('StarGold', 6.6, null).'</frame>')).Ui::clear('subwindow'));
    }

    public function group(Player $player, int $index): void
    {
        $name = array_keys(self::groups())[$index] ?? null;
        if ($name === null || State::atScore()) {
            return;
        }
        $zones = array_slice(array_keys(self::groups()[$name]), 0, TrackToolsWindows::AUTHORS_PER_PAGE);
        WindowManager::put($player, ['type' => 'timezone_zones', 'page' => 1, 'pages' => 1, 'group' => $name, 'picks' => $zones]);
        $list = '<frame posn="-0.5 -0.3 0">'.app(TrackToolsWindows::class)->pickList($zones).'</frame>';
        Ui::sendTo($player, Frame::wrapMain(Frame::main($this->title($player), ['Icons128x32_1', 'RT_TimeAttack'], $list,
            '<frame posn="-6.6 0 0">'.Frame::tool('ToolUp', 6.6, 'tz_back').'</frame>')));
    }

    public function back(Player $player): void
    {
        $this->open($player);
    }

    /** A zone clicked: store "<display>|<IANA zone>", close the window, re-send the clock. */
    public function pick(Player $player, int $index): void
    {
        $w = WindowManager::get($player);
        if (($w['type'] ?? null) !== 'timezone_zones') {
            return;
        }
        $display = $w['picks'][$index] ?? null;
        $zone = $display !== null ? (self::groups()[$w['group']][$display] ?? null) : null;
        if ($zone === null) {
            return;
        }
        $stats = PlayerStats::of($player);
        $stats->timezone = $display.'|'.$zone;
        $stats->save();
        WindowManager::close($player);
        app(ClockWidget::class)->show($player);
    }
}
