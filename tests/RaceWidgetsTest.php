<?php
namespace Funseco\Widgets\Tests;

use Funseco\Core\Models\Map;
use Funseco\Core\Servers\TmForever\Values\ChallengeInfo;
use Funseco\Core\Servers\TmForever\Values\PlayerInfo;
use Funseco\Core\Servers\TmForever\Values\PlayerRankingsCollection;
use Funseco\Core\Services\Cache\CurrentSpectators;
use Funseco\Core\SharedKernel\Events\MapEnded;
use Funseco\Core\SharedKernel\Events\MapStarted;
use Funseco\Core\SharedKernel\Events\PlayerCheckpointPassed;
use Funseco\Core\SharedKernel\Events\PlayerRetired;
use Funseco\Core\SharedKernel\Events\RoundStarted;
use Funseco\Widgets\Listeners\Lifecycle;
use Funseco\Widgets\Panels as P;
use Funseco\Widgets\Support\Format;
use Funseco\Widgets\Support\Gamemodes;
use Funseco\Widgets\Support\State;
use Funseco\Widgets\Support\TrackCard;

class RaceWidgetsTest extends WidgetsTestCase
{
    private function gameInfo(array $info): void
    {
        $this->responses['GetCurrentGameInfo'] = $info + ['GameMode' => 1, 'NbChallenge' => 1234];
    }

    private function mapStart(bool $warmup = false): void
    {
        app(Lifecycle::class)->mapStarted(new MapStarted($this->map, $warmup));
    }

    public function test_map_start_sends_every_race_widget_to_everybody(): void
    {
        $this->gameInfo(['TimeAttackLimit' => 300000]);
        $this->responses['GetLadderServerLimits'] = ['LadderServerLimitMin' => 50000, 'LadderServerLimitMax' => 100000];
        config(['manialinks.eyepiece_widget.race.enabled' => 'true']);
        $this->mapStart();
        $xml = $this->xml();

        foreach (['gamemode', 'players', 'trackcount', 'visitors', 'challenge', 'logo', 'cpcount'] as $name) {
            $this->assertTrue($this->shown($name, $xml), "$name not shown");
        }
        $this->assertStringContainsString('TIME ATTACK', $xml);
        $this->assertStringContainsString('5:00', $xml);
        $this->assertStringContainsString('1,234', $xml, 'track count is number formatted');
        $this->assertStringContainsString('Current Track', $xml);
        $this->assertStringContainsString('by nadeo', $xml);
        $this->assertStringContainsString('0:31.00', $xml, 'author time');
        $this->assertStringContainsString('posn="49.05 48 0"', $xml, 'owner position, pinned');
    }

    public function test_gamemode_limits_per_mode(): void
    {
        $this->assertSame('15 pts.', Gamemodes::limit(0, ['RoundsPointsLimit' => 15]));
        $this->assertSame('10 pts.', Gamemodes::limit(0, ['RoundsUseNewRules' => true, 'RoundsPointsLimitNewRules' => 10, 'RoundsPointsLimit' => 15]));
        $this->assertSame('5:00', Gamemodes::limit(1, ['TimeAttackLimit' => 300000]));
        $this->assertSame('20 pts.', Gamemodes::limit(2, ['TeamPointsLimit' => 20]));
        $this->assertSame('3 laps', Gamemodes::limit(3, ['LapsNbLaps' => 3]));
        $this->assertSame('6:00 min.', Gamemodes::limit(3, ['LapsTimeLimit' => 360000, 'LapsNbLaps' => 3]));
        $this->assertSame('100 pts.', Gamemodes::limit(5, ['CupPointsLimit' => 100]));
        $this->assertNull(Gamemodes::limit(4, []));
    }

    public function test_ladder_limit_text(): void
    {
        $this->responses['GetLadderServerLimits'] = ['LadderServerLimitMin' => 50000, 'LadderServerLimitMax' => 100000];
        Gamemodes::loadLadder();
        $xml = app(P\LadderLimitWidget::class)->render();
        $this->assertStringContainsString('50-100k', $xml);
        $this->assertStringContainsString('LADDER', $xml);
        $this->assertStringContainsString('posn="-64 40.3 0"', $xml);
    }

