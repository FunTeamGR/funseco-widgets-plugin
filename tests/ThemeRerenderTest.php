<?php
namespace Funseco\Widgets\Tests;

use Funseco\Core\SharedKernel\UI\Theme\ThemeRegistry;
use Funseco\Widgets\Panels\GamemodeWidget;
use Funseco\Widgets\Support\State;

class ThemeRerenderTest extends WidgetsTestCase
{
    private function themes(): ThemeRegistry
    {
        return app(ThemeRegistry::class);
    }

    public function test_player_choice_resends_only_their_widgets_in_the_new_theme(): void
    {
        $this->forgetSent();
        $this->themes()->setForPlayer($this->bob, 'neon');

        $this->assertTrue($this->shown('gamemode', $this->xmlTo('bob')));
        $this->assertStringNotContainsString('ProgressBar', $this->xmlTo('bob'));
        $this->assertSame('', $this->xmlTo('alice'));
    }

    public function test_default_change_resends_everybody_without_own_choice(): void
    {
        $this->themes()->setForPlayer($this->bob, 'glass');
        $this->forgetSent();
        $this->themes()->use('neon');

        $this->assertTrue($this->shown('gamemode', $this->xmlTo('alice')));
        $this->assertSame('', $this->xmlTo('bob'));
    }

    public function test_rerender_in_score_mode_sends_score_widgets(): void
    {
        State::setScore(true);
        $this->forgetSent();
        $this->themes()->use('warm');

        $this->assertFalse($this->shown('gamemode', $this->xmlTo('alice')));
        $this->assertTrue($this->shown('next_track', $this->xmlTo('alice')) || $this->shown('logo', $this->xmlTo('alice')));
    }

    public function test_disabled_widget_stays_reset(): void
    {
        config(['manialinks.gamemode_widget.enabled' => 'false']);
        $this->forgetSent();
        $this->themes()->use('neon');

        $this->assertTrue($this->cleared('gamemode', $this->xmlTo('alice')));
    }

    public function test_two_switches_leave_the_last_theme_on_top(): void
    {
        $this->forgetSent();
        $this->themes()->use('neon');
        $this->themes()->use('warm');

        $widget = app(GamemodeWidget::class);
        $xml = $this->xmlTo('alice');
        $warm = strrpos($xml, $widget->render($this->alice, $this->themes()->get('warm')));
        $neon = strrpos($xml, $widget->render($this->alice, $this->themes()->get('neon')));
        $this->assertNotFalse($warm);
        $this->assertGreaterThan($neon, $warm);
    }

    public function test_record_lists_are_not_redrawn_at_the_score_screen(): void
    {
        \Illuminate\Support\Facades\Event::fake([\Funseco\Core\SharedKernel\Events\RecordWidgetsToggled::class]);
        State::setScore(true);
        $this->themes()->use('warm');
        \Illuminate\Support\Facades\Event::assertNotDispatched(\Funseco\Core\SharedKernel\Events\RecordWidgetsToggled::class);

        State::setScore(false);
        $this->themes()->use('neon');
        \Illuminate\Support\Facades\Event::assertDispatched(\Funseco\Core\SharedKernel\Events\RecordWidgetsToggled::class);
    }
}
