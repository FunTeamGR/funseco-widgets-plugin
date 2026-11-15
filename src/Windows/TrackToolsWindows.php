<?php
namespace Funseco\Widgets\Windows;

use Funseco\Core\Models\Player;
use Funseco\Widgets\Support\Environments;
use Funseco\Widgets\Support\Gamemodes;
use Funseco\Widgets\Support\Ids;
use Funseco\Widgets\Support\State;
use Funseco\Widgets\Support\TrackIndex;
use Funseco\Widgets\Support\Ui;

/** The filter window, the sort window and the author list that sit beside the track list (spec 8.4, 8.5). */
class TrackToolsWindows
{
    public const AUTHORS_PER_PAGE = 80;

    /** Filter cards: col, row, header icon [style, substyle, overlay?], title, description, option */
    private function filterCards(): array
    {
        $s = Gamemodes::stunts();

        return [
            [0, 0, ['MedalsBig', 'MedalNadeo', 'Close'], $s ? 'No Score' : 'No Author Time', 'Display only Tracks where you did not beat the '.($s ? 'score' : 'author time').' on.', 'noauthor'],
            [0, 1, ['Icons128x128_1', 'LoadTrack'], 'Only Recent Tracks', 'Display only Tracks that have been played recently.', 'onlyrecent'],
            [0, 2, ['Icons128x128_1', 'LoadTrack', 'QuitRace'], 'No Recent Tracks', 'Display only Tracks that have been played not recently.', 'norecent'],
            [1, 0, ['MedalsBig', 'MedalGold', 'Close'], 'No Gold Time', 'Display only Tracks where you did not beat the gold time on.', 'nogold'],
            [1, 1, ['Icons128x128_1', 'Rankings'], 'Only Ranked Tracks', 'Display only Tracks where you already have a rank received.', 'onlyrank'],
            [1, 2, ['Icons128x128_1', 'Rankings', 'QuitRace'], 'Not Ranked Tracks', 'Display only Tracks where you not already have a rank received.', 'norank'],
            [2, 0, ['MedalsBig', 'MedalSilver', 'Close'], $s ? 'No Silver Score' : 'No Silver Time', 'Display only Tracks where you did not beat the silver time on.', 'nosilver'],
            [2, 1, ['Icons128x32_1', 'RT_Laps'], 'Only Multilap Tracks', 'Display only Tracks that are multilaps Tracks.', 'onlymulti'],
            [2, 2, ['Icons128x32_1', 'RT_Laps', 'QuitRace'], 'No Multilap Tracks', 'Display only Tracks that are not multilaps Tracks.', 'nomulti'],
            [3, 0, ['MedalsBig', 'MedalBronze', 'Close'], $s ? 'No Bronze Score' : 'No Bronze Time', 'Display only Tracks where you did not beat the bronze time on.', 'nobronze'],
            [3, 1, ['Icons128x128_1', 'IconPlayersLadder', 'QuitRace'], 'No Finish', 'Display only Tracks that you did not have finished yet.', 'nofinish'],
            [3, 2, ['Icons128x128_1', 'ChallengeAuthor'], 'Select Authorname', 'Select an Authorname and display only Tracks from this author.', 'author'],
            [3, 3, ['Icons128x128_1', 'Load'], 'Current Jukebox', 'Display only Tracks, that are in the jukebox to get played.', 'jukebox'],
            [3, 4, ['Icons128x128_1', 'Browse'], 'All Tracks', 'Display all Tracks, that are currently available on this Server.', 'all'],
        ];
    }

    private function sortCards(): array
    {
        return [
            [0, 0, ['Icons128x128_1', 'Browse'], 'All Tracks', 'Display all Tracks, that are currently available on this Server.', 'all'],
            [0, 1, ['Icons128x128_1', 'Rankings'], 'Best Ranked Tracks', "Sort Tracks by Rank,\nfrom best to worst.", 'best'],
            [0, 2, ['Icons128x128_1', 'Rankings'], 'Worst Ranked Tracks', "Sort Tracks by Rank,\nfrom worst to best.", 'worst'],
            [1, 0, ['Icons128x128_1', 'NewTrack'], 'Sort by Trackname', 'Sort all currently available Tracks by Trackname.', 'track'],
            [1, 1, ['Icons128x128_1', 'Race'], 'Shortest Tracks', "Sort Tracks by Authortime,\nfrom shortest to longest.", 'shortest'],
            [1, 2, ['Icons128x128_1', 'Race'], 'Longest Tracks', "Sort Tracks by Authortime,\nfrom longest to shortest.", 'longest'],
            [2, 0, ['Icons128x128_1', 'ChallengeAuthor'], 'Sort by Authorname', 'Sort all currently available Tracks by Authorname.', 'sortauthor'],
            [2, 1, ['Icons128x128_1', 'LoadTrack'], 'Newest Tracks First', "Sort Tracks by age,\nfrom newest to oldest.", 'newest'],
            [2, 2, ['Icons128x128_1', 'LoadTrack'], 'Oldest Tracks First', "Sort Tracks by age,\nfrom oldest to newest.", 'oldest'],
            [3, 1, ['Icons128x128_1', 'Challenge'], 'Karma Best Tracks', "Sort Tracks by Karma,\nfrom best to worst.", 'bestkarma'],
            [3, 2, ['Icons128x128_1', 'Challenge'], 'Karma Worst Tracks', "Sort Tracks by Karma,\nfrom worst to best.", 'worstkarma'],
        ];
    }