    public function test_player_spectator_counts_and_full_colour(): void
    {
        CurrentSpectators::add($this->bob);
        ServerInfo_set(1, 10);
        $xml = app(P\PlayerSpectatorWidget::class)->render();
        $this->assertStringContainsString('text="1/1"', $xml);
        $this->assertStringContainsString('textcolor="F00"', $xml, 'players at the maximum are red');
        $this->assertStringContainsString('text="1/10"', $xml);
    }

    public function test_checkpoint_count_text(): void
    {
        // 4 checkpoints incl. finish => 3 shown
        $this->assertSame('$O1 $Zof$O 3', P\CheckpointCountWidget::lines(0, false)[1]);
        $this->assertSame('$OWarm-Up', P\CheckpointCountWidget::lines(0, true)[1]);
        $this->assertSame('ALL CHECKPOINTS REACHED', P\CheckpointCountWidget::lines(2, false)[0]);
        $this->assertSame('Finish now!', P\CheckpointCountWidget::lines(2, false)[1]);
        $this->assertSame('TRACK SUCCESSFULLY', P\CheckpointCountWidget::lines(3, false)[0]);
    }

    public function test_checkpoint_count_without_checkpoints(): void
    {
        $this->map->update(['cp_count' => 1]);
        $this->assertSame('WITHOUT CHECKPOINTS', P\CheckpointCountWidget::lines(-1, false)[0]);
    }

    public function test_checkpoint_total_for_laps_and_multilap(): void
    {
        $this->map->update(['cp_count' => 5, 'lap_race' => 1, 'lap_count' => 3]);
        ServerInfo_mode(0);
        $this->assertSame(15, P\CheckpointCountWidget::total());
        ServerInfo_mode(3);
        State::put('gameinfo', ['LapsNbLaps' => 2]);
        $this->assertSame(10, P\CheckpointCountWidget::total());
        ServerInfo_mode(1);
        $this->assertSame(5, P\CheckpointCountWidget::total());
    }

    public function test_checkpoint_updates_the_driver_only_and_retire_resets(): void
    {
        $this->mapStart();
        $this->forgetSent();
        app(Lifecycle::class)->checkpoint(new PlayerCheckpointPassed($this->alice, 0, 12000, 0));
        $this->assertStringContainsString('$O1 $Zof$O 3', $this->xmlTo('alice'));
        $this->assertSame('', $this->xmlTo('bob'));

        $this->forgetSent();
        app(Lifecycle::class)->retired(new PlayerRetired($this->alice));
        $this->assertStringContainsString('$O0 $Zof$O 3', $this->xmlTo('alice'));
    }

    public function test_checkpoint_count_never_sent_in_nice_mode(): void
    {
        State::setNice(true);
        $this->assertStringContainsString('<manialink id="'.\Funseco\Widgets\Support\Ids::ml('cpcount').'"></manialink>', app(P\CheckpointCountWidget::class)->render($this->alice));
    }

    public function test_spectator_switch_hides_then_resets_the_checkpoint_count(): void
    {
        $this->mapStart();
        $this->forgetSent();
        $spec = new PlayerInfo(['Login' => 'alice', 'NickName' => 'A', 'SpectatorStatus' => 1]);
        app(Lifecycle::class)->playerInfoUpdated(new \Funseco\Core\SharedKernel\Events\PlayerInfoUpdated($this->alice, $spec, false));
        $this->assertTrue($this->cleared('cpcount', $this->xmlTo('alice')));

        $this->forgetSent();
        $play = new PlayerInfo(['Login' => 'alice', 'NickName' => 'A', 'SpectatorStatus' => 0]);
        app(Lifecycle::class)->playerInfoUpdated(new \Funseco\Core\SharedKernel\Events\PlayerInfoUpdated($this->alice, $play, true));
        $this->assertTrue($this->shown('cpcount', $this->xmlTo('alice')));
    }

