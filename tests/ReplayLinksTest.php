<?php
namespace Funseco\Widgets\Tests;

use Funseco\Core\SharedKernel\Values\TmxTrack;
use Funseco\Widgets\Support\ReplayLinks;

class ReplayLinksTest extends WidgetsTestCase
{
    private function track(?string $replay): TmxTrack
    {
        return new TmxTrack(7, 'uid-1', 'T', 'a', null, null, 'Race', 'Fullspeed', 'Stadium', 'Day', 'Easy', 'Single', 'Short',
            3, '', 'TMNF', '', 31000, 'http://t/7', 'http://t/7.jpg', 'http://t/dl', $replay);
    }

    public function test_nothing_without_tmx_or_replay(): void
    {
        $this->assertSame('', ReplayLinks::icons(null, 10, -1));
        $this->assertSame('', ReplayLinks::icons($this->track(null), 10, -1));
    }

    public function test_download_icon_only_when_no_preview_configured(): void
    {
        config(['funseco.replay_preview_url' => '', 'funseco.replay_watch_url' => '']);
        $xml = ReplayLinks::icons($this->track('https://tmnf.exchange/recordgbx/55'), 10, -1);
        $this->assertStringContainsString('url="http://tmnforever.tm-exchange.com/recordgbx/55"', $xml);
        $this->assertSame(1, substr_count($xml, '<quad'));
    }

    public function test_preview_icon_when_configured(): void
    {
        config(['funseco.replay_preview_url' => 'http://127.0.0.1:8787/t/{tmx_id}?r={replay_id}']);
        $xml = ReplayLinks::icons($this->track('https://tmnf.exchange/recordgbx/55'), 10, -1);
        $this->assertStringContainsString('url="http://127.0.0.1:8787/t/7"', $xml);
    }

    public function test_watch_endpoint_opens_in_client(): void
    {
        config(['funseco.replay_watch_url' => 'http://w.example/tmnf/replay/{replay_id}', 'funseco.replay_preview_url' => '']);
        $xml = ReplayLinks::icons($this->track('https://tmnf.exchange/recordgbx/55'), 10, -1);
        $this->assertStringContainsString('manialink="http://w.example/tmnf/replay/55"', $xml);
        $this->assertStringNotContainsString('url=', $xml);
    }

    public function test_built_in_server_is_the_default_watch_link(): void
    {
        config(['funseco.replay_watch_url' => '', 'funseco.replay_preview_url' => '',
            'funseco.replay_server.enabled' => true, 'funseco.replay_server.public_url' => 'http://203.0.113.5:8788/']);
        $xml = ReplayLinks::icons($this->track('https://tmnf.exchange/recordgbx/55'), 10, -1);
        $this->assertStringContainsString('manialink="http://203.0.113.5:8788/replay/55"', $xml);
    }

    public function test_server_replies_with_a_maniacode(): void
    {
        config(['funseco.replay_server.replay_url' => 'http://tmnforever.tm-exchange.com/recordgbx/{replay_id}']);
        $ok = \Funseco\Widgets\Support\ReplayCodeServer::reply('GET /replay/55 HTTP/1.1');
        $this->assertStringStartsWith('HTTP/1.1 200 OK', $ok);
        $this->assertStringContainsString('<view_replay><name>Replay-55</name><url>http://tmnforever.tm-exchange.com/recordgbx/55</url>', $ok);
        foreach (['GET /replay/5x HTTP/1.1', 'GET /other HTTP/1.1', 'POST /replay/5 HTTP/1.1', 'GET /replay/../../x HTTP/1.1'] as $bad) {
            $this->assertStringStartsWith('HTTP/1.1 404', \Funseco\Widgets\Support\ReplayCodeServer::reply($bad));
        }
    }

