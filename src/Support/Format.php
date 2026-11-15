<?php
namespace Funseco\Widgets\Support;

/** Time and number formatting of the widgets and windows (spec 1.4). */
final class Format
{
    /** m:ss.hh (h:mm:ss.hh past an hour); -1 is ???; stunts scores stay raw. */
    public static function time(?int $ms, bool $stunts = false): string
    {
        if ($ms === null) {
            return self::unknown($stunts);
        }
        if ($stunts) {
            return (string) $ms;
        }
        if ($ms === -1) {
            return '???';
        }
        $hundredths = intdiv($ms % 1000, 10);
        $seconds = intdiv($ms, 1000);
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;

        return $h > 0
            ? sprintf('%d:%02d:%02d.%02d', $h, $m, $s, $hundredths)
            : sprintf('%d:%02d.%02d', $m, $s, $hundredths);
    }

    /** m:ss or h:mm:ss (gamemode limits). */
    public static function short(int $ms): string
    {
        $seconds = intdiv(max(0, $ms), 1000);
        $h = intdiv($seconds, 3600);

        return $h > 0
            ? sprintf('%d:%02d:%02d', $h, intdiv($seconds % 3600, 60), $seconds % 60)
            : sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    }

    public static function unknown(bool $stunts = false): string
    {
        return $stunts ? '---' : '-:--.---';
    }

    /** features.number_format (english 1,234 / german 1.234 / french 1 234), optionally 21k. */
    public static function number(int $n): string
    {
        if (filter_var(config('manialinks.features.shorten_numbers'), FILTER_VALIDATE_BOOLEAN) && $n > 1000) {
            return intdiv($n, 1000).'k';
        }

        return match (config('manialinks.features.number_format', 'english')) {
            'german' => number_format($n, 0, ',', '.'),
            'french' => number_format($n, 0, ',', ' '),
            default  => number_format($n),
        };
    }

    /** HH:MM:SS of a duration in seconds. */
    public static function duration(int $seconds): string
    {
        $seconds = max(0, $seconds);

        return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
    }

    public static function stripColors(?string $text): string
    {
        $text = str_replace('$$', "\0", (string) $text);
        $text = preg_replace('/\$(?:[0-9a-f][^$]{0,2}|[lh]\[[^\]]*\]|[^$]?)/i', '', $text);

        return str_replace("\0", '$', $text);
    }
}