    public function test_current_ranking_lines(): void
    {
        $w = app(P\CurrentRankingWidget::class);
        $this->assertSame(['0/2', 'RANKING'], $w->lineFor($this->alice), 'map start: 0/connected');

        State::put('ranking', [
            ['Login' => 'bob', 'Rank' => 1, 'BestTime' => 30000, 'Score' => 0],
            ['Login' => 'alice', 'Rank' => 2, 'BestTime' => 0, 'Score' => 0],
        ]);
        $this->assertSame(['0/2', 'RANKING'], $w->lineFor($this->alice), 'no time yet: rank 0');
        $this->assertSame(['1/2', 'RANKING'], $w->lineFor($this->bob));
        $this->assertNull($w->lineFor(\Funseco\Core\Models\Player::factory()->create(['login' => 'carol'])), 'not listed: no update');
    }

    public function test_current_ranking_team_mode(): void
    {
        ServerInfo_mode(2);
        $w = app(P\CurrentRankingWidget::class);
        State::put('ranking', [['Score' => 0], ['Score' => 0]]);
        $this->assertSame(['---', 'FIRST'], $w->lineFor($this->alice));
        State::put('ranking', [['Score' => 3], ['Score' => 5]]);
        $this->assertSame(['RED', 'FIRST'], $w->lineFor($this->alice));
        State::put('ranking', [['Score' => 4], ['Score' => 4]]);
        $this->assertSame(['TEAM', 'DRAW'], $w->lineFor($this->alice));
    }

    public function test_current_ranking_team_mode_reads_the_entries_by_team_id(): void
    {
        ServerInfo_mode(2);
        $w = app(P\CurrentRankingWidget::class);
        // red listed first, and a player row in between: the team id (PlayerId 0 blue, 1 red) decides
        State::put('ranking', [
            ['Login' => '', 'PlayerId' => 1, 'Score' => 9],
            ['Login' => 'bob', 'PlayerId' => 7, 'Score' => 100],
            ['Login' => '', 'PlayerId' => 0, 'Score' => 3],
        ]);
        $this->assertSame(['RED', 'FIRST'], $w->lineFor($this->alice));
        State::put('ranking', [['Login' => '', 'PlayerId' => 1, 'Score' => 2], ['Login' => '', 'PlayerId' => 0, 'Score' => 3]]);
        $this->assertSame(['BLUE', 'FIRST'], $w->lineFor($this->alice));
    }

    public function test_current_ranking_refresh_sends_each_player_their_line(): void
    {
        $this->responses['GetCurrentRanking'] = [
            ['Login' => 'bob', 'Rank' => 1, 'BestTime' => 30000, 'Score' => 0],
            ['Login' => 'alice', 'Rank' => 2, 'BestTime' => 31000, 'Score' => 0],
        ];
        app(P\CurrentRankingWidget::class)->refresh();
        $this->assertStringContainsString('text="2/2"', $this->xmlTo('alice'));
        $this->assertStringContainsString('text="1/2"', $this->xmlTo('bob'));
    }

    public function test_score_hides_race_widgets_and_shows_score_widgets(): void
    {
        $this->mapStart();
        $this->forgetSent();
        $this->responses['GetNextChallengeInfo'] = ['UId' => 'uid-2', 'Name' => 'Next One', 'Author' => 'bob', 'Environnement' => 'Island',
            'Mood' => 'Night', 'AuthorTime' => 41000, 'GoldTime' => 43000, 'SilverTime' => 46000, 'BronzeTime' => 50000];
        $this->responses['GetNextGameInfo'] = ['GameMode' => 0];
        $ended = new MapEnded($this->map, new ChallengeInfo(['uid-1', 'n', 'f', 'a', 'Stadium', 'Day', 1, 1, 1, 1, 0, false, 1, 4]), new PlayerRankingsCollection, false, false);
        app(Lifecycle::class)->mapEnded($ended);
        $xml = $this->xml();

        foreach (['gamemode', 'players', 'trackcount', 'visitors', 'cpcount', 'ranking', 'round_score'] as $name) {
            $this->assertTrue($this->cleared($name, $xml), "$name not hidden at score");
        }
        $this->assertStringContainsString('Next Track', $xml);
        $this->assertStringContainsString('Next One', $xml);
        $this->assertStringContainsString('1:00', Format::short(60000));
        foreach (['next_env', 'next_mode', 'clock', 'logo'] as $name) {
            $this->assertTrue($this->shown($name, $xml), "$name not shown at score");
        }
        $this->assertStringContainsString('UPCOMING', $xml);
        $this->assertStringContainsString('posn="49.1 -40.9 0"', $xml);
        $this->assertStringContainsString('posn="53.9 -40.9 0"', $xml);
        $this->assertStringContainsString('RT_Rounds', $xml, 'the next gamemode icon');
        $this->assertStringNotContainsString('Current Track', preg_replace('#<manialink id="'.\Funseco\Widgets\Support\Ids::ml('challenge').'">.*?</manialink>#s', '', $xml) ?: '');

        $this->forgetSent();
        $this->mapStart();
        $this->assertTrue($this->cleared('next_env'), 'score widgets closed at the next map start');
        $this->assertTrue($this->shown('challenge'));
    }

