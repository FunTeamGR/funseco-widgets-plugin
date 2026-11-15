<?php
namespace Funseco\Widgets\Listeners;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\Messages\Msg;
use Funseco\Core\Services\Auth\Permissions;
use Funseco\Core\Services\Cache\CurrentMap;
use Funseco\Core\Services\Cache\CurrentPlayers;
use Funseco\Core\Services\Cache\CurrentSpectators;
use Funseco\Core\SharedKernel\Enums\Role;
use Funseco\Core\SharedKernel\Events\JukeboxChanged;
use Funseco\Core\SharedKernel\Events\MapEnded;
use Funseco\Core\SharedKernel\Events\MapStarted;
use Funseco\Core\SharedKernel\Events\PlayerCheckpointPassed;
use Funseco\Core\SharedKernel\Events\PlayerConnected;
use Funseco\Core\SharedKernel\Events\PlayerDisconnected;
use Funseco\Core\SharedKernel\Events\PlayerFinished;
use Funseco\Core\SharedKernel\Events\PlayerInfoUpdated;
use Funseco\Core\SharedKernel\Events\PlayerReady;
use Funseco\Core\SharedKernel\Events\PlayerRetired;
use Funseco\Core\SharedKernel\Events\TrackExchangeInfoLoaded;
use Funseco\Core\SharedKernel\Events\TrackListChanged;
use Funseco\Core\SharedKernel\ServerRanks;
use Funseco\Widgets\Models\PlayerStats;
use Funseco\Widgets\Panels as P;
use Funseco\Widgets\Support\Format;
use Funseco\Widgets\Support\Gamemodes;
use Funseco\Widgets\Support\NextTrack;
use Funseco\Widgets\Support\State;
use Funseco\Widgets\Support\Text;
use Funseco\Widgets\Support\TrackCard;
use Funseco\Widgets\Support\TrackIndex;
use Funseco\Widgets\Support\Ui;
use Funseco\Widgets\VisitCounter;
use Funseco\Widgets\Windows\WelcomeWindow;
use Funseco\Widgets\Windows\WindowManager;

/**
 * When what is sent (spec 23): the race widgets at map start and to joiners, the score widgets
 * at the end of the race, and the small live updates in between.
 */
class Lifecycle
{
    /** Shared race panels (same XML for everybody). */
    private function racePanels(): array
    {
        return array_map(fn ($c) => app($c), [
            P\LadderLimitWidget::class, P\GamemodeWidget::class, P\PlayerSpectatorWidget::class, P\TrackCountWidget::class,
            P\VisitorsWidget::class, P\ChallengeWidget::class, P\LogoWidget::class, P\FavoriteWidget::class,
        ]);
    }

    private function perPlayerPanels(): array
    {
        return array_map(fn ($c) => app($c), [P\ClockWidget::class, P\TmxWidget::class]);
    }

    // ---- startup ----

    public function startup(): void
    {
        if ($map = CurrentMap::get()) {
            State::put('current_card', TrackCard::fromMap($map)->toArray());
        }
        P\VisitorsWidget::refresh();
        State::updateNice();
        Gamemodes::loadLadder(function () {
            Gamemodes::load(function () {
                foreach (CurrentPlayers::all() as $player) {
                    State::put('session.'.$player->login, time());   // so leave messages work
                    $this->sendRaceTo($player);
                }
            });
        });
    }

    // ---- map start / end ----

    public function mapStarted(MapStarted $e): void
    {
        State::setScore(false);
        State::put('warmup', $e->warmup);
        $card = TrackCard::fromMap($e->map);
        $current = State::get('current_card');
        if (($current['uid'] ?? null) !== $card->uid) {
            State::put('last', $current);
        }
        State::put('current_card', $card->toArray());
        State::forget('tracks');
        TrackIndex::forgetPersonal();
        foreach (CurrentPlayers::all() as $player) {
            State::forget('cp.'.$player->login);
        }

        app(P\RoundScoreWidget::class)->clearAll();
        app(P\NextTrackWidget::class)->hideAll();
        app(P\NextEnvironmentWidget::class)->hideAll();
        app(P\NextGamemodeWidget::class)->hideAll();
        app(P\DonationWidget::class)->hideAll();
        app(P\PlacementWidgets::class)->clearScore();
        State::updateNice();

        $send = function () {
            foreach (CurrentPlayers::all() as $p) {
                app(P\CustomUi::class)->send($p);
            }
            app(P\CurrentRankingWidget::class)->reset();
            foreach (array_merge($this->racePanels(), $this->perPlayerPanels()) as $panel) {
                $panel->showAll();
            }
            app(P\CheckpointCountWidget::class)->resetAll();
            app(P\RoundScoreWidget::class)->showAll();
            $placements = app(P\PlacementWidgets::class);
            $placements->enabled() && $placements->sendRace();
        };
        Gamemodes::load(function () use ($send) {
            if (date('w') === '0' && Ui::bool(Ui::cfg('ladderlimit_widget', 'roc_server', 'false'))) {
                Gamemodes::loadLadder($send);   // ROC servers change their ladder limits on Sundays

                return;
            }
            $send();
        });
    }

