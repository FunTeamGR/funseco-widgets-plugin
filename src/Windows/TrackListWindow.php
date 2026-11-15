<?php
namespace Funseco\Widgets\Windows;

use React\EventLoop\Loop;
use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentMap;
use Funseco\Core\Services\Chat\Facades\Chat;
use Funseco\Core\SharedKernel\Events\JukeboxDropRequested;
use Funseco\Core\SharedKernel\Events\JukeboxRequested;
use Funseco\Core\SharedKernel\JukeboxQueue;
use Funseco\Widgets\Support\Environments;
use Funseco\Widgets\Support\Format;
use Funseco\Widgets\Support\Gamemodes;
use Funseco\Widgets\Support\Ids;
use Funseco\Widgets\Support\State;
use Funseco\Widgets\Support\TrackIndex;
use Funseco\Widgets\Support\TrackQuery;
use Funseco\Widgets\Support\Ui;

/**
 * "Tracks on this Server" (spec 8.2): 20 cards a page with a jukebox button each, filtered or
 * sorted by the options of 8.3, paged with the frame's buttons.
 */
class TrackListWindow
{
    public const PER_PAGE = 20;

    public function open(Player $player, string $option = 'all', ?string $param = null, int $page = 1): void
    {
        if (WindowManager::refuseAtScore($player)) {
            return;
        }
        $threshold = (int) config('manialinks.show_progress_indicator.tracklist', 0);
        $first = State::get('personal.'.$player->login) === null;   // this player's ranks load now

        TrackIndex::load(function (array $rows) use ($player, $option, $param, $page, $threshold, $first) {
            $show = function () use ($player, $option, $param, $page, $rows) {
                $this->display($player, $rows, $option, $param, $page);
            };
            if ($threshold > 0 && $first && count($rows) > $threshold) {
                $this->loading($player);
                Loop::get()->futureTick($show);

                return;
            }
            $show();
        });
    }

    private function loading(Player $player): void
    {
        $anim = Ui::image('progress_indicator');
        $content = ($anim ? Ui::quad('37.7 -21.15 0.11', '22 22', 'halign="center" valign="center" image="'.Ui::esc($anim).'"') : '')
            .Ui::label('37.7 -31.15 0.12', '', '$SLoading... please wait.', 'halign="center" textsize="2" textcolor="FFFF"');
        Ui::sendTo($player, Frame::wrapMain(Frame::main('Loading... please wait.', ['Icons128x128_1', 'United'], $content)));
    }

    private function display(Player $player, array $rows, string $option, ?string $param, int $page): void
    {
        $result = TrackQuery::apply($player, $rows, $option, $param);
        $notice = false;
        if ($result['empty']) {
            $result = TrackQuery::apply($player, $rows, 'all');
            $option = 'all';
            $param = null;
            $notice = true;
        }
        $list = $result['rows'];
        $pages = max(1, (int) ceil(count($list) / self::PER_PAGE));
        $page = max(1, min($pages, $page));
        $slice = array_slice($list, ($page - 1) * self::PER_PAGE, self::PER_PAGE);
        $n = count($list);

        WindowManager::put($player, ['type' => 'tracklist', 'option' => $option, 'param' => $param, 'page' => $page,
            'pages' => $pages, 'uids' => array_column($slice, 'uid')]);

        $title = 'Tracks on this Server   |   Page '.$page.'/'.$pages.'   |   '.$n.' Track'.($n === 1 ? '' : 's').' '.$result['title'];
        $xml = Frame::main($title, ['Icons128x128_1', 'NewTrack'], $this->cards($player, $slice, ($page - 1) * self::PER_PAGE), $this->buttons($player, $page, $pages));
        Ui::sendTo($player, Frame::wrapMain($xml).($notice ? Frame::wrapSub($this->notice()) : Ui::clear('subwindow')));
    }