    public function test_next_track_comes_from_the_jukebox_first(): void
    {
        $next = Map::factory()->create(['uid' => 'uid-9', 'name' => 'Juked', 'author' => 'carol', 'environment' => 'Bay', 'mood' => 'Sunset']);
        $this->app->instance(\Funseco\Core\SharedKernel\JukeboxQueue::class, new class implements \Funseco\Core\SharedKernel\JukeboxQueue {
            public function upcoming(): array { return [['uid' => 'uid-9', 'login' => 'bob']]; }
            public function history(): array { return []; }
        });
        \Funseco\Widgets\Support\NextTrack::load();
        $this->assertSame('Juked', TrackCard::fromArray(State::get('next'))->name);
        $this->assertSame([], $this->sent('GetNextChallengeInfo'));
    }

    public function test_round_start_resets_round_score_and_checkpoints(): void
    {
        $this->mapStart();
        $this->forgetSent();
        app(Lifecycle::class)->roundStarted();
        $this->assertStringContainsString('$O0 $Zof$O 3', $this->xmlTo('alice'));
    }

    public function test_next_environment_and_mode_only_at_score(): void
    {
        $this->assertTrue($this->cleared('next_env', app(P\NextEnvironmentWidget::class)->render()));
        State::setScore(true);
        State::put('next', TrackCard::unknown()->toArray());
        $this->assertStringContainsString('ENVIRONMENT', app(P\NextEnvironmentWidget::class)->render());
    }

    public function test_nice_mode_thresholds(): void
    {
        for ($i = 0; $i < 22; $i++) {
            CurrentPlayersAddFake($i);   // 24 with alice and bob
        }
        State::updateNice();
        $this->assertFalse(State::nice(), '25 needed');
        CurrentPlayersAddFake(100);
        State::updateNice();
        $this->assertTrue(State::nice());
        \Funseco\Core\Services\Cache\CurrentPlayers::forget('fake100');
        State::updateNice();
        $this->assertFalse(State::nice(), 'at 24 or fewer it turns off');
    }

    public function test_custom_ui_hides_challenge_info_and_round_scores(): void
    {
        $xml = app(P\CustomUi::class)->xml();
        $this->assertStringContainsString('<challenge_info visible="false"/>', $xml);
        $this->assertStringContainsString('<round_scores visible="false"/>', $xml);
        $this->assertStringContainsString('<chat visible="true"/>', $xml);
        $this->assertStringContainsString('<scoretable visible="false"/>', $xml);
        $this->assertStringContainsString('<global visible="true"/>', $xml);
    }

    public function test_tmx_widget_hidden_with_null_default_and_shown_with_exchange(): void
    {
        $this->app->instance(\Funseco\Core\SharedKernel\TrackExchange::class, new \Funseco\Core\Services\Defaults\NullTrackExchange);
        $this->assertFalse(app(P\TmxWidget::class)->enabled());

        $track = new \Funseco\Core\SharedKernel\Values\TmxTrack(7, 'uid-1', 'T', 'a', '2010-01-01T00:00:00', null, 'Race', 'Fullspeed', 'Stadium',
            'Day', 'Easy', 'Single', 'Short', 3, '', 'TMNF', '', 31000, 'http://t/7', 'http://t/7.jpg', 'http://t/dl', null,
            [new \Funseco\Core\SharedKernel\Values\TmxRecord(1, 1, 'Zed', 29500, 0, null, null, null)]);
        $this->app->instance(\Funseco\Core\SharedKernel\TrackExchange::class, new FakeExchange($track));
        $w = app(P\TmxWidget::class);
        $this->assertTrue($w->enabled());
        $xml = $w->render($this->alice);
        $this->assertStringContainsString('0:29.50', $xml);
        $this->assertStringContainsString('WORLD-RECORD', $xml);
        $this->assertStringContainsString('action="', $xml);

        $this->app->instance(\Funseco\Core\SharedKernel\TrackExchange::class, new FakeExchange(null));
        $xml = $w->render($this->alice);
        $this->assertStringContainsString('NOT AT', $xml);
        $this->assertStringNotContainsString('action="', $xml);
    }

