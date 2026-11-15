<?php

namespace Funseco\Widgets\Panels;

use Illuminate\Support\Facades\Log;
use Funseco\Core\Models\Map;
use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\TrackExchange;
use Funseco\Core\SharedKernel\UI\WidgetRegistry;
use Funseco\Widgets\Support\Format;
use Funseco\Widgets\Support\Ids;
use Funseco\Widgets\Support\Ui;

/**
 * Free widgets from widgets.placements (spec 17): image/icon/text blocks with a link. Each
 * placement is its own manialink. always: sent on connect; race/<gamemode>: during the race;
 * score: built at score.
 */
class PlacementWidgets
{
    private const SCORE_ONLY = ['%TRACK_TMX_PREFIX%', '%TRACK_TMX_ID%', '%TRACK_TMX_PAGEURL%', '%TRACK_UID%', '%TRACK_NAME%'];

    /** @var array<int, array>|null */
    private ?array $valid = null;

    public function enabled(): bool
    {
        return Ui::enabled('placement_widget') && $this->placements() !== [];
    }

    /** @return array<int, array> valid placements by index */
    public function placements(): array
    {
        if ($this->valid !== null) {
            return $this->valid;
        }
        $valid = [];
        $chat = 0;
        foreach ((array) config('widgets.placements', []) as $i => $p) {
            if (! Ui::bool($p['enabled'] ?? true)) {
                continue;
            }
            // 'source' => 'funseco.discord': image, imagefocus and url come from that config array (env-driven).
            if (isset($p['source'])) {
                $src = (array) config((string) $p['source']);
                if (array_key_exists('url', $src) && trim((string) $src['url']) === '') {
                    continue; // source declares a url but none is set: hide the button
                }
                $p = array_filter($src, fn ($v) => $v !== null && $v !== '') + $p;
            }
            $display = strtolower((string) ($p['display'] ?? ''));
            $ok = $display !== '' && (isset($p['include']) && is_readable((string) $p['include'])
                || (isset($p['pos_x'], $p['pos_y'], $p['width'], $p['height'])));
            if (! $ok || $i >= Ids::MAX_PLACEMENTS) {
                Log::info('[Widgets] placement #'.$i.' skipped: incomplete or unreadable');

                continue;
            }
            $linked = empty($p['url']) && empty($p['manialink']) && empty($p['action_id']) && ! empty($p['chat_command']);
            if ($linked && ++$chat > Ids::MAX_PLACEMENTS) {
                continue;
            }
            $valid[$i] = ['display' => $display] + $p;
        }

        return $this->valid = $valid;
    }

    public function forget(): void
    {
        $this->valid = null;
    }

    /** Current gamemode config key (rounds, time_attack …). */
    private function mode(): string
    {
        return app(WidgetRegistry::class)->gamemode();
    }

    /** Placement indexes shown during the race. */
    private function raceIndexes(): array
    {
        return array_keys(array_filter($this->placements(), fn ($p) => in_array($p['display'], ['race', $this->mode()], true)));
    }

    public function sendAlways(Player $player): void
    {
        $this->send($player, array_keys(array_filter($this->placements(), fn ($p) => $p['display'] === 'always')));
    }

    /** Race (and current gamemode) placements to one player, or everybody. */
    public function sendRace(?Player $player = null): void
    {
        $this->send($player, $this->raceIndexes());
    }

    /** Score placements to everybody; $map fills the placeholders. */
    public function sendScore(?Map $map): void
    {
        $this->send(null, array_keys(array_filter($this->placements(), fn ($p) => $p['display'] === 'score')), $map);
    }

    public function clearRace(): void
    {
        foreach ($this->raceIndexes() as $i) {
            Ui::sendAll(Ui::clear('placement_'.$i));
        }
    }

    public function clearScore(): void
    {
        foreach ($this->placements() as $i => $p) {
            if ($p['display'] === 'score') {
                Ui::sendAll(Ui::clear('placement_'.$i));
            }
        }
    }

    /** Slash command of a clicked chat-command placement, or null. */
    public function chatCommand(int $index): ?array
    {
        $p = $this->placements()[$index] ?? null;
        if (! $p || empty($p['chat_command']) || $this->linkOf($p) !== null) {
            return null;
        }
        $parts = preg_split('/\s+/', trim(ltrim((string) $p['chat_command'], '/')));

        return [array_shift($parts), $parts];
    }

