<?php
namespace Funseco\Widgets\Support;

use Illuminate\Support\Facades\Log;
use React\Socket\ConnectionInterface;
use React\Socket\SocketServer;

/**
 * Tiny HTTP listener that answers GET /replay/<replay id> with a ManiaCode (view_replay) and serves the
 * icons (GET /icons/replay_preview.png, replay_download.png and their _focus hover versions, discord.png), so the
 * replay icons can open a TMX replay inside the game client without any outside service.
 * Only digits are accepted for the id; the replay URL comes from funseco.replay_server.replay_url.
 */
class ReplayCodeServer
{
    private const MAX_REQUEST = 8192;

    private ?SocketServer $server = null;

    public function listen(): bool
    {
        if ($this->server) {
            return true;
        }
        if (!config('funseco.replay_server.enabled')) {
            return false;
        }
        $addr = config('funseco.replay_server.host', '127.0.0.1').':'.config('funseco.replay_server.port', 8788);
        try {
            $this->server = new SocketServer($addr);
            $this->server->on('connection', fn (ConnectionInterface $c) => $this->accept($c));
            $this->server->on('error', fn (\Throwable $e) => Log::warning('[Replay] server error: '.$e->getMessage()));
        } catch (\Throwable $e) {
            Log::warning("[Replay] ManiaCode server cannot listen on $addr: ".$e->getMessage());
            $this->server = null;

            return false;
        }
        Log::info("[Replay] ManiaCode server listening on $addr");

        return true;
    }

    public function stop(): void
    {
        $this->server?->close();
        $this->server = null;
    }

    private function accept(ConnectionInterface $conn): void
    {
        $buffer = '';
        $conn->on('error', fn () => $conn->close());
        $conn->on('data', function (string $data) use (&$buffer, $conn) {
            $buffer .= $data;
            if (strlen($buffer) > self::MAX_REQUEST) {
                $conn->end(self::response(414, 'text/plain', 'Too long'));

                return;
            }
            if (!str_contains($buffer, "\r\n\r\n") && !str_contains($buffer, "\n\n")) {
                return;
            }
            $conn->end(self::reply(strtok($buffer, "\r\n") ?: ''));
        });
    }

    /** Full HTTP response for a request line like "GET /replay/55 HTTP/1.1". */
    public static function reply(string $requestLine): string
    {
        if (preg_match('#^GET /icons/(replay_(?:preview|download)(?:_focus)?|discord)\.png HTTP/1\.[01]$#', $requestLine, $m)) {
            $png = (string) file_get_contents(__DIR__.'/../../resources/'.$m[1].'.png');

            return self::response(200, 'image/png', $png);
        }
        if (!preg_match('#^GET /replay/(\d{1,10})(?:\?\S*)? HTTP/1\.[01]$#', $requestLine, $m)) {
            return self::response(404, 'text/plain', 'Not found');
        }

        return self::response(200, 'application/xml', self::maniacode($m[1]));
    }

    public static function maniacode(string $replayId): string
    {
        $url = str_replace('{replay_id}', $replayId, (string) config('funseco.replay_server.replay_url'));

        return '<?xml version="1.0" encoding="utf-8"?>'."\n"
            .'<maniacode noconfirmation="1"><view_replay><name>Replay-'.$replayId.'</name><url>'
            .htmlspecialchars($url, ENT_XML1).'</url></view_replay></maniacode>';
    }

    private static function response(int $code, string $type, string $body): string
    {
        $text = [200 => 'OK', 404 => 'Not Found', 414 => 'URI Too Long'][$code] ?? 'Error';

        return "HTTP/1.1 $code $text\r\nContent-Type: $type\r\nContent-Length: ".strlen($body)."\r\nConnection: close\r\n\r\n".$body;
    }
}