    private function tools(): string
    {
        return Frame::tool('ToolTree', -6.6, 'tl_filter').Frame::tool('ToolRoot', -3.3, 'tl_sort').Frame::tool('ToolUp', 0, 'opt_all');
    }

    private function card(int $col, int $row, array $icon, string $title, string $text, string $option): string
    {
        $x = 19.05 * $col;
        $y = -9.45 * $row;
        $ok = Ui::image('widget_ok_normal');
        $focus = Ui::image('widget_ok_focus');
        $action = Ids::action('opt_'.$option);
        $button = $ok
            ? Ui::quad('14.15 -5.65 0.03', '4 4', 'image="'.Ui::esc($ok).'"'.($focus ? ' imagefocus="'.Ui::esc($focus).'"' : '').' action="'.$action.'"')
            : Ui::styled('14.65 -6.15 0.03', '3 3', 'Icons64x64_1', 'Add', 'action="'.$action.'"');

        return '<frame posn="'.$x.' '.$y.' 0"><format textsize="1" textcolor="FFFF"/>'
            .Ui::styled('0 0 0.02', '17.75 9.2', 'BgsPlayerCard', 'BgRacePlayerName')
            .Ui::styled('0.4 -0.36 0.04', '16.95 2', 'BgsPlayerCard', 'ProgressBar')
            .Ui::styled('0.8 -0.2 0.05', '2.2 2.2', $icon[0], $icon[1])
            .(isset($icon[2]) ? Ui::styled('1 -0.4 0.06', '2 2', 'Icons64x64_1', $icon[2]) : '')
            .Ui::label('3.2 -0.55 0.05', '17.3 0', $title, 'textsize="1"')
            .Ui::label('1 -2.7 0.04', '16 2', $text, 'scale="0.9" autonewline="1"')
            .$button.'</frame>';
    }

    private function show(Player $player, string $type, string $title, array $icon, string $content, string $buttons): void
    {
        WindowManager::put($player, ['type' => $type, 'page' => 1, 'pages' => 1]);
        Ui::sendTo($player, Frame::wrapMain(Frame::main($title, $icon, $content, $buttons)).Ui::clear('subwindow'));
    }

    public function filter(Player $player): void
    {
        if (WindowManager::refuseAtScore($player)) {
            return;
        }
        $present = $this->present();
        $xml = '';
        foreach ($this->filterCards() as $c) {
            $xml .= $this->card(...$c);
        }
        $xml .= $this->wide(0, 3, 'Track mood', ['Icons128x128_1', 'Manialink'], $this->moodImages($present['mood']))
            .$this->wide(0, 4, 'Track environment', ['Icons128x128_1', 'Advanced'], $this->envImages($present['env']));
        $this->show($player, 'filter', 'Filter options for Tracklist', ['Icons128x128_1', 'NewTrack'], $xml, $this->tools());
    }

    public function sort(Player $player): void
    {
        if (WindowManager::refuseAtScore($player)) {
            return;
        }
        $xml = '';
        foreach ($this->sortCards() as $c) {
            $xml .= $this->card(...$c);
        }
        $this->show($player, 'sort', 'Sort options for Tracklist', ['Icons128x128_1', 'NewTrack'], $xml, $this->tools());
    }

    /** @return array{mood: array<string,bool>, env: array<string,bool>} which moods/environments have tracks */
    private function present(): array
    {
        $mood = $env = [];
        foreach ((array) State::get('tracks', []) as $t) {
            $mood[strtolower($t['mood'])] = true;
            if ($k = Environments::key($t['env'])) {
                $env[$k] = true;
            }
        }

        return compact('mood', 'env');
    }

    private function wide(int $col, int $row, string $title, array $icon, string $inner): string
    {
        return '<frame posn="'.(19.05 * $col).' '.(-9.45 * $row - ($row - 3) * 0).' 0"><format textsize="1" textcolor="FFFF"/>'
            .Ui::styled('0 0 0.02', '55.85 9.2', 'BgsPlayerCard', 'BgRacePlayerName')
            .Ui::styled('0.4 -0.36 0.04', '55.05 2', 'BgsPlayerCard', 'ProgressBar')
            .Ui::styled('0.8 -0.2 0.05', '2.2 2.2', $icon[0], $icon[1])
            .Ui::label('3.2 -0.55 0.05', '', $title, 'textsize="1"')
            .$inner.'</frame>';
    }