    public function test_favorite_link_needs_a_server_login(): void
    {
        $this->assertFalse(app(P\FavoriteWidget::class)->enabled());
        config(['widgets.favorite.login' => 'my_server', 'widgets.favorite.zone' => 'World|Greece']);
        $this->assertTrue(app(P\FavoriteWidget::class)->enabled());
        $xml = app(P\FavoriteWidget::class)->render();
        $this->assertStringContainsString('manialink="addfavorite?action=add&amp;server=my_server&amp;name=My%20%24f00Server&amp;zone=World%7CGreece"', $xml);
        $this->assertStringContainsString('addplayerid="1"', $xml);
    }

    public function test_logo_is_branded_and_configurable(): void
    {
        config(['manialinks.eyepiece_widget.race.enabled' => 'true']); // off by default on this server
        $xml = app(P\LogoWidget::class)->render();
        $this->assertStringContainsString('Funseco', $xml);
        $this->assertStringNotContainsString('undef.name', $xml);
        config(['widgets.branding.logo.normal' => 'http://img/logo.png', 'widgets.branding.url' => 'http://example.org']);
        $xml = app(P\LogoWidget::class)->render();
        $this->assertStringContainsString('image="http://img/logo.png"', $xml);
        $this->assertStringContainsString('url="http://example.org"', $xml);
    }

    public function test_timers_refresh_ranking_every_interval_and_clock_every_minute(): void
    {
        $this->mapStart();
        $this->responses['GetCurrentRanking'] = [['Login' => 'alice', 'Rank' => 1, 'BestTime' => 30000, 'Score' => 0]];
        $this->forgetSent();
        for ($i = 0; $i < 9; $i++) {
            app(Lifecycle::class)->everySecond();
        }
        $this->assertSame([], $this->sent('GetCurrentRanking'));
        app(Lifecycle::class)->everySecond();   // the 10th second
        $this->assertCount(1, $this->sent('GetCurrentRanking'));
        $this->assertTrue($this->shown('players'));
        $this->assertFalse($this->shown('clock'));
        $this->forgetSent();
        for ($i = 0; $i < 50; $i++) {
            app(Lifecycle::class)->everySecond();
        }
        $this->assertTrue($this->shown('clock'), 'the clock updates at 60 s');

        State::setScore(true);
        $this->forgetSent();
        app(Lifecycle::class)->everySecond();
        $this->assertSame([], $this->calls, 'no updates at score');
    }

    public function test_custom_ui_is_sent_again_when_the_race_is_running(): void
    {
        $this->forgetSent();
        event(new \Funseco\Core\SharedKernel\Events\GameStatusChanged(4, 'Running - Play'));

        $this->assertStringContainsString('<challenge_info visible="false"/>', $this->xmlTo('alice'));

        $this->forgetSent();
        event(new \Funseco\Core\SharedKernel\Events\GameStatusChanged(5, 'Running - Finish'));
        $this->assertStringNotContainsString('<custom_ui>', $this->xmlTo('alice'));
    }
}

function ServerInfo_set(int $players, int $specs): void
{
    \Funseco\Core\Services\Cache\ServerInfo::set(['max_players' => $players, 'max_spectators' => $specs, 'server_name' => 'x']);
}

function ServerInfo_mode(int $mode): void
{
    \Funseco\Core\Services\Cache\ServerInfo::setGameMode($mode);
}

function CurrentPlayersAddFake(int $i): void
{
    \Funseco\Core\Services\Cache\CurrentPlayers::add(new \Funseco\Core\Models\Player(['login' => 'fake'.$i, 'nickname' => 'f']));
}