    private function notice(): string
    {
        return Frame::sub('Notice', ['Icons64x64_1', 'TrackInfo'], Ui::label('3 -6 0.05', '42 0', 'This filter return an empty result, which means that no Track match your wished filter.', 'textsize="2" scale="0.8" autonewline="1" maxline="7"')
            .Frame::dialogButton('Ok', '15.8 -21.7', 'sub_close'));
    }

    /** Re-render the open list (after a jukebox change). */
    public function refresh(Player $player): void
    {
        $w = WindowManager::get($player);
        if (($w['type'] ?? null) === 'tracklist') {
            $this->open($player, $w['option'], $w['param'], $w['page']);
        }
    }

    public function turn(Player $player, string $action): void
    {
        $w = WindowManager::get($player);
        if (($w['type'] ?? null) === 'tracklist') {
            $this->open($player, $w['option'], $w['param'], Frame::turn($action, $w['page'], $w['pages']));
        }
    }

    private function buttons(Player $player, int $page, int $pages): string
    {
        $clear = '';
        if ($player->allowed('clearjukebox')) {
            $clear = '<frame posn="-16.5 0 0">'
                .Ui::styled('0 0 0', '3.2 3.2', 'Icons64x64_1', 'ArrowDown', 'action="'.Ids::action('tl_clear').'"')
                .Ui::quad('0.85 -1.1 0.01', '1.5 1.4', 'bgcolor="EEEF"')
                .Ui::styled('0.25 -0.2 0.02', '2.9 2.9', 'Icons128x128_1', 'LoadTrack')
                .Ui::label('0.8 -0.76 0.03', '', '$S$O$F00X', 'textsize="2" scale="0.8"')
                .'</frame>';
        }

        return $clear.Frame::tool('ToolTree', -6.6, 'tl_filter').Frame::tool('ToolRoot', -3.3, 'tl_sort').Frame::tool('StarGold', 0, null)
            .Frame::paging($page, $pages);
    }

    /** @param array<int, array> $slice */
    private function cards(Player $player, array $slice, int $offset): string
    {
        $queue = app(JukeboxQueue::class);
        $juked = [];
        foreach ($queue->upcoming() as $entry) {
            $juked[$entry['uid']] ??= $entry['login'];
        }
        $recent = array_flip($queue->history());
        $me = TrackIndex::personal($player);
        $karma = TrackIndex::karma();
        $current = CurrentMap::get()?->uid;
        $stunts = Gamemodes::stunts();
        $xml = '';

        foreach ($slice as $i => $t) {
            $isJuked = isset($juked[$t['uid']]);
            $isRecent = isset($recent[$t['uid']]);
            $canMinus = $isJuked && ($juked[$t['uid']] === $player->login || $player->allowed('dropjukebox'));
            $canPlus = !$isJuked && (!$isRecent || $player->allowed('chat_jb_recent'));
            $colour = $isRecent ? 'FFF8' : 'FFFF';
            $name = $isRecent ? Format::stripColors($t['name']) : $t['name'];
            $author = $isRecent ? Format::stripColors($t['author']) : $t['author'];
            [$barStyle, $barSub] = $isJuked ? ['Bgs1InRace', 'BgListLine'] : ($isRecent ? ['BgsPlayerCard', 'BgRacePlayerName'] : ['BgsPlayerCard', 'ProgressBar']);
            $headColour = $isJuked ? '000F' : $colour;
            $x = 19.05 * intdiv($i, 5);
            $y = -9.45 * ($i % 5);
            $action = ($canMinus || $canPlus) ? Ids::action('card_'.$i) : null;

            $xml .= '<frame posn="'.$x.' '.$y.' 0"><format textsize="1" textcolor="'.$colour.'"/>'
                .Environments::cardQuad($t['env'])
                .Ui::styled('0 0 0.02', '17.75 9.2', 'BgsPlayerCard', 'BgRacePlayerName')
                .Ui::styled('0.4 -0.36 0.04', '16.95 2', $barStyle, $barSub)
                .Ui::label('3.8 -0.55 0.05', '17.3 0', 'Track #'.($offset + $i + 1), 'textsize="1" textcolor="'.$headColour.'"')
                .Ui::label('1 -2.7 0.04', '16 2', $name)
                .Ui::label('1 -4.5 0.04', '17.3 2', 'by '.$author, 'scale="0.9"')
                .($action !== null ? $this->button($isJuked ? 'minus' : 'plus', $action) : '')
                .($t['uid'] === $current ? Ui::styled('15.3 0.4 0.06', '3.4 3.4', 'BgRaceScore2', 'Fame') : '')
                .Ui::styled('0.7 -6.9 0.04', '1.5 1.5', 'BgRaceScore2', 'ScoreReplay')
                .Ui::label('2.4 -7.15 0.04', '5 1.5', $t['author_time'] > 0 ? Format::time($t['author_time'], $stunts) : Format::unknown($stunts), 'scale="0.75"')
                .Ui::styled('6.3 -6.8 0.04', '2 1.6', 'Icons128x128_1', 'Rankings')
                .Ui::label('8.1 -7.15 0.04', '3.8 1.5', TrackIndex::rankText($me['ranks'][$t['id']] ?? null), 'scale="0.75"')
                .Ui::styled('11.2 -6.8 0.04', '1.6 1.6', 'Icons64x64_1', 'StateFavourite')
                .Ui::label('12.8 -7.15 0.04', '2.2 1.5', 'L'.($karma[$t['id']] ?? 0), 'scale="0.75"')
                .'</frame>';
        }

        return $xml;
    }