    private function image(string $kind, string $key, string $state): ?string
    {
        return Ui::image($kind, $key, $state);
    }

    private function moodImages(array $present): string
    {
        $xml = '';
        foreach (array_keys(Environments::MOODS) as $i => $mood) {
            $x = [1.6, 15.5, 29.4, 43.3][$i];
            $on = isset($present[$mood]);
            $img = $this->image('mood', $mood, $on ? 'normal' : 'disabled');
            $focus = $on ? $this->image('mood', $mood, 'focus') : null;
            $xml .= $img
                ? Ui::quad("$x -3 0.05", '10.88 5.44', 'image="'.Ui::esc($img).'"'.($focus ? ' imagefocus="'.Ui::esc($focus).'"' : '').($on ? ' action="'.Ids::action('opt_mood_'.$mood).'"' : ''))
                : Ui::label("$x -3 0.05", '10.88 5.44', Environments::MOODS[$mood], 'textsize="1"'.($on ? ' action="'.Ids::action('opt_mood_'.$mood).'" focusareacolor1="FFF0" focusareacolor2="FFFF"' : ' textcolor="FFF5"'));
        }

        return $xml;
    }

    private function envImages(array $present): string
    {
        $xml = '';
        $height = ['stadium' => 3.53];
        foreach (Environments::FILTER as $key => [$x, $w, $lx]) {
            $on = isset($present[$key]);
            $opt = 'opt_env_'.($key === 'snow' ? 'alpine' : $key);
            $img = $this->image('environment', $key, $on ? 'enabled' : 'disabled');
            $focus = $on ? $this->image('environment', $key, 'focus') : null;
            $y = $key === 'stadium' ? -3.43 : -2.53;
            $h = $height[$key] ?? 4.86;
            if ($img) {
                $xml .= Ui::quad("$x $y 0.05", "$w $h", 'image="'.Ui::esc($img).'"'.($focus ? ' imagefocus="'.Ui::esc($focus).'"' : '').($on ? ' action="'.Ids::action($opt).'"' : ''));
            }
            $xml .= Ui::label("$lx -7.63 0.05", '10 2', Environments::NAMES[$key], 'scale="0.8"'.($on ? ' action="'.Ids::action($opt).'"' : ' textcolor="FFF5"'));
        }

        return $xml;
    }

    // ---- authors ----

    public function authors(Player $player, int $page = 1): void
    {
        if (WindowManager::refuseAtScore($player)) {
            return;
        }
        \Funseco\Widgets\Support\TrackIndex::load(function (array $rows) use ($player, $page) {
            $authors = array_values(array_unique(array_filter(array_column($rows, 'author'), 'strlen')));
            natcasesort($authors);
            $authors = array_slice(array_values($authors), 0, TrackIndex::LIMIT);
            $pages = max(1, (int) ceil(count($authors) / self::AUTHORS_PER_PAGE));
            $page = max(1, min($pages, $page));
            $slice = array_slice($authors, ($page - 1) * self::AUTHORS_PER_PAGE, self::AUTHORS_PER_PAGE);

            WindowManager::put($player, ['type' => 'authors', 'page' => $page, 'pages' => $pages, 'picks' => $slice]);
            $n = count($authors);
            $title = 'Select Trackauthor for filtering the Tracklist   |   Page '.$page.'/'.$pages.'   |   '.$n.' Author'.($n === 1 ? '' : 's');
            $xml = '<frame posn="0.5 -0.3 0">'.$this->pickList($slice).'</frame>';
            Ui::sendTo($player, Frame::wrapMain(Frame::main($title, ['Icons128x128_1', 'NewTrack'], $xml, $this->tools().Frame::paging($page, $pages))).Ui::clear('subwindow'));
        });
    }

    public function turnAuthors(Player $player, string $action): void
    {
        $w = WindowManager::get($player);
        if (($w['type'] ?? null) === 'authors') {
            $this->authors($player, Frame::turn($action, $w['page'], $w['pages']));
        }
    }

    /** Columns of 20 clickable entries (authors and timezone zones): spec 5.2 and 8.5. */
    public function pickList(array $labels): string
    {
        $xml = '';
        foreach ($labels as $i => $label) {
            $col = intdiv($i, 20);
            $line = $i % 20;
            $x = 19.05 * $col;
            $xml .= Ui::styled("$x ".-(2.2 * $line)." 0.02", '17 2.2', 'Bgs1InRace', 'BgIconBorder', 'action="'.Ids::action('pick_'.$i).'"')
                .Ui::label(($x + 1).' '.-(2.2 * $line + 0.3).' 0.03', '16.5 0', $label, 'textsize="1" scale="0.9" textcolor="05CF"');
        }

        return $xml;
    }

    public function pickAuthor(Player $player, int $index): ?string
    {
        $w = WindowManager::get($player);

        return ($w['type'] ?? null) === 'authors' ? ($w['picks'][$index] ?? null) : null;
    }
}