    public function test_preview_icon_is_an_image_from_the_built_in_server(): void
    {
        config(['funseco.replay_watch_url' => '', 'funseco.replay_preview_url' => 'http://p/{tmx_id}',
            'funseco.replay_server.enabled' => true, 'funseco.replay_server.public_url' => 'http://203.0.113.5:8788']);
        $xml = ReplayLinks::icons($this->track('https://tmnf.exchange/recordgbx/55'), 10, -1);
        $this->assertStringContainsString('image="http://203.0.113.5:8788/icons/replay_preview.png" imagefocus="http://203.0.113.5:8788/icons/replay_preview_focus.png" url="http://p/7"', $xml);
        $png = \Funseco\Widgets\Support\ReplayCodeServer::reply('GET /icons/replay_preview_focus.png HTTP/1.1');
        $this->assertStringStartsWith('HTTP/1.1 200 OK', $png);
        $this->assertStringContainsString("\x89PNG", $png);
    }

    public function test_icon_route_only_serves_known_files(): void
    {
        $this->assertStringStartsWith('HTTP/1.1 200', \Funseco\Widgets\Support\ReplayCodeServer::reply('GET /icons/replay_download.png HTTP/1.1'));
        $this->assertStringStartsWith('HTTP/1.1 404', \Funseco\Widgets\Support\ReplayCodeServer::reply('GET /icons/../../.env HTTP/1.1'));
        $this->assertStringStartsWith('HTTP/1.1 404', \Funseco\Widgets\Support\ReplayCodeServer::reply('GET /icons/replay_x.png HTTP/1.1'));
    }

    public function test_each_top_ten_record_links_its_own_replay(): void
    {
        config(['funseco.replay_watch_url' => '', 'funseco.replay_server.enabled' => false,
            'funseco.replay_preview_url' => 'http://var.example/{tmx_id}/{replay_id}']);
        $record = new \Funseco\Core\SharedKernel\Values\TmxRecord(3, 4242, 'Bob', 31500, 0, null, null, 'https://tmnf.exchange/recordgbx/4242');

        $xml = ReplayLinks::recordIcons($this->track('https://tmnf.exchange/recordgbx/55'), $record, 20, -1);

        $this->assertStringContainsString('url="http://tmnforever.tm-exchange.com/recordgbx/4242"', $xml);
        $this->assertStringContainsString('url="http://var.example/7/4242"', $xml);   // map id, then the record's replay id
        $this->assertStringNotContainsString('/55', $xml);                              // not the world record's
    }

    public function test_a_record_without_a_replay_has_no_icons(): void
    {
        $record = new \Funseco\Core\SharedKernel\Values\TmxRecord(3, 0, 'Bob', 31500, 0, null, null, null);

        $this->assertSame('', ReplayLinks::recordIcons($this->track(null), $record, 20, -1));
    }

    public function test_track_info_window_links_are_plain_http_for_the_game(): void
    {
        $tmx = new TmxTrack(7, 'uid-1', 'T', 'a', null, null, 'Race', 'Fullspeed', 'Stadium', 'Day', 'Easy', 'Single', 'Short',
            3, '', 'TMNF', '', 31000, 'https://tmnf.exchange/trackshow/7', 'http://t/7.jpg', 'https://tmnf.exchange/trackgbx/7', 'https://tmnf.exchange/recordgbx/55');
        $links = new \ReflectionMethod(\Funseco\Widgets\Windows\TmxInfoWindow::class, 'links');
        $xml = $links->invoke(new \Funseco\Widgets\Windows\TmxInfoWindow(), $tmx);

        $this->assertStringNotContainsString('https://', $xml);
        $this->assertStringContainsString('url="http://tmnforever.tm-exchange.com/trackshow/7"', $xml);
        $this->assertStringContainsString('url="http://tmnforever.tm-exchange.com/recordgbx/55"', $xml);
    }

    public function test_icons_come_from_the_icons_host_when_one_is_set(): void
    {
        config(['funseco.icons_url' => 'http://cdn.example.org/icons/', 'funseco.replay_server.enabled' => false, 'funseco.replay_preview_url' => '']);

        $this->assertSame('http://cdn.example.org/icons/replay_download.png', ReplayLinks::iconUrl('replay_download'));
        $this->assertStringContainsString('image="http://cdn.example.org/icons/replay_download.png"', ReplayLinks::icons($this->track('https://tmnf.exchange/recordgbx/55'), 10, -1));

        config(['funseco.icons_url' => '']);
        $this->assertNull(ReplayLinks::iconUrl('replay_download'));
    }
}