    public function mapEnded(MapEnded $e): void
    {
        // Warm-up rounds and restarts show no score screen (mirrors the scoreboard plugin).
        if ($e->wasWarmup || $e->restart) {
            return;
        }
        State::setScore(true);
        $t = microtime(true);
        app(P\DonationWidget::class)->showAll();   // first: nothing below may delay it
        \Log::info('[Timing] widgets mapEnded: donation sent at '.number_format($t, 3).' (took '.round((microtime(true) - $t) * 1000).' ms)');
        WindowManager::closeAll();
        foreach (array_merge($this->racePanels(), $this->perPlayerPanels(), [app(P\CheckpointCountWidget::class), app(P\RoundScoreWidget::class), app(P\CurrentRankingWidget::class)]) as $panel) {
            $panel->hideAll();
        }
        app(P\PlacementWidgets::class)->clearRace();
        app(P\RoundScoreWidget::class)->flushPoints();
        app(P\RoundScoreWidget::class)->clearAll();
        P\VisitorsWidget::refresh();

        // everything that does not need the next track
        app(P\ClockWidget::class)->showAll();
        app(P\FavoriteWidget::class)->showAll();
        app(P\LogoWidget::class)->showAll();
        $placements = app(P\PlacementWidgets::class);
        $placements->enabled() && $placements->sendScore($e->map);

        $this->sendNext();
        Gamemodes::nextMode(function (int $mode) {
            State::put('next_mode', $mode);
            app(P\NextGamemodeWidget::class)->showAll();
        });
    }

    /** Next track widget and next environment, at score. */
    private function sendNext(): void
    {
        NextTrack::load(function () {
            if (State::atScore()) {
                app(P\NextTrackWidget::class)->showAll();
                app(P\NextEnvironmentWidget::class)->showAll();
            }
        });
    }

    public function jukeboxChanged(JukeboxChanged $e): void
    {
        State::atScore() && $this->sendNext();
    }

    public function trackListChanged(TrackListChanged $e): void
    {
        if ($e->listModified) {
            State::forget('tracks');
            TrackIndex::forgetPersonal();
            Gamemodes::load(fn () => State::atScore() || app(P\TrackCountWidget::class)->showAll());
        }
        State::atScore() && $this->sendNext();
    }

    public function trackExchangeLoaded(TrackExchangeInfoLoaded $e): void
    {
        State::atScore() || app(P\TmxWidget::class)->showAll();
        State::atScore() || app(P\ChallengeWidget::class)->showAll();
    }

    // ---- players ----

    /** All race widgets (and the key, custom UI, placements) for one player. */
    public function sendRaceTo(Player $player): void
    {
        app(P\ToggleKey::class)->show($player);
        app(P\CustomUi::class)->send($player);
        if (State::atScore()) {
            $this->sendScoreTo($player);

            return;
        }
        foreach (array_merge($this->racePanels(), $this->perPlayerPanels()) as $panel) {
            $panel->show($player);
        }
        app(P\CurrentRankingWidget::class)->show($player);
        app(P\RoundScoreWidget::class)->show($player);
        $placements = app(P\PlacementWidgets::class);
        if ($placements->enabled()) {
            $placements->sendAlways($player);
            $placements->sendRace($player);
        }
    }

    private function sendScoreTo(Player $player): void
    {
        foreach ([P\NextTrackWidget::class, P\ClockWidget::class, P\FavoriteWidget::class, P\NextEnvironmentWidget::class,
                  P\NextGamemodeWidget::class, P\LogoWidget::class, P\DonationWidget::class] as $class) {
            app($class)->show($player);
        }
    }

    public function playerConnected(PlayerConnected $e): void
    {
        $player = $e->player;
        State::put('session.'.$player->login, time());
        State::put('team.'.$player->login, (int) $e->playerInfo->team_id);
        $visits = app(VisitCounter::class)->add($player);
        if (!PlayerStats::of($player)->display_widgets) {
            State::setHidden($player->login, true);
        }
        State::updateNice();

        $this->sendRaceTo($player);
        app(P\PlayerSpectatorWidget::class)->showAll();
        app(WelcomeWindow::class)->show($player);
        $this->joinMessage($player, $e->playerInfo, $visits);
        app(\Funseco\Widgets\Preloader::class)->start($player);
    }

    /** Resend what the player must see now: manialinks sent right at connect are dropped by the client. */
    public function playerReady(PlayerReady $e): void
    {
        $this->sendRaceTo($e->player);
    }

