<?php
namespace Funseco\Widgets\Tests;

use Illuminate\Support\Facades\Event;
use Funseco\Core\Models\Player;
use Funseco\Core\Servers\TmForever\Values\PlayerInfo;
use Funseco\Core\Services\Cache\CurrentPlayers;
use Funseco\Core\Services\Chat\Facades\Chat;
use Funseco\Core\SharedKernel\RecordWidgetVisibility;
use Funseco\Core\SharedKernel\Events\PlayerConnected;
use Funseco\Core\SharedKernel\Events\PlayerDisconnected;
use Funseco\Core\SharedKernel\Events\RecordWidgetsToggled;
use Funseco\Core\SharedKernel\Visits;
use Funseco\Widgets\ClickHandler;
use Funseco\Widgets\Listeners\Lifecycle;
use Funseco\Widgets\Models\PlayerStats;
use Funseco\Widgets\Panels as P;
use Funseco\Widgets\Support\Ids;
use Funseco\Widgets\Support\State;

class CommandsAndPlayersTest extends WidgetsTestCase
{
    private function lastChat(): string
    {
        $all = array_merge($this->messages('ChatSendServerMessageToLogin'), $this->messages('ChatSendServerMessage'));

        return (string) end($all);
    }

    public function test_f7_hides_and_shows_the_record_widgets(): void
    {
        Event::fake([RecordWidgetsToggled::class]);
        $this->forgetSent();
        Chat::resolve('/togglewidgets', 'alice');

        $this->assertTrue(app(RecordWidgetVisibility::class)->hidden($this->alice));
        Event::assertDispatched(RecordWidgetsToggled::class, fn ($e) => $e->player->login === 'alice' && $e->visible === false);
        $xml = $this->xmlTo('alice');
        $this->assertTrue($this->cleared('ranking', $xml), 'ranking widget hidden');
        $this->assertTrue($this->cleared('tmx', $xml), 'tmx widget hidden');
        $this->assertStringContainsString('Your record widgets are now hidden, press F7 to reactivate them.', $this->lastChat());

        $this->forgetSent();
        State::put('ranking', [['Login' => 'alice', 'Rank' => 1, 'BestTime' => 30000, 'Score' => 0]]);
        app(ClickHandler::class)->handle($this->alice, 'widgets_toggle');   // the F7 key
        $this->assertFalse(app(RecordWidgetVisibility::class)->hidden($this->alice));
        $this->assertTrue($this->shown('ranking', $this->xmlTo('alice')), 'ranking comes back on show');
        $this->assertStringContainsString('Your record widgets are now shown, press F7 to hide them.', $this->lastChat());
    }

    public function test_toggle_closes_an_open_window(): void
    {
        app(\Funseco\Widgets\Windows\HelpWindow::class)->open($this->alice);
        $this->forgetSent();
        Chat::resolve('/togglewidgets', 'alice');
        $this->assertTrue($this->cleared('window', $this->xmlTo('alice')));
    }

    public function test_toggle_does_nothing_at_score_and_is_refused_in_nice_mode(): void
    {
        State::setScore(true);
        Chat::resolve('/togglewidgets', 'alice');
        $this->assertFalse(State::hidden('alice'));
        $this->assertSame([], $this->messages());

        State::setScore(false);
        State::setNice(true);
        Chat::resolve('/togglewidgets', 'alice');
        $this->assertFalse(State::hidden('alice'));
        $this->assertStringContainsString('too many players on the server', $this->lastChat());
    }

    public function test_eyepiece_hide_and_show_are_stored(): void
    {
        Chat::resolve('/eyepiece hide', 'alice');
        $this->assertFalse(PlayerStats::of($this->alice)->display_widgets);
        $this->assertTrue(State::hidden('alice'));
        $this->assertStringContainsString('permanent hidden and your preference was saved', $this->lastChat());
        $this->assertStringContainsString('/eyepiece show', $this->lastChat());

        Chat::resolve('/eyepiece show', 'alice');
        $this->assertTrue(PlayerStats::of($this->alice)->display_widgets);
        $this->assertFalse(State::hidden('alice'));
        $this->assertStringContainsString('permanent shown', $this->lastChat());
    }

    public function test_eyepiece_hide_works_at_score_and_stored_preference_loads_on_connect(): void
    {
        State::setScore(true);
        Chat::resolve('/eyepiece hide', 'alice');
        $this->assertFalse(PlayerStats::of($this->alice)->display_widgets);

        State::setHidden('alice', false);
        app(Lifecycle::class)->playerConnected(new PlayerConnected($this->alice, new PlayerInfo(['Login' => 'alice', 'NickName' => 'A'])));
        $this->assertTrue(State::hidden('alice'), 'the stored choice hides the widgets again');
    }