    /** Plus / minus button: the owner's images when set, a game icon otherwise. */
    private function button(string $kind, int $action): string
    {
        $normal = Ui::image('widget_'.$kind.'_normal');
        $focus = Ui::image('widget_'.$kind.'_focus');
        if ($normal) {
            return Ui::quad('14.15 -5.65 0.03', '4 4', 'image="'.Ui::esc($normal).'"'.($focus ? ' imagefocus="'.Ui::esc($focus).'"' : '').' action="'.$action.'"');
        }

        return Ui::styled('14.65 -6.15 0.03', '3 3', 'Icons64x64_1', $kind === 'plus' ? 'Add' : 'Close', 'action="'.$action.'"');
    }

    // ---- clicks ----

    /** A card's button: add the track to the jukebox, or drop it. */
    public function card(Player $player, int $index): void
    {
        $w = WindowManager::get($player);
        $uid = $w['uids'][$index] ?? null;
        if (($w['type'] ?? null) !== 'tracklist' || $uid === null) {
            return;
        }
        $row = collect(State::get('tracks', []))->firstWhere('uid', $uid);
        $juked = collect(app(JukeboxQueue::class)->upcoming())->contains('uid', $uid);
        if ($juked) {
            event(new JukeboxDropRequested($player, $uid));
            if (Ui::bool(config('manialinks.features.tracklist.force_tracklist', 'true'))) {
                $this->refresh($player);
            }

            return;
        }
        if (!$row) {
            return;
        }
        event(new JukeboxRequested($player, ['uid' => $uid, 'file' => $row['file'], 'name' => $row['name'], 'env' => $row['env']]));
        $this->refresh($player);
    }

    public function confirmClear(Player $player): void
    {
        if (!$player->allowed('clearjukebox')) {
            return;
        }
        $content = Ui::label('3 -6 0.05', '42 0', 'Do you really want to drop the complete Jukebox?', 'textsize="2" scale="0.8" autonewline="1" maxline="7"')
            .Frame::dialogButton('Yes', '2.5 -21.7', 'tl_clear_yes').Frame::dialogButton('No', '11.5 -21.7', 'sub_close');
        Ui::sendTo($player, Frame::wrapSub(Frame::sub('Notice', ['Icons64x64_1', 'TrackInfo'], $content)));
    }

    public function clear(Player $player): void
    {
        WindowManager::closeSub($player);
        Chat::run($player, 'admin clearjukebox');
        if (Ui::bool(config('manialinks.features.tracklist.force_tracklist', 'true'))) {
            $this->refresh($player);
        }
    }
}