    private function joinMessage(Player $player, $info, int $visits): void
    {
        \Server::query()->GetDetailedPlayerInfo($player->login, function ($result) use ($player, $info, $visits) {
            $path = is_array($result[0] ?? null) ? (string) ($result[0]['Path'] ?? '') : '';
            $role = $player->effectiveRole();
            $title = Ui::enabled('join_leave_info', 'add_rights') && $role !== Role::Player
                ? '{#logina}'.app(Permissions::class)->title($player)
                : Text::raw('new_player');
            $rank = app(ServerRanks::class)->positionOf($player);
            Msg::send('widgets.join', $player, [
                'title'      => $title,
                'nickname'   => $player->nickname,
                'zone'       => str_replace('|', ', ', $path),
                'nation'     => self::nationName($player),
                'visits'     => (string) $visits,
                'ladderrank' => $info->ladder_ranking > 0 ? Format::number((int) $info->ladder_ranking) : 'N/A',
                'serverrank' => $rank ? $rank[0].'/'.$rank[1] : 'N/A',
            ]);
        });
    }

    public static function nationName(Player $player): string
    {
        static $names = null;
        $names ??= require __DIR__.'/../../resources/nations.php';

        return $names[$player->nation] ?? (string) $player->nation;
    }

    public function playerDisconnected(PlayerDisconnected $e): void
    {
        $player = $e->player;
        if ($player->login === '') {
            return;
        }
        app(\Funseco\Widgets\Preloader::class)->forget($player->login);
        $since = State::get('session.'.$player->login);
        foreach (['session.', 'cp.', 'team.', 'win.'] as $prefix) {
            State::forget($prefix.$player->login);
        }
        State::setHidden($player->login, false);
        State::updateNice();
        if ($since !== null) {
            Msg::send('widgets.leave', $player, [
                'nickname' => $player->nickname,
                'nation'   => self::nationName($player),
                'zone'     => self::nationName($player),
                'playtime' => Format::duration(time() - (int) $since),
            ]);
        }
    }

    /**
     * The client goes back to its default interface while a map loads (no manialink page is up). Say again which
     * parts of the game's own UI are hidden once the race is running, so its map-info panel stays away.
     */
    public function gameStatusChanged(\Funseco\Core\SharedKernel\Events\GameStatusChanged $e): void
    {
        if (!$e->isPlaying()) {
            return;
        }
        foreach (CurrentPlayers::all() as $player) {
            app(P\CustomUi::class)->send($player);
        }
    }

    public function playerInfoUpdated(PlayerInfoUpdated $e): void
    {
        State::put('team.'.$e->player->login, (int) $e->playerInfo->team_id);
        if ($e->spectatorChanged()) {
            app(P\CustomUi::class)->send($e->player); // the game's own interface settings go with the widgets
        }
        if (State::atScore() || !$e->spectatorChanged()) {
            return;
        }
        $cp = app(P\CheckpointCountWidget::class);
        $e->playerInfo->is_spectator ? $cp->hide($e->player) : $cp->showCheckpoint($e->player, -1);
    }

    // ---- driving ----

    public function checkpoint(PlayerCheckpointPassed $e): void
    {
        if (State::atScore()) {
            return;
        }
        app(P\CheckpointCountWidget::class)->showCheckpoint($e->player, $e->checkpointIndex);
        if (Gamemodes::mode() === 3) {
            $round = app(P\RoundScoreWidget::class);
            $round->checkpoint($e->player, $e->checkpointIndex + 1, $e->time);
            $round->showAll();
        }
    }

    public function finished(PlayerFinished $e): void
    {
        PlayerStats::of($e->player)->increment('finishes');
        if (State::atScore() || Gamemodes::mode() === 3) {
            return;
        }
        $round = app(P\RoundScoreWidget::class);
        $round->finished($e->player, $e->score);
        $round->showAll();
    }

    public function retired(PlayerRetired $e): void
    {
        State::atScore() || app(P\CheckpointCountWidget::class)->showCheckpoint($e->player, -1);
    }

    public function roundStarted(): void
    {
        if (State::atScore()) {
            return;
        }
        $round = app(P\RoundScoreWidget::class);
        $round->reset();
        $round->showAll();
        app(P\CheckpointCountWidget::class)->resetAll();
    }

    public function roundEnded(): void
    {
        app(P\RoundScoreWidget::class)->bankRound();
    }

    // ---- timers ----

    public function everySecond(): void
    {
        app(\Funseco\Widgets\Preloader::class)->tick();
        if (State::atScore() || !CurrentMap::get()) {
            return;
        }
        $n = (int) State::get('tick', 0) + 1;
        State::put('tick', $n);
        $refresh = max(1, (int) State::get(State::nice() ? 'hfresh' : 'lfresh', config('manialinks.features.refresh_interval', 10)));
        if ($n % $refresh === 0) {
            app(P\CurrentRankingWidget::class)->refresh();
            app(P\PlayerSpectatorWidget::class)->showAll();
        }
        if ($n % max(1, (int) config('widgets.clock_interval', 60)) === 0) {
            app(P\ClockWidget::class)->showAll();
        }
    }
}
