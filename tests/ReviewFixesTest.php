<?php
namespace Funseco\Widgets\Tests;

use Illuminate\Support\Facades\Event;
use Funseco\Core\Servers\TmForever\Values\ChallengeInfo;
use Funseco\Core\Servers\TmForever\Values\PlayerRankingsCollection;
use Funseco\Core\Services\Chat\Facades\Chat;
use Funseco\Core\SharedKernel\Events\MapEnded;
use Funseco\Core\SharedKernel\Events\RecordWidgetsToggled;
use Funseco\Widgets\Listeners\Lifecycle;
use Funseco\Widgets\Panels as P;
use Funseco\Widgets\Support\State;

class ReviewFixesTest extends WidgetsTestCase
{
    private function ended(bool $warmup, bool $restart): MapEnded
    {
        return new MapEnded($this->map, new ChallengeInfo(['uid-1', 'n', 'f', 'a', 'Stadium', 'Day', 1, 1, 1, 1, 0, false, 1, 4]), new PlayerRankingsCollection, $warmup, $restart);
    }

    public function test_warmup_and_restart_do_not_open_the_score_screen(): void
    {
        State::setScore(false);
        $this->forgetSent();
        app(Lifecycle::class)->mapEnded($this->ended(true, false));
        $this->assertFalse(State::atScore());
        app(Lifecycle::class)->mapEnded($this->ended(false, true));
        $this->assertFalse(State::atScore());
        $this->assertSame([], $this->sent('SendDisplayManialinkPage'));
    }

    public function test_late_ranking_answer_does_not_draw_at_score(): void
    {
        State::setScore(true);
        $this->responses['GetCurrentRanking'] = [['Login' => 'alice', 'Rank' => 1, 'BestTime' => 30000, 'Score' => 0]];
        $this->forgetSent();
        app(P\CurrentRankingWidget::class)->refresh();
        $this->assertSame('', $this->xmlTo('alice'));
    }

    public function test_hiding_at_score_does_not_dispatch_the_toggle_event(): void
    {
        Event::fake([RecordWidgetsToggled::class]);
        State::setScore(true);
        Chat::resolve('/eyepiece hide', 'alice');
        Event::assertNotDispatched(RecordWidgetsToggled::class);
    }

    public function test_startup_records_a_session_start_for_online_players(): void
    {
        $this->responses['GetCurrentGameInfo'] = ['GameMode' => 1];
        $this->responses['GetLadderServerLimits'] = ['LadderServerLimitMin' => 0, 'LadderServerLimitMax' => 50000];
        app(Lifecycle::class)->startup();
        $this->assertNotNull(State::get('session.alice'));
    }
}
