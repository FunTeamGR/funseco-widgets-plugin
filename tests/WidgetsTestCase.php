<?php
namespace Funseco\Widgets\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Funseco\Core\Testing\FakesServer;
use Funseco\Core\Models\Map;
use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentMap;
use Funseco\Core\Services\Cache\CurrentPlayers;
use Funseco\Core\Services\Cache\CurrentSpectators;
use Funseco\Core\Services\Cache\ServerInfo;
use Funseco\Widgets\Support\Ids;

/** Shared setup: a fake server, two players, a current map. */
abstract class WidgetsTestCase extends TestCase
{
    use FakesServer;
    use RefreshDatabase;

    protected Player $alice;
    protected Player $bob;
    protected Map $map;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeServer();
        CurrentPlayers::clear();
        CurrentSpectators::clear();
        $this->alice = Player::factory()->create(['login' => 'alice', 'nickname' => '$f00Alice', 'nation' => 'GER']);
        $this->bob = Player::factory()->create(['login' => 'bob', 'nickname' => 'Bob', 'nation' => 'FRA']);
        CurrentPlayers::add($this->alice);
        CurrentPlayers::add($this->bob);
        $this->map = Map::factory()->create(['uid' => 'uid-1', 'name' => '$fffSpeed', 'author' => 'nadeo', 'environment' => 'Stadium',
            'mood' => 'Day', 'author_time' => 31000, 'gold_time' => 33000, 'silver_time' => 36000, 'bronze_time' => 40000, 'cp_count' => 4]);
        CurrentMap::set($this->map);
        ServerInfo::set(['server_name' => 'My $f00Server', 'max_players' => 20, 'max_spectators' => 10]);
        ServerInfo::setGameMode(1);
    }

    /** Everything sent as a manialink, joined (to all and to one login). */
    protected function xml(): string
    {
        return implode("\n", array_merge(
            array_map(fn ($a) => (string) $a[0], $this->sent('SendDisplayManialinkPage')),
            array_map(fn ($a) => (string) ($a[1] ?? ''), $this->sent('SendDisplayManialinkPageToLogin')),
        ));
    }

    /** Manialink XML sent to one login. */
    protected function xmlTo(string $login): string
    {
        return implode("\n", array_map(fn ($a) => (string) $a[1], array_filter($this->sent('SendDisplayManialinkPageToLogin'), fn ($a) => $a[0] === $login)));
    }

    protected function forgetSent(): void
    {
        $this->calls = [];
    }

    /** Does a manialink with this widget id carry content (not just the empty reset)? */
    protected function shown(string $name, ?string $xml = null): bool
    {
        $xml ??= $this->xml();

        return (bool) preg_match('#<manialink id="'.Ids::ml($name).'">(?!</manialink>)#', $xml);
    }

    protected function cleared(string $name, ?string $xml = null): bool
    {
        return str_contains($xml ?? $this->xml(), '<manialink id="'.Ids::ml($name).'"></manialink>');
    }
}