    public function test_eyepiece_opens_help_at_race_and_is_refused_at_score(): void
    {
        $this->forgetSent();
        Chat::resolve('/eyepiece', 'alice');
        $xml = $this->xmlTo('alice');
        $this->assertStringContainsString('/togglewidgets', $xml);
        $this->assertStringContainsString('/elist [PARAMETER]', $xml);
        $this->assertStringContainsString('Funseco widgets', $xml);
        $this->assertStringNotContainsString('undef.name', $xml);
        $this->assertStringNotContainsString('RECORDS-EYEPIECE', $xml);

        State::setScore(true);
        $this->forgetSent();
        Chat::resolve('/eyepiece', 'alice');
        $this->assertSame('', $this->xmlTo('alice'));
        $this->assertStringContainsString('Could not display this window at score!', $this->lastChat());
    }

    public function test_help_pages(): void
    {
        $help = app(\Funseco\Widgets\Windows\HelpWindow::class);
        $help->open($this->alice, 2);
        $this->assertStringContainsString('/eyeset forcenice (true|false)', $this->xmlTo('alice'));
        $this->assertStringContainsString('/eyepiece payouts', $this->xmlTo('alice'));
        $this->forgetSent();
        app(ClickHandler::class)->handle($this->alice, 'widgets_page_next');
        $this->assertStringContainsString('A TrackMania Nations Forever server controller', $this->xmlTo('alice'));
    }

    public function test_eyepiece_payouts_is_for_staff_only(): void
    {
        Chat::resolve('/eyepiece payouts', 'alice');
        $this->assertSame([], $this->messages());
        config(['adminops.masteradmins' => ['alice']]);
        Chat::resolve('/eyepiece payouts', 'alice');
        $this->assertStringContainsString('WinningPayoutWidget is not enabled', $this->lastChat());
    }

    public function test_eyeset_is_for_master_admins(): void
    {
        Chat::resolve('/eyeset lfresh 5', 'alice');
        $this->assertStringNotContainsString('refresh_interval', $this->lastChat());

        config(['adminops.masteradmins' => ['alice']]);
        Chat::resolve('/eyeset lfresh 5', 'alice');
        $this->assertStringContainsString('Set <refresh_interval> (normal mode) to "5" sec.', $this->lastChat());
        $this->assertSame(5, State::get('lfresh'));
        Chat::resolve('/eyeset hfresh 20', 'alice');
        $this->assertStringContainsString('(nice mode) to "20" sec.', $this->lastChat());
        Chat::resolve('/eyeset llimit 10', 'alice');
        $this->assertStringContainsString('Set <lower_limit> (nice mode) to "10" Players.', $this->lastChat());
        Chat::resolve('/eyeset ulimit 12', 'alice');
        $this->assertStringContainsString('Set <upper_limit> (nice mode) to "12" Players.', $this->lastChat());
        Chat::resolve('/eyeset playermarker false', 'alice');
        $this->assertStringContainsString('<features><mark_online_player_records> to "false".', $this->lastChat());
        Chat::resolve('/eyeset forcenice true', 'alice');
        $this->assertStringContainsString('Set <nicemode><force> to "true".', $this->lastChat());
        $this->assertTrue(State::nice(), 'forcenice turns nice mode on at once');
        Chat::resolve('/eyeset what', 'alice');
        $this->assertStringContainsString('Did not found any possible parameter to set!', $this->lastChat());
    }

    public function test_eyeset_reload_resends_widgets_but_not_at_score(): void
    {
        config(['adminops.masteradmins' => ['alice']]);
        $this->forgetSent();
        Chat::resolve('/eyeset reload', 'alice');
        $this->assertStringContainsString('Reload of the widget configuration done.', $this->lastChat());
        $this->assertTrue($this->shown('gamemode', $this->xmlTo('alice')));

        State::setScore(true);
        Chat::resolve('/eyeset reload', 'alice');
        $this->assertStringContainsString('Can not reload the configuration at Score!', $this->lastChat());
    }

