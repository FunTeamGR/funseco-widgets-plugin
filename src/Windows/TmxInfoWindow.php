<?php
namespace Funseco\Widgets\Windows;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\TrackExchange;
use Funseco\Core\SharedKernel\Values\TmxTrack;
use Funseco\Widgets\Support\Format;
use Funseco\Widgets\Support\Gamemodes;
use Funseco\Widgets\Support\ReplayLinks;
use Funseco\Widgets\Support\State;
use Funseco\Widgets\Support\TrackCard;
use Funseco\Widgets\Support\Ui;

/** TMX Track Info (spec 10.2): screenshot, comment, times, top-10 replays and links. */
class TmxInfoWindow
{
    public function open(Player $player): void
    {
        if (WindowManager::refuseAtScore($player)) {
            return;
        }
        $tmx = app(TrackExchange::class)->current();
        if (!$tmx) {
            return;
        }
        $card = TrackCard::fromArray(State::get('current_card'));
        $stunts = $tmx->isStunts() || Gamemodes::stunts();
        $date = TmxTrack::date($tmx->updatedAt && $tmx->updatedAt !== $tmx->uploadedAt ? $tmx->updatedAt : $tmx->uploadedAt);
        $shot = $tmx->imageUrl ?: Ui::image('no_screenshot');

        $content = Ui::quad('0 0 0.03', '32.2 24.2', 'bgcolor="FFF9"')
            .Ui::label('5 -10.8 0.04', '', 'Press DEL if can not see an Image here!', 'scale="0.6"')
            .($shot ? Ui::quad('0.1 -0.1 0.50', '32 24', 'image="'.Ui::esc($shot).'"') : '')
            .Ui::label('0.4 -25.3 0.04', '32 3', '$S'.$tmx->name, 'textsize="3"')
            .Ui::label('0.4 -28.1 0.04', '', 'by '.$tmx->author, 'textsize="2" scale="0.9"')
            .Ui::label('0.4 -30.3 0.04', '18 1.5', 'from '.$date, 'textsize="1" scale="0.8"')
            .Ui::label('18.4 -30.3 0.04', '', 'TMX-ID: '.$tmx->id, 'textsize="1" scale="0.8"')
            .($tmx->authorComment !== '' ? Ui::label('0.4 -32.5 0.04', '61 16', $this->comment($tmx->authorComment), 'textsize="1" scale="0.9" autonewline="1" maxline="8"') : '')
            .'<frame posn="36.1 -17.8 0">'.TrackOverviewWindow::timesBlock($card).'</frame>'
            .'<frame posn="43 -17.8 0">'.TrackOverviewWindow::tmxInfo($tmx, '14.5 2').'</frame>'
            .$this->records($tmx, $stunts)
            .$this->links($tmx);
        WindowManager::put($player, ['type' => 'tmx', 'page' => 1, 'pages' => 1]);
        Ui::sendTo($player, Frame::wrapMain(Frame::main('TMX Track Info', ['Icons128x128_1', 'LoadTrack'], $content)).Ui::clear('subwindow'));
    }

    /** <br> becomes a line break, BB-code and HTML are removed, bare URLs become links. */
    public function comment(string $text): string
    {
        $text = preg_replace('#<br\s*/?>#i', "\n", $text);
        $text = preg_replace('/\[[^\]]*\]/', '', $text);
        $text = strip_tags($text);
        $text = preg_replace('#((?:https?|ftp)://[^\s<>\[\]$]+)#i', '$L[$1]$1$L', $text);

        return $text;
    }

    private function records(TmxTrack $tmx, bool $stunts): string
    {
        $xml = '<frame posn="57.15 0 1"><format textsize="1"/>'
            .Ui::styled('0 0 0', '21.2 47', 'BgsPlayerCard', 'BgRacePlayerName')
            .Ui::styled('0.4 -0.36 0.01', '20.4 2', 'BgsPlayerCard', 'ProgressBar')
            .Ui::styled('0.6 0 0.02', '2.5 2.5', 'Icons128x32_1', 'RT_Cup')
            .Ui::label('3.2 -0.55 0.02', '', 'TMX Top10 Records', 'textsize="1"')
            .'<frame posn="0 -2.7 0">';
        $trackDate = TmxTrack::date($tmx->updatedAt ?: $tmx->uploadedAt);
        foreach (array_slice($tmx->records, 0, 10) as $i => $r) {
            $y = -1.75 * $i;
            $old = TmxTrack::date($r->trackAt) !== '' && TmxTrack::date($r->trackAt) !== $trackDate;
            $time = ($stunts ? (string) $r->score : Format::time($r->time)).($old ? ' $F00$W!' : '');
            $xml .= Ui::label("2.6 $y 0.02", '2 1.7', ($i + 1).'.', 'halign="right" scale="0.9"')
                .Ui::label("6.4 $y 0.02", '4 1.7', $time, 'halign="right" scale="0.9" textcolor="'.Ui::colour('race', 'scores').'"')
                .Ui::label("6.9 $y 0.02", '10.6 1.7', $r->name, 'scale="0.9"')
                .ReplayLinks::recordIcons($tmx, $r, 21.0, $y - 0.1, 1.5);
        }

        return $xml.'</frame></frame>';
    }

    private function links(TmxTrack $tmx): string
    {
        $logo = Ui::image('tmx_logo_normal');
        $focus = Ui::image('tmx_logo_focus');

        return '<frame posn="33.7 0 1"><format textsize="1" style="TextCardScores2"/>'
            .($logo ? Ui::quad('0 0 0.01', '11 5.5', 'image="'.Ui::esc($logo).'"'.($focus ? ' imagefocus="'.Ui::esc($focus).'"' : '').' url="'.Ui::esc(config('widgets.tmx_home_url', 'https://tmnf.exchange/')).'"') : '')
            .Ui::label('11.7 -0.3 0.02', '24 2', '$FFF» Visit Track Page', 'scale="0.5" url="'.Ui::esc(ReplayLinks::clientUrl((string) $tmx->pageUrl)).'"')
            .Ui::label('11.7 -2.2 0.02', '24 2', '$FFF» Download Track', 'scale="0.5" url="'.Ui::esc(ReplayLinks::clientUrl((string) $tmx->downloadUrl)).'"')
            .($tmx->replayUrl ? Ui::label('11.7 -4.1 0.02', '24 2', '$FFF» Download Replay', 'scale="0.5" url="'.Ui::esc(ReplayLinks::clientUrl((string) $tmx->replayUrl)).'"') : '')
            .'</frame>';
    }
}
