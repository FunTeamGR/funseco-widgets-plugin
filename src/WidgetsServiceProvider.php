<?php
namespace Funseco\Widgets;

use Funseco\Core\SharedKernel\PublishesPluginConfig;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Funseco\Core\Services\ManiaLink\ManiaLinkService;
use Funseco\Core\SharedKernel\Events as E;
use Funseco\Core\SharedKernel\RecordWidgetVisibility;
use Funseco\Core\SharedKernel\UI\Grid\Overflow;
use Funseco\Core\SharedKernel\UI\WidgetRegistry;
use Funseco\Core\SharedKernel\Visits;
use Funseco\Widgets\Listeners\Lifecycle;
use Funseco\Widgets\Panels as P;
use Funseco\Widgets\Support\Ids;
use Funseco\Core\SharedKernel\Settings\Setting;
use Funseco\Core\SharedKernel\Settings\SettingsRegistry;
use Funseco\Core\SharedKernel\Settings\SettingType;

class WidgetsServiceProvider extends ServiceProvider
{
    use PublishesPluginConfig;

    /** Panels and windows are stateless services (state lives in the state() store). */
    private const SINGLETONS = [
        P\LadderLimitWidget::class, P\GamemodeWidget::class, P\PlayerSpectatorWidget::class, P\TrackCountWidget::class,
        P\VisitorsWidget::class, P\ChallengeWidget::class, P\NextTrackWidget::class, P\LogoWidget::class, P\FavoriteWidget::class,
        P\ClockWidget::class, P\TmxWidget::class, P\CurrentRankingWidget::class, P\CheckpointCountWidget::class,
        P\NextEnvironmentWidget::class, P\NextGamemodeWidget::class, P\DonationWidget::class, P\RoundScoreWidget::class,
        P\ToggleKey::class, P\CustomUi::class, P\PlacementWidgets::class,
        Windows\TrackListWindow::class, Windows\TrackToolsWindows::class, Windows\TrackOverviewWindow::class,
        Windows\TimezoneWindow::class, Windows\NationsWindow::class, Windows\TmxInfoWindow::class, Windows\HelpWindow::class,
        Windows\WelcomeWindow::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/widgets.php', 'widgets');
        $this->publishPluginConfig(__DIR__.'/..');

        // join/leave text: the message toggle replaces the join_leave_info `enabled` gate (its value is the default).
        $this->app->make(\Funseco\Core\SharedKernel\Messages\MessageRegistry::class)->declare('widgets', [
            \Funseco\Core\SharedKernel\Messages\Message::make('join', 'Player joined', (string) config('widgets.messages.join', config('manialinks.messages.join')),
                ['title' => 'Role title or "New Player"', 'nickname' => 'Nickname', 'zone' => 'Zone', 'nation' => 'Nation', 'visits' => 'Visit count',
                    'ladderrank' => 'Ladder rank', 'serverrank' => 'Server rank'],
                enabled: \Funseco\Widgets\Support\Ui::enabled('join_leave_info'), raw: ['title']),
            \Funseco\Core\SharedKernel\Messages\Message::make('leave', 'Player left', (string) config('widgets.messages.leave', config('manialinks.messages.leave')),
                ['nickname' => 'Nickname', 'nation' => 'Nation', 'zone' => 'Zone', 'playtime' => 'Time played'],
                enabled: \Funseco\Widgets\Support\Ui::enabled('join_leave_info')),
        ]);

        // All read when a widget or window renders, so changes show on the next render.
        $this->app->make(SettingsRegistry::class)->declare('widgets', 'widgets', [
            Setting::string('branding.name', 'Server brand name'),
            Setting::string('branding.footer', 'Window footer text'),
            Setting::url('branding.url', 'Brand link URL'),
            Setting::url('branding.logo.normal', 'Logo image URL'),
            Setting::url('branding.logo.focus', 'Logo hover image URL'),
            Setting::list('branding.caption', 'Logo caption (two words)'),
            Setting::url('branding.image.normal', 'Help image URL'),
            Setting::url('branding.image.focus', 'Help image hover URL'),
            Setting::url('branding.image.link', 'Help image link URL'),
            Setting::url('images.base_url', 'Widget images base URL'),
            Setting::bool('preload.enabled', 'Preload images'),
            Setting::int('preload.first', 'Preload start delay', min: 0, max: 600, unit: 's'),
            Setting::int('preload.every', 'Preload batch interval', min: 1, max: 600, unit: 's'),
            Setting::string('favorite.login', 'Favorite button server login'),
            Setting::string('favorite.name', 'Favorite button server name'),
            Setting::string('favorite.zone', 'Favorite button server zone'),
            Setting::url('tmx_home_url', 'TMX home URL'),
            Setting::int('max_records', 'Records ranked per track', min: 1, max: 10000),
            Setting::int('clock_interval', 'Clock refresh interval', min: 1, max: 3600, unit: 's'),
            Setting::list('default_rounds_points', 'Default rounds points', itemType: SettingType::Int),
        ]);
        foreach (self::SINGLETONS as $class) {
            $this->app->singleton($class);
        }
        $this->app->singleton(VisitCounter::class);
        $this->app->singleton(Visibility::class);
        $this->app->singleton(Visits::class, fn ($app) => $app->make(VisitCounter::class));
        $this->app->singleton(RecordWidgetVisibility::class, fn ($app) => $app->make(Visibility::class));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->registerStatusButtons(app(WidgetRegistry::class));

        $manialink = app(ManiaLinkService::class);
        $manialink->addActions(Ids::manialinks());
        $manialink->addActions(Ids::actions(), ClickHandler::class);

        foreach ([Commands\ToggleWidgetsCommand::class, Commands\EyepieceCommand::class, Commands\EyesetCommand::class, Commands\ElistCommand::class] as $command) {
            \Chat::register($command);
        }

        foreach ([
            E\StartupEvent::class             => 'startup',
            E\MapStarted::class               => 'mapStarted',
            E\MapEnded::class                 => 'mapEnded',
            E\GameStatusChanged::class        => 'gameStatusChanged',
            E\JukeboxChanged::class           => 'jukeboxChanged',
            E\TrackListChanged::class         => 'trackListChanged',
            E\TrackExchangeInfoLoaded::class  => 'trackExchangeLoaded',
            E\PlayerConnected::class          => 'playerConnected',
            E\PlayerReady::class              => 'playerReady',
            E\PlayerDisconnected::class       => 'playerDisconnected',
            E\PlayerInfoUpdated::class        => 'playerInfoUpdated',
            E\PlayerCheckpointPassed::class   => 'checkpoint',
            E\PlayerFinished::class           => 'finished',
            E\PlayerRetired::class            => 'retired',
            E\RoundStarted::class             => 'roundStarted',
            E\RoundEnded::class               => 'roundEnded',
            E\EverySecondEvent::class         => 'everySecond',
        ] as $event => $method) {
            Event::listen($event, [Lifecycle::class, $method]);
        }
        Event::listen(E\ThemeChanged::class, [Listeners\ThemeRerender::class, 'handle']);
        Event::listen(E\StartupEvent::class, fn () => app(Support\ReplayCodeServer::class)->listen());
        // A window of another plugin (score lists) replaces ours instead of stacking on it.
        Event::listen(E\WindowOpened::class, function (E\WindowOpened $e) {
            if ($e->owner !== 'widgets' && \Funseco\Widgets\Windows\WindowManager::get($e->player) !== null) {
                \Funseco\Widgets\Windows\WindowManager::close($e->player);
            }
        });
    }

    /** The top-left status buttons (4.6 x 6.5), left to right by order. */
    private function registerStatusButtons(WidgetRegistry $grid): void
    {
        // The branding logo: in the menu row, right of the Menu button, as high as the row (2.2).
        $grid->widget('branding_logo', 'menu', 5,
            width: fn () => config('widgets.branding.logo.normal') ? 2.11 : 9.0, height: 2.2);

        foreach (['ladderlimit_widget' => 10, 'gamemode_widget' => 20, 'player_spectator_widget' => 30, 'current_ranking_widget' => 40] as $name => $order) {
            $grid->widget($name, 'buttons', $order, width: 4.6, height: 6.5, overflow: Overflow::Wrap);
        }
    }
}