    private function send(?Player $player, array $indexes, ?Map $map = null): void
    {
        foreach ($indexes as $i) {
            $xml = $this->render($i, $this->placements()[$i], $map);
            if ($xml === null) {
                continue;
            }
            $player ? Ui::sendTo($player, $xml) : Ui::sendAll($xml);
        }
    }

    /** Link attribute: url, else manialink, else action_id, else chat_command. */
    private function linkOf(array $p, ?Map $map = null): ?string
    {
        $score = $p['display'] === 'score';
        $fill = fn (string $v) => $this->fill($v, $score ? $map : null);
        if (! empty($p['url'])) {
            return ($v = $fill($p['url'])) === null ? '' : ' url="'.Ui::esc($v).'"';
        }
        if (! empty($p['manialink'])) {
            return ($v = $fill($p['manialink'])) === null ? '' : ' manialink="'.Ui::esc($v).'"';
        }
        if (! empty($p['action_id'])) {
            return ' action="'.(int) $p['action_id'].'"';
        }

        return null;
    }

    /** Fill score placeholders; null when a TMX placeholder has no TMX track. Others lose them. */
    private function fill(string $value, ?Map $map): ?string
    {
        if ($map === null) {
            return str_replace(self::SCORE_ONLY, '', $value);
        }
        $tmx = app(TrackExchange::class)->current();
        if (! $tmx && preg_match('/%TRACK_TMX_/', $value)) {
            return null;
        }

        return strtr($value, [
            '%TRACK_TMX_PREFIX%' => strtolower((string) ($tmx?->section ?? '')),
            '%TRACK_TMX_ID%' => (string) ($tmx?->id ?? ''),
            '%TRACK_TMX_PAGEURL%' => $tmx?->pageUrl ? \Funseco\Widgets\Support\ReplayLinks::clientUrl($tmx->pageUrl) : '',
            '%TRACK_UID%' => (string) $map->uid,
            '%TRACK_NAME%' => rawurlencode(Format::stripColors((string) $map->name)),
        ]);
    }

    private function render(int $i, array $p, ?Map $map): ?string
    {
        if (! empty($p['include'])) {
            return Ui::manialink('placement_'.$i, str_replace(["\t", "\r", "\n"], '', (string) file_get_contents($p['include'])));
        }
        $link = $this->linkOf($p, $map);
        if ($link === '') {
            return null;   // TMX placeholder without a TMX track
        }
        if ($link === null && ! empty($p['chat_command'])) {
            $link = ' action="'.Ids::action('chat_'.$i).'"';
        }
        $link ??= '';
        $z = max(-3, min(20, (float) ($p['layer'] ?? 3)));
        $pos = fn (float $dz) => '0 0 '.($z + $dz);
        $align = (isset($p['halign']) ? ' halign="'.Ui::esc($p['halign']).'"' : '').(isset($p['valign']) ? ' valign="'.Ui::esc($p['valign']).'"' : '');
        $size = (float) $p['width'].' '.(float) $p['height'];
        $inner = '';
        if (! empty($p['background_style'])) {
            $inner .= Ui::styled($pos(0.001), $size, $p['background_style'], (string) ($p['background_substyle'] ?? ''), ltrim($link));
        }
        if (! empty($p['image'])) {
            $inner .= Ui::quad($pos(0.002), $size, 'image="'.Ui::esc($p['image']).'"'
                .(! empty($p['imagefocus']) ? ' imagefocus="'.Ui::esc($p['imagefocus']).'"' : '').$align.$link);
        }
        if (! empty($p['icon_style'])) {
            $inner .= Ui::styled($pos(0.003), $size, $p['icon_style'], (string) ($p['icon_substyle'] ?? ''), ltrim($align.$link));
        }
        if ($inner === '' && trim($link) !== '') {
            // text only: a transparent quad over the area carries the link
            $inner .= Ui::quad($pos(0.001), $size, 'bgcolor="0000"'.$link);
        }
        if (isset($p['text']) && $p['text'] !== '') {
            $inner .= Ui::label($pos(0.004), '', (string) $p['text'], trim($align).' textsize="'.(int) ($p['textsize'] ?? 1).'" scale="'.(float) ($p['textscale'] ?? 1).'"');
        }

        $x = (float) $p['pos_x'];
        // align_x: take the x of another widget (e.g. the button above), falling back to pos_x while it is hidden.
        if (!empty($p['align_x']) && $above = app(WidgetRegistry::class)->placement((string) $p['align_x'])) {
            $x = (float) $above->x;
        }

        return Ui::manialink('placement_'.$i, '<frame posn="'.$x.' '.(float) $p['pos_y'].' 0">'.$inner.'</frame>');
    }
}