    public function test_connect_counts_a_visit_and_announces_the_player(): void
    {
        $this->responses['GetDetailedPlayerInfo'] = ['Login' => 'alice', 'Path' => 'World|Germany|Berlin'];
        $this->responses['GetCurrentGameInfo'] = ['GameMode' => 1];
        $info = new PlayerInfo(['Login' => 'alice', 'NickName' => '$f00Alice', 'LadderRanking' => 12345]);
        app(Lifecycle::class)->playerConnected(new PlayerConnected($this->alice, $info));
        app(Lifecycle::class)->playerConnected(new PlayerConnected($this->alice, $info));

        $this->assertSame(2, PlayerStats::of($this->alice)->visits);
        $join = $this->messages('ChatSendServerMessage')[1];
        $this->assertStringContainsString('New Player', $join);
        $this->assertStringContainsString('Alice', $join);
        $this->assertStringNotContainsString('$f00', $join, 'nickname colours stripped');
        $this->assertStringContainsString('World, Germany, Berlin', $join);
        $this->assertStringContainsString('Visits: $fff2', str_replace('$39f', '', $join));
        $this->assertStringContainsString('12,345', $join);

        $xml = $this->xmlTo('alice');
        foreach (['gamemode', 'challenge', 'toggle_key'] as $name) {
            $this->assertTrue($this->shown($name, $xml), "$name for the joiner");
        }
        $this->assertStringContainsString('actionkey="3"', $xml);
        $this->assertStringContainsString('<manialinks><manialink id=', $xml);
        $this->assertStringContainsString('<custom_ui>', $xml);
    }

    public function test_an_edited_join_text_is_used(): void
    {
        app(\Funseco\Core\Services\Messages\MessageService::class)->set('widgets.join', 'text', 'Hi {nickname} from {zone}');
        $this->responses['GetDetailedPlayerInfo'] = ['Login' => 'alice', 'Path' => 'World|Germany'];
        app(Lifecycle::class)->playerConnected(new PlayerConnected($this->alice, new PlayerInfo(['Login' => 'alice', 'NickName' => 'Alice'])));
        $this->assertStringContainsString('Hi Alice from World, Germany', $this->messages('ChatSendServerMessage')[0]);
    }

    public function test_the_leave_message_is_skipped_when_its_toggle_is_off(): void
    {
        app(\Funseco\Core\Services\Messages\MessageService::class)->set('widgets.leave', 'enabled', false);
        $this->responses['GetDetailedPlayerInfo'] = [];
        app(Lifecycle::class)->playerConnected(new PlayerConnected($this->bob, new PlayerInfo(['Login' => 'bob', 'NickName' => 'Bob'])));
        $before = count($this->messages('ChatSendServerMessage'));
        State::put('session.bob', time() - 60);
        app(Lifecycle::class)->playerDisconnected(new PlayerDisconnected($this->bob));
        $this->assertCount($before, $this->messages('ChatSendServerMessage'));
    }

    public function test_leave_message_has_the_session_playtime(): void
    {
        $this->responses['GetDetailedPlayerInfo'] = [];
        app(Lifecycle::class)->playerConnected(new PlayerConnected($this->bob, new PlayerInfo(['Login' => 'bob', 'NickName' => 'Bob'])));
        State::put('session.bob', time() - 3725);
        app(Lifecycle::class)->playerDisconnected(new PlayerDisconnected($this->bob));
        $left = (string) end($this->calls[array_key_last(array_filter($this->calls, fn ($c) => $c[0] === 'ChatSendServerMessage'))][1]);
        $this->assertStringContainsString('has left the game after', $left);
        $this->assertStringContainsString('01:02:05', $left);
        $this->assertStringContainsString('France', $left);
    }

    public function test_players_who_were_online_at_startup_get_no_join_message(): void
    {
        $this->responses['GetCurrentGameInfo'] = ['GameMode' => 1];
        $this->responses['GetLadderServerLimits'] = ['LadderServerLimitMin' => 0, 'LadderServerLimitMax' => 50000];
        app(Lifecycle::class)->startup();
        $this->assertSame([], $this->messages('ChatSendServerMessage'));
        $this->assertSame(0, PlayerStats::of($this->alice)->visits);
        $this->assertTrue($this->shown('gamemode', $this->xmlTo('alice')));
    }

    public function test_visits_contract_lists_the_top_visitors(): void
    {
        PlayerStats::of($this->alice)->update(['visits' => 3]);
        PlayerStats::of($this->bob)->update(['visits' => 7]);
        $top = app(Visits::class)->top(10);
        $this->assertSame(['bob' => 7, 'alice' => 3], $top);
        $this->assertSame(['bob' => 7], app(Visits::class)->top(1));
    }

