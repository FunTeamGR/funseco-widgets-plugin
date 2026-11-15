<?php
namespace Funseco\Widgets\Windows;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Chat\Facades\Chat;
use Funseco\Widgets\Support\Ids;
use Funseco\Widgets\Support\Ui;

/** The widgets help (spec 13.4): three pages — player commands, master admin commands, about. */
class HelpWindow
{
    private const PAGES = 3;

    public function open(Player $player, int $page = 1): void
    {
        $page = max(1, min(self::PAGES, $page));
        WindowManager::put($player, ['type' => 'help', 'page' => $page, 'pages' => self::PAGES]);

        $name = (string) config('widgets.branding.name', 'Funseco');
        $url = (string) config('widgets.branding.url', '');
        $title = ($url !== '' ? '$L['.$url.']'.$name.'$L' : $name).' widgets';
        $content = '<frame posn="0.5 -0.3 0"><format textsize="1"/>'.$this->image().match ($page) {
            1 => $this->page1($player),
            2 => $this->page2(),
            default => $this->about(),
        }.'</frame>';

        $prev = $page > 1 ? Frame::tool('ArrowPrev', 19.8, 'page_prev') : Frame::tool('ArrowPrev', 19.8, null);
        $next = $page < self::PAGES ? Frame::tool('ArrowNext', 23.1, 'page_next') : Frame::tool('ArrowNext', 23.1, null);
        Ui::sendTo($player, Frame::wrapMain(Frame::main($title, ['BgRaceScore2', 'LadderRank'], $content, $prev.$next)).Ui::clear('subwindow'));
    }

    public function turn(Player $player, string $action): void
    {
        $w = WindowManager::get($player);
        if (($w['type'] ?? null) === 'help') {
            $this->open($player, $w['page'] + (str_starts_with($action, 'page_prev') ? -1 : 1));
        }
    }

    private function image(): string
    {
        $img = config('widgets.branding.image.normal');
        if (!$img) {
            return '';
        }
        $focus = config('widgets.branding.image.focus') ?: $img;
        $link = config('widgets.branding.image.link');

        return Ui::quad('56 0 0.01', '18 46.5', 'image="'.Ui::esc($img).'" imagefocus="'.Ui::esc($focus).'"'.($link ? ' url="'.Ui::esc($link).'"' : ''));
    }

    private function line(float $y, string $cmd, string $text, float $cw = 17, float $tw = 37.5): string
    {
        return Ui::label("0 $y 0.02", "$cw 2", $cmd, 'textcolor="FFFF"').Ui::label("18 $y 0.02", "$tw 2", $text, 'textcolor="FF0F" autonewline="1"');
    }

    private function page1(Player $player): string
    {
        $xml = $this->line(0, '/eyepiece', 'Display this help')
            .$this->line(-2, '/eyepiece hide', 'Hide the record widgets and store this as your preference')
            .$this->line(-4, '/eyepiece show', 'Show the record widgets and store this as your preference')
            .$this->line(-9, '/togglewidgets $FF0or$FFF F7', 'Toggle the display of the record widgets');
        if (Chat::has('estat')) {
            $xml .= $this->line(-12, '/estat [PARAMETER]', "Optional parameter can be:\n\$FFFdedirecs, localrecs, topnations, topranks, topwinners, mostrecords, mostfinished, topplaytime, topdonators, toptracks, topvoters, topvisitors, topactive");
        }
        if (Chat::has('emusic')) {
            $xml .= $this->line(-23, '/emusic', 'Lists musics currently on the server');
        }
        $xml .= $this->line(-26, '/elist [PARAMETER]', "Lists tracks currently on the server, optional parameter can be:\n\$FFFjukebox, author, track, norecent, onlyrecent, norank, onlyrank, nomulti, onlymulti, noauthor, nogold, nosilver, nobronze, nofinish, best, worst, shortest, longest, newest, oldest, sortauthor, bestkarma, worstkarma \$FF0or a keyword to search for");
        if (Ui::bool(config('manialinks.features.mark_online_player_records', 'true'))) {
            $xml .= Ui::styled('-0.3 -38.5 0.02', '2.8 2.8', 'Icons128x128_1', 'Solo')
                .Ui::label('3 -38.7 0.02', '52 2', 'Marker for an other Player that is currently online at this Server with a record and is ranked before you', 'autonewline="1"')
                .Ui::styled('-0.3 -41.3 0.02', '2.8 2.8', 'Icons128x128_1', 'ChallengeAuthor')
                .Ui::label('3 -41.5 0.02', '52 2', 'Marker for an other Player that is currently online at this Server with a record and is ranked behind you', 'autonewline="1"');
        }

        return $xml.Ui::styled('0 -44.5 0.02', '2.2 2.2', 'Icons64x64_1', 'ArrowNext')
            .Ui::label('3 -44.5 0.02', '52 2', 'Marker for your driven record');
    }

    private function page2(): string
    {
        $xml = Ui::label('0 0 0.02', '55 2', 'Commands for MasterAdmins only:', 'textcolor="FF0F"');
        $i = 0;
        foreach ([
            '/eyeset reload' => 'Re-sends all widgets',
            '/eyeset lfresh [INT]' => 'Set the normal <refresh_interval> sec.',
            '/eyeset hfresh [INT]' => 'Set the nice <refresh_interval> sec.',
            '/eyeset llimit [INT]' => 'Set the nice <lower_limit> Players',
            '/eyeset ulimit [INT]' => 'Set the nice <upper_limit> Players',
            '/eyeset forcenice (true|false)' => 'Set the <nicemode><force>',
            '/eyeset playermarker (true|false)' => 'Set the <features><mark_online_player_records>',
        ] as $cmd => $text) {
            $xml .= $this->line(-2 - 2 * $i++, $cmd, $text);
        }

        return $xml.Ui::label('0 -22 0.02', '55 2', 'Commands for Op/Admin/MasterAdmin:', 'textcolor="FF0F"')
            .$this->line(-24, '/eyepiece payouts', 'Show the outstanding winning payouts');
    }

    private function about(): string
    {
        $xml = '';
        foreach ((array) config('widgets.branding.about', []) as $i => $line) {
            $xml .= Ui::label('0 '.-(2 * $i).' 0.02', '54 2', (string) $line);
        }

        return $xml;
    }
}
