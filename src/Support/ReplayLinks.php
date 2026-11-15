<?php

namespace Funseco\Widgets\Support;

use Funseco\Core\SharedKernel\Values\TmxRecord;
use Funseco\Core\SharedKernel\Values\TmxTrack;

/**
 * Replay icons for a track that is on TMX: watch the world record replay in the game client
 * (downloaded and opened by the client) and, only when funseco.replay_preview_url is set, open a
 * preview page in the browser. Nothing is drawn for a track without a TMX replay.
 */
final class ReplayLinks
{
    public static function available(?TmxTrack $tmx): bool
    {
        return $tmx !== null && $tmx->replayUrl !== null && $tmx->replayUrl !== '';
    }

    /**
     * Link the game client can follow: http, and the legacy TMX host (trakman links replays as
     * http://tmnforever.tm-exchange.com/recordgbx/<id>, which the client handles as a download).
     */
    public static function clientUrl(string $url): string
    {
        $url = preg_replace('#^https?://#i', '', $url);
        $url = preg_replace('#^tmnf\.exchange/#i', 'tmnforever.tm-exchange.com/', $url);
        $url = preg_replace('#^tmuf\.exchange/#i', 'united.tm-exchange.com/', $url);

        return 'http://'.$url;
    }

    /** ManiaCode endpoint that opens the replay in the client (funseco.replay_watch_url), null when unset. */
    public static function watchUrl(TmxTrack $tmx, ?TmxRecord $record = null): ?string
    {
        $template = trim((string) config('funseco.replay_watch_url', ''));
        if ($template === '' && config('funseco.replay_server.enabled')) {
            $template = rtrim((string) config('funseco.replay_server.public_url'), '/').'/replay/{replay_id}';
        }

        return $template === '' || !($record ? self::recordAvailable($record) : self::available($tmx)) ? null : self::fill($template, $tmx, $record);
    }

    /** A given record (not just the world record) of the track has a replay. */
    private static function recordAvailable(TmxRecord $record): bool
    {
        return $record->replayId > 0 || ($record->replayUrl !== null && $record->replayUrl !== '');
    }

    private static function fill(string $template, TmxTrack $tmx, ?TmxRecord $record = null): string
    {
        if ($record !== null) {
            $replayId = (string) $record->replayId;
        } else {
            preg_match('/(\d+)\D*$/', (string) $tmx->replayUrl, $m);
            $replayId = $m[1] ?? '';
        }

        return strtr($template, ['{id}' => (string) $tmx->id, '{tmx_id}' => (string) $tmx->id, '{uid}' => $tmx->uid, '{replay_id}' => $replayId]);
    }

    /**
     * Preview page URL, or null when none is configured. {id} {tmx_id} {uid} {replay_id}.
     * Without a record (the track's own icons) the {replay_id} part is dropped, so the page shows the
     * track; only a given record (the top-10 rows) links its own replay.
     */
    public static function previewUrl(TmxTrack $tmx, ?TmxRecord $record = null): ?string
    {
        $template = trim((string) config('funseco.replay_preview_url', ''));
        if ($template === '' || !($record ? self::recordAvailable($record) : self::available($tmx))) {
            return null;
        }

        if ($record === null) {
            $template = preg_replace('#(?:/|[?&][^/?&=]*=)\{replay_id\}#', '', $template);
        }

        return self::fill($template, $tmx, $record);
    }

    /** Icon image URL (the client needs http): the icons host, else the built-in listener; null when neither is set. */
    public static function iconUrl(string $name): ?string
    {
        if ($base = (string) config('funseco.icons_url', '')) {
            return rtrim($base, '/').'/'.$name.'.png';
        }

        return config('funseco.replay_server.enabled')
            ? rtrim((string) config('funseco.replay_server.public_url'), '/').'/icons/'.$name.'.png'
            : null;
    }

    /** One icon: the image with a gold hover version, or the built-in style icon when there is no listener. */
    private static function icon(string $name, array $fallback, string $pos, string $size, string $link): string
    {
        $image = self::iconUrl($name);

        return $image === null
            ? StatusBar::icon($fallback[0], $fallback[1], $pos, $size, $link)
            : Ui::quad($pos, $size, 'image="'.Ui::esc($image).'" imagefocus="'.Ui::esc((string) self::iconUrl($name.'_focus')).'" '.$link);
    }

    /**
     * Icons right-aligned at $right (x of the right edge), row at $y.
     *
     * @return string quads ('' when there is nothing to show)
     */
    public static function icons(?TmxTrack $tmx, float $right, float $y, float $size = 1.7, float $z = 0.05): string
    {
        if (! self::available($tmx)) {
            return '';
        }
        return self::draw($right, $y, $size, $z, self::watchUrl($tmx), self::clientUrl((string) $tmx->replayUrl), self::previewUrl($tmx));
    }

    /** The same two icons for one record of the track's top 10 (its own replay id in the links). */
    public static function recordIcons(TmxTrack $tmx, TmxRecord $record, float $right, float $y, float $size = 1.5, float $z = 0.05): string
    {
        if (!self::recordAvailable($record)) {
            return '';
        }
        $download = $record->replayUrl !== null && $record->replayUrl !== ''
            ? self::clientUrl($record->replayUrl)
            : 'http://tmnforever.tm-exchange.com/recordgbx/'.$record->replayId;

        return self::draw($right, $y, $size, $z, self::watchUrl($tmx, $record), $download, self::previewUrl($tmx, $record));
    }

    private static function draw(float $right, float $y, float $size, float $z, ?string $watch, string $download, ?string $preview): string
    {
        $x = $right - $size;
        $link = $watch !== null ? 'manialink="'.Ui::esc($watch).'"' : 'url="'.Ui::esc($download).'"';
        $xml = self::icon('replay_download', ['Icons128x128_1', 'Load'], "$x $y $z", "$size $size", $link);
        if ($preview !== null) {
            $x -= $size + 0.3;
            $xml .= self::icon('replay_preview', ['Icons64x64_1', 'ClipPlay'], "$x $y $z", "$size $size", 'url="'.Ui::esc($preview).'"');
        }

        return $xml;
    }
}
