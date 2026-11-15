<?php
namespace Funseco\Widgets\Tests;

use Illuminate\Support\Facades\Event;
use Funseco\Core\Models\Map;
use Funseco\Core\Models\Player;
use Funseco\Core\Servers\TmForever\Values\CheckpointCollection;
use Funseco\Core\Services\Chat\Facades\Chat;
use Funseco\Core\SharedKernel\Events\JukeboxDropRequested;
use Funseco\Core\SharedKernel\Events\JukeboxRequested;
use Funseco\Core\SharedKernel\Events\PlayerFinished;
use Funseco\Core\SharedKernel\FinishHistory;
use Funseco\Core\SharedKernel\JukeboxQueue;
use Funseco\Core\SharedKernel\KarmaStats;
use Funseco\Core\SharedKernel\RecordBook;
use Funseco\Widgets\ClickHandler;
use Funseco\Widgets\Listeners\Lifecycle;
use Funseco\Widgets\Models\PlayerStats;
use Funseco\Widgets\Panels as P;
use Funseco\Widgets\Support\Ids;
use Funseco\Widgets\Support\State;
use Funseco\Widgets\Support\TrackIndex;
use Funseco\Widgets\Windows as W;

class WindowsAndListsTest extends WidgetsTestCase
{
    private array $queue = [];
    private array $history = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(JukeboxQueue::class, new class($this->queue, $this->history) implements JukeboxQueue {
            public function __construct(private array &$q, private array &$h) {}
            public function upcoming(): array { return $this->q; }
            public function history(): array { return $this->h; }
        });
    }

    /** Server tracks and matching database maps. */
    private function tracks(int $n): array
    {
        $rows = [];
        for ($i = 1; $i <= $n; $i++) {
            $m = Map::factory()->create(['uid' => "t$i", 'name' => "Track $i", 'author' => $i % 2 ? 'zed' : 'amy', 'environment' => $i % 3 ? 'Stadium' : 'Island',
                'mood' => 'Day', 'author_time' => 20000 + $i * 1000, 'gold_time' => 25000 + $i * 1000, 'lap_race' => $i === 4 ? 1 : 0]);
            $rows[] = ['UId' => "t$i", 'Name' => "Track $i", 'FileName' => "Track$i.Challenge.gbx", 'Author' => $m->author, 'Environnement' => $m->environment];
        }
        $this->responses['GetChallengeList'] = $rows;

        return $rows;
    }

    /** Ranks / finished / best are given by track uid (t1 …) and mapped to database ids. */
    private function bindBook(array $ranks = [], array $finished = [], array $best = []): void
    {
        $id = fn ($uid) => (int) Map::where('uid', $uid)->value('id');
        $ranks = array_combine(array_map(fn ($u) => $id("t$u"), array_keys($ranks)), $ranks) ?: [];
        $finished = array_map(fn ($u) => $id("t$u"), $finished);
        $best = array_combine(array_map(fn ($u) => $id("t$u"), array_keys($best)), $best) ?: [];
        $this->app->instance(RecordBook::class, new class($ranks) implements RecordBook {
            public function __construct(private array $r) {}
            public function ranksFor(Player $player): array { return $this->r; }
            public function checkpointsFor(Player $player, Map $map): ?CheckpointCollection { return null; }
            public function checkpointsOfRank(Map $map, int $rank): ?CheckpointCollection { return null; }
            public function countOn(Map $map): int { return 0; }
            public function recordCounts(int $maxRank = PHP_INT_MAX): array { return []; }
            public function topRanks(array $mapIds, int $limit): array { return []; }
        });
        $this->app->instance(FinishHistory::class, new class($finished, $best) implements FinishHistory {
            public function __construct(private array $f, private array $b) {}
            public function finishedMapIds(Player $player): array { return $this->f; }
            public function bestScores(Player $player): array { return $this->b; }
            public function finishCount(Player $player, Map $map): int { return 0; }
            public function averageScore(Player $player, Map $map, int $last): ?int { return null; }
        });
    }

    // ---- clock and timezone ----

    public function test_clock_shows_time_abbreviation_and_beat(): void
    {
        $this->assertSame('000', P\ClockWidget::beat(strtotime('2026-01-01 23:00:00 UTC')), 'BMT midnight is 23:00 UTC');
        $this->assertSame('500', P\ClockWidget::beat(strtotime('2026-01-01 11:00:00 UTC')));

        $xml = app(P\ClockWidget::class)->render($this->alice);
        $this->assertStringContainsString('BEAT', $xml);
        $this->assertMatchesRegularExpression('/text="(CET|CEST)"/', $xml, 'default Europe/Berlin');
        $this->assertStringContainsString('posn="44.3 39.3 0"', $xml);
        $this->assertStringContainsString('action="'.Ids::action('open_timezone').'"', $xml);
    }

    public function test_clock_time_format_drops_seconds_and_swatch_letters(): void
    {
        config(['manialinks.clock_widget.timeformat' => 'H:i:s.u B']);
        $this->assertSame('H:i:. ', P\ClockWidget::format());
    }

    public function test_clock_at_score_is_not_clickable_and_moves(): void
    {
        State::setScore(true);
        $xml = app(P\ClockWidget::class)->render($this->alice);
        $this->assertStringContainsString('posn="41.05 33.2 0"', $xml);
        $this->assertStringNotContainsString('action=', $xml);
    }

    public function test_clock_sends_one_render_per_timezone(): void
    {
        PlayerStats::of($this->bob)->update(['timezone' => 'Tokyo|Asia/Tokyo']);
        app(P\ClockWidget::class)->showAll();
        $this->assertStringContainsString('JST', $this->xmlTo('bob'));
        $this->assertStringNotContainsString('JST', $this->xmlTo('alice'));
    }

    // ---- round score ----

    private function roundMode(int $mode): void
    {
        \Funseco\Core\Services\Cache\ServerInfo::setGameMode($mode);
        State::put('gameinfo', []);
    }

    public function test_round_score_orders_by_finish_time_and_shows_points(): void
    {
        $this->roundMode(0);
        $w = app(P\RoundScoreWidget::class);
        $w->reset();
        $w->finished($this->bob, 31000);
        $w->finished($this->alice, 30000);
        $w->finished($this->alice, 20000);   // only the first finish of a round counts

        $this->assertSame(['alice', 'bob'], array_column($w->ordered(), 'login'));
        $xml = $w->render();
        $this->assertStringContainsString('posn="49.2 17.8 0"', $xml);
        $this->assertStringContainsString('0:30.00', $xml);
        $this->assertStringContainsString('$O+10', $xml);
        $this->assertStringContainsString('$O+6', $xml);
        $this->assertStringContainsString('sizen="15.5 28.5"', $xml, '1.8 * 14 + 3.3');
        $this->assertStringContainsString('Round Score', $xml);
    }

    public function test_round_score_ties_go_by_best_time_then_join_order(): void
    {
        $this->roundMode(0);
        $w = app(P\RoundScoreWidget::class);
        State::put('rs.best.bob', 29000);
        State::put('rs.best.alice', 30000);
        State::put('rs.rows', ['alice' => ['nick' => 'A', 'time' => 31000, 'cp' => 0], 'bob' => ['nick' => 'B', 'time' => 31000, 'cp' => 0]]);
        $this->assertSame(['bob', 'alice'], array_column($w->ordered(), 'login'));
    }

    public function test_round_score_points_use_the_rounds_points_contract_and_last_value(): void
    {
        $this->roundMode(0);
        $this->app->instance(\Funseco\Core\SharedKernel\RoundsPoints::class, new class implements \Funseco\Core\SharedKernel\RoundsPoints {
            public function points(): ?array { return [5, 3]; }
        });
        $this->assertSame([5, 3, 3], app(P\RoundScoreWidget::class)->pointsFor([[], [], []]));
        State::put('gameinfo', ['RoundsUseNewRules' => true]);
        $this->assertSame([1, 0, 0], app(P\RoundScoreWidget::class)->pointsFor([[], [], []]));
    }

    public function test_round_score_empty_states_and_off_in_time_attack(): void
    {
        $this->roundMode(1);
        $this->assertTrue($this->cleared('round_score', app(P\RoundScoreWidget::class)->render()));
        $this->roundMode(0);
        $this->assertStringContainsString('Free For You!', app(P\RoundScoreWidget::class)->render());
        State::put('warmup', true);
        $xml = app(P\RoundScoreWidget::class)->render();
        $this->assertStringContainsString('No Score during Warm-Up!', $xml);
        $this->assertStringContainsString('posn="49.2 10.7 0"', $xml, 'warm-up has its own position');
    }

    public function test_round_score_updates_on_finish_and_resets_at_round_start(): void
    {
        $this->roundMode(0);
        app(Lifecycle::class)->roundStarted();
        $this->forgetSent();
        app(Lifecycle::class)->finished(new PlayerFinished($this->alice, $this->map, 30000, new CheckpointCollection));
        $this->assertStringContainsString('0:30.00', $this->xml());
        $this->forgetSent();
        app(Lifecycle::class)->roundStarted();
        $this->assertStringNotContainsString('0:30.00', $this->xml());
    }

    public function test_round_points_are_banked_and_stored_at_score(): void
    {
        $this->roundMode(0);
        $w = app(P\RoundScoreWidget::class);
        $w->finished($this->alice, 30000);
        $w->finished($this->bob, 31000);
        $w->bankRound();
        $w->reset();
        $w->finished($this->bob, 29000);
        $w->bankRound();
        $w->flushPoints();
        $this->assertSame(6 + 10, PlayerStats::of($this->bob)->round_points);
        $this->assertSame(10, PlayerStats::of($this->alice)->round_points);
    }

    public function test_round_score_in_laps_orders_by_checkpoint(): void
    {
        $this->roundMode(3);
        $w = app(P\RoundScoreWidget::class);
        $w->reset();
        $w->checkpoint($this->alice, 2, 20000);
        $w->checkpoint($this->bob, 3, 25000);
        $this->assertSame(['bob', 'alice'], array_column($w->ordered(), 'login'));
        $xml = $w->render();
        $this->assertStringContainsString('$O+', $xml);
        $this->assertStringContainsString('D02F', $xml, 'behind the leader');
    }

    // ---- track list ----

    public function test_elist_shows_the_cards_sorted_by_author(): void
    {
        $this->tracks(7);
        $this->bindBook([1 => 3]);
        $this->forgetSent();
        Chat::resolve('/elist', 'alice');
        $xml = $this->xmlTo('alice');

        $this->assertStringContainsString('Tracks on this Server   |   Page 1/1   |   7 Tracks ', $xml);
        $this->assertStringContainsString('Track #1', $xml);
        $this->assertLessThan(strpos($xml, 'text="Track 1"'), strpos($xml, 'text="Track 2"'), 'author amy (Track 2) sorts before zed (Track 1)');
        $this->assertStringContainsString('by amy', $xml);
        $this->assertStringContainsString('L0', $xml);
        $this->assertStringContainsString('0:21.00', $xml);
    }

    public function test_tracklist_pages_of_twenty_and_paging_buttons(): void
    {
        $this->tracks(45);
        $this->bindBook();
        Chat::resolve('/elist', 'alice');
        $this->assertStringContainsString('Page 1/3   |   45 Tracks', $this->xmlTo('alice'));
        $this->assertStringNotContainsString('action="'.Ids::action('page_first').'"', $this->xmlTo('alice'), 'first page: no first/prev');

        $this->forgetSent();
        app(ClickHandler::class)->handle($this->alice, 'widgets_page_next5');
        $this->assertStringContainsString('Page 3/3', $this->xmlTo('alice'), '+5 stops at the last page');
        $this->assertStringContainsString('Track #41', $this->xmlTo('alice'));
        $this->forgetSent();
        app(ClickHandler::class)->handle($this->alice, 'widgets_page_prev5');
        $this->assertStringContainsString('Page 1/3', $this->xmlTo('alice'));
    }

    public function test_tracklist_singular_title(): void
    {
        $this->tracks(1);
        $this->bindBook();
        Chat::resolve('/elist', 'alice');
        $this->assertStringContainsString('1 Track ', $this->xmlTo('alice'));
        $this->assertStringNotContainsString('1 Tracks', $this->xmlTo('alice'));
    }

    public function test_tracklist_filters_and_search(): void
    {
        $this->tracks(6);
        $this->bindBook([1 => 4, 2 => 99, 3 => 2], [1, 2], [1 => 15000, 3 => 99999]);
        $cases = [
            'norank'     => ['(Filter: Not Ranked Tracks)', 4],    // 4,5,6 and rank 99 > max 50 (track 2)
            'onlyrank'   => ['(Filter: Only Ranked Tracks)', 2],
            'nofinish'   => ['(Filter: Not Finished Tracks)', 4],
            'onlymulti'  => ['(Filter: Only Multilap)', 1],
            'nomulti'    => ['(Filter: No Multilap)', 5],
            'noauthor'   => ['(Filter: No Author Time)', 1],       // track 3: best 99999 > author time
            'best'       => ['(Sorting: Best Player Rank)', 2],
            'shortest'   => ['(Sorting: Shortest Author Time)', 6],
            'amy'        => ['(Search: &#039;amy&#039;)', 3],
            'track 4'    => ['(Search: &#039;track 4&#039;)', 1],
        ];
        foreach ($cases as $arg => [$title, $count]) {
            $this->forgetSent();
            Chat::resolve('/elist '.$arg, 'alice');
            $xml = $this->xmlTo('alice');
            $this->assertStringContainsString($title, $xml, $arg);
            $this->assertStringContainsString('   |   '.$count.' Track', $xml, "$arg count");
            $this->assertStringNotContainsString('no Track match', $xml);
        }
    }

    public function test_tracklist_best_sorts_by_rank(): void
    {
        $this->tracks(3);
        $this->bindBook([1 => 9, 2 => 3, 3 => 5]);
        Chat::resolve('/elist best', 'alice');
        $xml = $this->xmlTo('alice');
        $this->assertLessThan(strpos($xml, 'text="Track 3"'), strpos($xml, 'text="Track 2"'));
        $this->assertLessThan(strpos($xml, 'text="Track 1"'), strpos($xml, 'text="Track 3"'));
    }

    public function test_empty_filter_shows_everything_and_a_notice(): void
    {
        $this->tracks(3);
        $this->bindBook();
        Chat::resolve('/elist onlyrank', 'alice');
        $xml = $this->xmlTo('alice');
        $this->assertStringContainsString('   |   3 Tracks', $xml);
        $this->assertStringContainsString('This filter return an empty result', $xml);
        $this->assertStringContainsString('Notice', $xml);
    }

    public function test_recent_and_jukebox_filters_use_the_jukebox_contract(): void
    {
        $this->tracks(4);
        $this->bindBook();
        $this->history = ['t3', 't1'];
        $this->queue = [['uid' => 't2', 'login' => 'bob']];
        $this->app->instance(JukeboxQueue::class, new class($this->queue, $this->history) implements JukeboxQueue {
            public function __construct(private array $q, private array $h) {}
            public function upcoming(): array { return $this->q; }
            public function history(): array { return $this->h; }
        });
        Chat::resolve('/elist onlyrecent', 'alice');
        $xml = $this->xmlTo('alice');
        $this->assertLessThan(strpos($xml, 'text="Track 1"'), strpos($xml, 'text="Track 3"'), 'most recent first');
        $this->assertStringContainsString('   |   2 Tracks', $xml);
        $this->forgetSent();
        Chat::resolve('/elist norecent', 'alice');
        $this->assertStringContainsString('   |   2 Tracks', $this->xmlTo('alice'));
        $this->forgetSent();
        Chat::resolve('/elist jukebox', 'alice');
        $this->assertStringContainsString('   |   1 Track ', $this->xmlTo('alice'));
        $this->assertStringContainsString('Bgs1InRace', $this->xmlTo('alice'));
        $this->assertStringContainsString('BgListLine', $this->xmlTo('alice'), 'juked cards have the juke header');
    }

    public function test_karma_sort_and_value(): void
    {
        $this->tracks(2);
        $this->bindBook();
        $first = Map::where('uid', 't1')->first();
        $second = Map::where('uid', 't2')->first();
        $this->app->instance(KarmaStats::class, new class([$first->id => [3 => 2, -3 => 2], $second->id => [3 => 1]]) implements KarmaStats {
            public function __construct(private array $votes) {}
            public function karmaByMap(): array { return []; }
            public function mapKarma(Map $map): ?int { return null; }
            public function votesByMap(): array { return $this->votes; }
            public function votedMapIds(Player $player): array { return []; }
            public function voteCounts(int $limit): array { return []; }
        });
        $this->assertSame([$first->id => 50, $second->id => 100], TrackIndex::karma());
        config(['manialinks.features.karma.calculation_method' => 'rasp']);
        $this->assertSame([$first->id => 0, $second->id => 3], TrackIndex::karma());
        Chat::resolve('/elist bestkarma', 'alice');
        $xml = $this->xmlTo('alice');
        $this->assertLessThan(strpos($xml, 'text="Track 1"'), strpos($xml, 'text="Track 2"'));
    }

    public function test_rank_text_is_padded_or_none(): void
    {
        $this->assertSame('07.', TrackIndex::rankText(7));
        $this->assertSame('$ZNone', TrackIndex::rankText(null));
        $this->assertSame('$ZNone', TrackIndex::rankText(51), 'above max records');
    }

    public function test_plus_button_asks_the_jukebox_and_minus_drops(): void
    {
        $this->tracks(3);
        $this->bindBook();
        Chat::resolve('/elist', 'alice');
        Event::fake([JukeboxRequested::class, JukeboxDropRequested::class]);
        app(ClickHandler::class)->handle($this->alice, 'widgets_card_0');
        Event::assertDispatched(JukeboxRequested::class, fn ($e) => $e->player->login === 'alice' && $e->track['uid'] === 't2');

        $this->queue[] = ['uid' => 't2', 'login' => 'alice'];
        $this->app->instance(JukeboxQueue::class, new class($this->queue) implements JukeboxQueue {
            public function __construct(private array $q) {}
            public function upcoming(): array { return $this->q; }
            public function history(): array { return []; }
        });
        app(ClickHandler::class)->handle($this->alice, 'widgets_card_0');
        Event::assertDispatched(JukeboxDropRequested::class, fn ($e) => $e->uid === 't2');
    }

    public function test_cards_show_buttons_by_state_and_ability(): void
    {
        $this->tracks(2);
        $this->bindBook();
        $this->history = ['t1'];
        $this->app->instance(JukeboxQueue::class, new class implements JukeboxQueue {
            public function upcoming(): array { return [['uid' => 't2', 'login' => 'bob']]; }
            public function history(): array { return ['t1']; }
        });
        Chat::resolve('/elist', 'alice');
        $xml = $this->xmlTo('alice');
        // t1: recently played, alice may not queue it; t2: juked by bob, alice may not drop it
        $this->assertStringNotContainsString('action="'.Ids::action('card_0').'"', $xml);
        $this->assertStringNotContainsString('action="'.Ids::action('card_1').'"', $xml);
        $this->assertStringContainsString('textcolor="FFF8"', $xml, 'recently played text is dimmed');

        config(['adminops.masteradmins' => ['alice']]);
        $this->forgetSent();
        Chat::resolve('/elist', 'alice');
        $this->assertStringContainsString('action="'.Ids::action('card_1').'"', $this->xmlTo('alice'), 'staff can drop');
    }

    public function test_clear_jukebox_button_only_for_the_ability(): void
    {
        $this->tracks(2);
        $this->bindBook();
        Chat::resolve('/elist', 'alice');
        $this->assertStringNotContainsString('action="'.Ids::action('tl_clear').'"', $this->xmlTo('alice'));
        config(['adminops.masteradmins' => ['alice']]);
        $this->forgetSent();
        Chat::resolve('/elist', 'alice');
        $this->assertStringContainsString('action="'.Ids::action('tl_clear').'"', $this->xmlTo('alice'));
        $this->forgetSent();
        app(ClickHandler::class)->handle($this->alice, 'widgets_tl_clear');
        $this->assertStringContainsString('Do you really want to drop the complete Jukebox?', $this->xmlTo('alice'));
        $this->assertStringContainsString('Yes', $this->xmlTo('alice'));
    }

    public function test_current_track_is_marked(): void
    {
        $this->tracks(1);
        $this->bindBook();
        \Funseco\Core\Services\Cache\CurrentMap::set(Map::where('uid', 't1')->first());
        Chat::resolve('/elist', 'alice');
        $this->assertStringContainsString('substyle="Fame"', $this->xmlTo('alice'));
    }

    public function test_filter_and_sort_windows(): void
    {
        $this->tracks(3);
        $this->bindBook();
        Chat::resolve('/elist', 'alice');
        $this->forgetSent();
        app(ClickHandler::class)->handle($this->alice, 'widgets_tl_filter');
        $xml = $this->xmlTo('alice');
        $this->assertStringContainsString('Filter options for Tracklist', $xml);
        $this->assertStringContainsString('No Author Time', $xml);
        $this->assertStringContainsString('Select Authorname', $xml);
        $this->assertStringContainsString('Track mood', $xml);
        $this->assertStringContainsString('Track environment', $xml);
        $this->assertStringContainsString('action="'.Ids::action('opt_env_island').'"', $xml, 'islands exist');
        $this->assertStringNotContainsString('action="'.Ids::action('opt_env_bay').'"', $xml, 'no bay tracks: disabled');
        $this->assertStringContainsString('action="'.Ids::action('opt_mood_day').'"', $xml);

        $this->forgetSent();
        app(ClickHandler::class)->handle($this->alice, 'widgets_tl_sort');
        $xml = $this->xmlTo('alice');
        $this->assertStringContainsString('Sort options for Tracklist', $xml);
        $this->assertStringContainsString('Karma Worst Tracks', $xml);
        $this->assertStringNotContainsString('>xxx<', $xml);

        $this->forgetSent();
        app(ClickHandler::class)->handle($this->alice, 'widgets_opt_env_island');
        $this->assertStringContainsString('(Filter: Only env. Island)', $this->xmlTo('alice'));
        $this->forgetSent();
        app(ClickHandler::class)->handle($this->alice, 'widgets_opt_mood_day');
        $this->assertStringContainsString('(Filter: Only mood Day)', $this->xmlTo('alice'));
    }

    public function test_author_list_and_author_filter(): void
    {
        $this->tracks(5);
        $this->bindBook();
        Chat::resolve('/elist author', 'alice');
        $xml = $this->xmlTo('alice');
        $this->assertStringContainsString('2 Authors', $xml);
        $this->assertStringContainsString('Page 1/1', $xml);
        $this->assertStringContainsString('text="amy"', $xml);

        $this->forgetSent();
        app(ClickHandler::class)->handle($this->alice, 'widgets_pick_1');   // zed (amy, zed)
        $this->assertStringContainsString('(Filter: Only Tracks by zed)', $this->xmlTo('alice'));
        $this->assertStringContainsString('   |   3 Tracks', $this->xmlTo('alice'));
    }

    public function test_author_pages_use_ceiling(): void
    {
        $rows = [];
        for ($i = 1; $i <= 81; $i++) {
            $rows[] = ['UId' => "t$i", 'Name' => "T$i", 'FileName' => "$i.gbx", 'Author' => "author$i", 'Environnement' => 'Stadium'];
        }
        $this->responses['GetChallengeList'] = $rows;
        $this->bindBook();
        Chat::resolve('/elist author', 'alice');
        $this->assertStringContainsString('Page 1/2   |   81 Authors', $this->xmlTo('alice'));
    }

    public function test_windows_refused_at_score(): void
    {
        State::setScore(true);
        Chat::resolve('/elist', 'alice');
        $this->assertSame('', $this->xmlTo('alice'));
        $this->assertStringContainsString('Could not display this window at score!', end($this->calls)[1][0] ?? $this->messages()[0]);
    }

    public function test_loading_window_for_large_lists_on_first_open(): void
    {
        $this->tracks(5);
        $this->bindBook();
        config(['manialinks.show_progress_indicator.tracklist' => 3]);
        Chat::resolve('/elist', 'alice');
        \React\EventLoop\Loop::futureTick(fn () => \React\EventLoop\Loop::stop());
        \React\EventLoop\Loop::run();
        $this->assertStringContainsString('Loading... please wait.', $this->xmlTo('alice'));
    }

    // ---- other windows ----

    public function test_track_overview_shows_last_current_and_next(): void
    {
        $this->app->instance(\Funseco\Core\SharedKernel\TrackExchange::class, new \Funseco\Core\Services\Defaults\NullTrackExchange);
        $this->responses['GetNextChallengeInfo'] = ['UId' => 'n1', 'Name' => 'Coming', 'Author' => 'carl', 'Environnement' => 'Rally', 'Mood' => 'Night',
            'AuthorTime' => 50000, 'GoldTime' => 55000, 'SilverTime' => 60000, 'BronzeTime' => 70000];
        State::put('last', ['uid' => 'l1', 'name' => 'Old Track', 'author' => 'dan', 'env' => 'Bay', 'mood' => 'Day', 'authorTime' => 44000, 'gold' => 45000, 'silver' => 46000, 'bronze' => 47000, 'file' => '']);
        State::put('current_card', \Funseco\Widgets\Support\TrackCard::fromMap($this->map)->toArray());
        $this->forgetSent();
        app(ClickHandler::class)->handle($this->alice, 'widgets_open_overview');
        $xml = $this->xmlTo('alice');
        foreach (['Track overview', 'Last Track', 'Current Track', 'Next Track', 'Old Track', 'Coming', 'by nadeo', '0:44.00', '0:31.00', '0:50.00', 'Press DEL if can not see an Image here!'] as $text) {
            $this->assertStringContainsString($text, $xml, $text);
        }
        $this->assertStringNotContainsString('Visit Track Page', $xml, 'no TMX block without TMX');
    }

    public function test_track_overview_first_map_after_startup_has_unknown_last_track(): void
    {
        $this->app->instance(\Funseco\Core\SharedKernel\TrackExchange::class, new \Funseco\Core\Services\Defaults\NullTrackExchange);
        $this->responses['GetNextChallengeInfo'] = ['UId' => 'n1', 'Name' => 'Coming'];
        app(ClickHandler::class)->handle($this->alice, 'widgets_open_overview');
        $this->assertStringContainsString('text="unknown"', $this->xmlTo('alice'));
    }

    public function test_tmx_info_window(): void
    {
        $track = new \Funseco\Core\SharedKernel\Values\TmxTrack(7, 'uid-1', 'Fast One', 'nadeo', '2010-01-01T00:00:00', '2011-02-02T00:00:00', 'Race', 'Fullspeed', 'Stadium',
            'Day', 'Easy', 'Single', 'Short', 3, '', 'TMNF', 'Visit http://x.org now[b]<br>bye', 31000, 'http://t/7', 'http://t/7.jpg', 'http://t/dl', 'http://t/rp',
            [new \Funseco\Core\SharedKernel\Values\TmxRecord(1, 1, 'Zed', 29500, 0, null, '2009-05-05T00:00:00', null)]);
        $this->app->instance(\Funseco\Core\SharedKernel\TrackExchange::class, new FakeExchange($track));
        State::put('current_card', \Funseco\Widgets\Support\TrackCard::fromMap($this->map)->toArray());
        app(ClickHandler::class)->handle($this->alice, 'widgets_open_tmx');
        $xml = $this->xmlTo('alice');
        foreach (['TMX Track Info', 'Fast One', 'from 2011-02-02', 'TMX-ID: 7', 'TMX Top10 Records', 'Zed', '0:29.50', ' $F00$W!', '» Download Replay', '$L[http://x.org]http://x.org$L'] as $text) {
            $this->assertStringContainsString($text, $xml, $text);
        }
        $this->assertStringNotContainsString('[b]', $xml);
    }

    public function test_tmx_info_window_needs_a_tmx_track(): void
    {
        $this->app->instance(\Funseco\Core\SharedKernel\TrackExchange::class, new \Funseco\Core\Services\Defaults\NullTrackExchange);
        app(ClickHandler::class)->handle($this->alice, 'widgets_open_tmx');
        $this->assertSame('', $this->xmlTo('alice'));
    }

    public function test_nations_window_counts_players_and_fixes_flags(): void
    {
        Player::factory()->create(['login' => 'c', 'nation' => 'GER']);
        Player::factory()->create(['login' => 'd', 'nation' => 'SCG']);
        Player::factory()->create(['login' => 'e', 'nation' => 'OTH']);
        app(ClickHandler::class)->handle($this->alice, 'widgets_open_nations');
        $xml = $this->xmlTo('alice');
        $this->assertStringContainsString('Top Nations', $xml);
        $this->assertStringContainsString('Germany', $xml);
        $this->assertStringContainsString('Flags/SRB.dds', $xml);
        $this->assertStringContainsString('Flags/other.dds', $xml);
        $this->assertStringContainsString('Flags/GER.dds', $xml);
        $this->assertSame(2, array_values(\Funseco\Widgets\Windows\NationsWindow::counts())[0]);
    }

    public function test_visitors_widget_counts_the_highest_player_id(): void
    {
        P\VisitorsWidget::refresh();
        $this->assertStringContainsString('VISITORS', app(P\VisitorsWidget::class)->render());
        $this->assertSame((int) Player::max('id'), State::get('visitors'));
    }

    public function test_welcome_window_is_off_by_default_and_autohides_when_on(): void
    {
        app(W\WelcomeWindow::class)->show($this->alice);
        $this->assertSame('', $this->xmlTo('alice'));

        config(['manialinks.welcome_window.enabled' => 'true', 'manialinks.welcome_window.autohide' => '0',
            'manialinks.welcome_window.image.normal' => '', 'manialinks.welcome_window.message' => 'Hi {player}{br}at {server}']);
        app(W\WelcomeWindow::class)->show($this->alice);
        $xml = $this->xmlTo('alice');
        $this->assertStringContainsString('Welcome to My Server!', $xml);
        $this->assertStringContainsString('Hi $f00Alice$Z', $xml);
        $this->assertStringContainsString('sizen="72.4 0"', $xml, 'no image: the text is wider');
    }

    public function test_window_close_buttons(): void
    {
        app(W\HelpWindow::class)->open($this->alice);
        $this->forgetSent();
        app(ClickHandler::class)->handle($this->alice, 'widgets_window_close');
        $xml = $this->xmlTo('alice');
        $this->assertTrue($this->cleared('window', $xml));
        $this->assertTrue($this->cleared('subwindow', $xml));
        $this->assertNull(W\WindowManager::get($this->alice));
    }

    public function test_score_closes_open_windows_for_everyone(): void
    {
        app(W\HelpWindow::class)->open($this->alice);
        $this->forgetSent();
        W\WindowManager::closeAll();
        $this->assertTrue($this->cleared('window', $this->xml()));
        $this->assertNull(W\WindowManager::get($this->alice));
    }

    public function test_frame_has_branded_footer_and_close_button(): void
    {
        $xml = W\Frame::main('Title', ['Icons64x64_1', 'Close'], '');
        $this->assertStringContainsString('text="FUNSECO"', $xml);
        $this->assertStringNotContainsString('EYEPIECE', $xml);
        $this->assertStringContainsString('posn="-40.1 30.45 18.50"', $xml);
        $this->assertStringContainsString('action="'.Ids::action('window_close').'"', $xml);
        $this->assertStringContainsString('posn="-19.8 15 21.5"', W\Frame::sub('x', ['a', 'b'], ''));
    }

    public function test_paging_buttons_disable_at_the_ends(): void
    {
        $first = W\Frame::paging(1, 3);
        $this->assertStringNotContainsString('action="'.Ids::action('page_first').'"', $first);
        $this->assertStringContainsString('action="'.Ids::action('page_next').'"', $first);
        $this->assertSame(2, substr_count($first, 'StarGold') / 3);
        $this->assertSame(1, W\Frame::turn('page_prev5', 3, 5));
        $this->assertSame(5, W\Frame::turn('page_next5', 3, 5));
    }
}