    public function test_finish_counter_counts_real_finishes(): void
    {
        State::setScore(false);
        $finish = new \Funseco\Core\SharedKernel\Events\PlayerFinished($this->alice, $this->map, 30000, new \Funseco\Core\Servers\TmForever\Values\CheckpointCollection);
        app(Lifecycle::class)->finished($finish);
        app(Lifecycle::class)->finished($finish);
        $this->assertSame(2, PlayerStats::of($this->alice)->finishes);
    }

    public function test_placements_validation_and_chat_command(): void
    {
        config(['widgets.placements' => [
            ['enabled' => true, 'display' => 'race', 'pos_x' => 1, 'pos_y' => 2, 'width' => 3, 'height' => 4, 'chat_command' => '/afk now', 'icon_style' => 'Icons64x64_1', 'icon_substyle' => 'Close'],
            ['enabled' => true, 'display' => 'race', 'pos_x' => 1],   // incomplete: skipped
            ['enabled' => true, 'display' => 'always', 'pos_x' => 5, 'pos_y' => 6, 'width' => 3, 'height' => 4, 'text' => 'Hi <b>', 'url' => 'http://a', 'chat_command' => '/ignored'],
        ]]);
        $p = app(P\PlacementWidgets::class);
        $p->forget();
        $this->assertSame([0, 2], array_keys($p->placements()));
        $this->assertSame(['afk', ['now']], $p->chatCommand(0));
        $this->assertNull($p->chatCommand(2), 'a url wins over the chat command');

        $p->sendAlways($this->alice);
        $this->assertStringContainsString('url="http://a"', $this->xmlTo('alice'));
        $this->assertStringContainsString('Hi &lt;b&gt;', $this->xmlTo('alice'));
        $this->forgetSent();
        $p->sendRace($this->alice);
        $this->assertStringContainsString('action="'.Ids::action('chat_0').'"', $this->xmlTo('alice'));
        $this->assertStringContainsString('posn="1 2 0"', $this->xmlTo('alice'));
    }

    public function test_discord_button_hidden_without_url(): void
    {
        config(['funseco.discord.url' => '']);
        $p = app(P\PlacementWidgets::class);
        $p->forget();
        $this->assertSame([], $p->placements());
    }

    public function test_score_placement_placeholders(): void
    {
        config(['widgets.placements' => [
            ['enabled' => true, 'display' => 'score', 'pos_x' => 1, 'pos_y' => 2, 'width' => 3, 'height' => 4, 'url' => 'http://x/%TRACK_UID%?n=%TRACK_NAME%'],
            ['enabled' => true, 'display' => 'score', 'pos_x' => 1, 'pos_y' => 2, 'width' => 3, 'height' => 4, 'url' => 'http://tmx/%TRACK_TMX_ID%'],
            ['enabled' => true, 'display' => 'race', 'pos_x' => 1, 'pos_y' => 2, 'width' => 3, 'height' => 4, 'url' => 'http://r/%TRACK_UID%'],
        ]]);
        $this->app->instance(\Funseco\Core\SharedKernel\TrackExchange::class, new \Funseco\Core\Services\Defaults\NullTrackExchange);
        $p = app(P\PlacementWidgets::class);
        $p->forget();
        $p->sendScore($this->map);
        $this->assertStringContainsString('url="http://x/uid-1?n=Speed"', $this->xml());
        $this->assertStringNotContainsString('http://tmx/', $this->xml(), 'no TMX track: the placement is skipped');
        $this->forgetSent();
        $p->sendRace($this->alice);
        $this->assertStringContainsString('url="http://r/"', $this->xmlTo('alice'), 'placeholders are removed outside score placements');
    }

    public function test_donation_widget_lists_the_amounts_and_its_buttons_run_donate(): void
    {
        State::setScore(true);
        $xml = app(P\DonationWidget::class)->render();
        $this->assertStringContainsString('PLEASE', $xml);
        $this->assertStringContainsString('10000$n $mC', $xml);
        $this->assertStringContainsString('sizen="4.6 21.35"', $xml, '6.55 + 1.85 * 8');
        config(['manialinks.donation_widget.enabled' => 'false']);
        $this->assertTrue($this->cleared('donation', app(P\DonationWidget::class)->render()));

        $ran = [];
        \Chat::register(new class($ran) implements \Funseco\Core\SharedKernel\Commands\Command {
            use \Funseco\Core\SharedKernel\Commands\DeclaresCommand;

            protected string $name = 'donate';

            public function __construct(public array &$ran)
            {
            }

            public function handle(\Funseco\Core\Models\Player $player, array $args): void
            {
                $this->ran[] = $args;
            }
        });
        app(ClickHandler::class)->handle($this->alice, 'widgets_donate_1');
        $this->assertSame([['250']], $ran);
    }
}
