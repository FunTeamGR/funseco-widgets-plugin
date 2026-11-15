<?php
namespace Funseco\Widgets\Support;

/** Environment names, their image files and the owner's image geometry (spec 7.1, 8.2, 8.4). */
final class Environments
{
    /** Canonical key => display name, in window order. */
    public const NAMES = ['stadium' => 'Stadium', 'bay' => 'Bay', 'coast' => 'Coast', 'desert' => 'Desert', 'island' => 'Island', 'rally' => 'Rally', 'snow' => 'Alpine'];
    public const MOODS = ['sunrise' => 'Sunrise', 'day' => 'Day', 'sunset' => 'Sunset', 'night' => 'Night'];

    /** key => [posn x, y, sizen w, h] for the "next environment" status bar */
    private const NEXT = [
        'stadium' => [0.7, -1, 3.3, 2.156], 'bay' => [1.29, -0.6, 1.76, 2.97], 'coast' => [1.45, -0.6, 1.738, 2.97],
        'desert' => [1.33, -0.6, 1.837, 2.97], 'island' => [1.03, -0.6, 2.409, 2.97], 'rally' => [1.33, -0.6, 1.925, 2.97],
        'snow' => [1.23, -0.6, 2.112, 2.97],
    ];
    /** key => geometry in a track card */
    private const CARD = [
        'stadium' => [0.7, -0.35, 3, 1.96], 'bay' => [1.35, 0.1, 1.60, 2.7], 'coast' => [1.45, 0.1, 1.58, 2.7],
        'desert' => [1.35, 0.1, 1.67, 2.7], 'island' => [1.05, 0.1, 2.19, 2.7], 'rally' => [1.35, 0.1, 1.75, 2.7],
        'snow' => [1.25, 0.1, 1.92, 2.7],
    ];
    /** key => [image x, w, label x] in the filter window's environment card */
    public const FILTER = [
        'stadium' => [1.6, 5.4, 2.4], 'bay' => [11.2, 2.88, 11.9], 'coast' => [18.9, 2.84, 18.9], 'desert' => [26.6, 3, 26.7],
        'island' => [34.3, 3.94, 35.1], 'rally' => [42.3, 3.15, 43.1], 'snow' => [50.4, 3.46, 51],
    ];

    /** "Speed" is desert, "Alpine" is snow; null for unknown names. */
    public static function key(?string $env): ?string
    {
        $e = strtolower(trim((string) $env));
        $e = ['speed' => 'desert', 'alpine' => 'snow'][$e] ?? $e;

        return isset(self::NAMES[$e]) ? $e : null;
    }

    public static function image(string $kind, string $key, string $state): ?string
    {
        return Ui::image($kind, $key, $state);
    }

    /** Image quad of the next-environment widget, '' when unknown or no image set. */
    public static function nextQuad(?string $env): string
    {
        $key = self::key($env);
        $img = $key ? self::image('environment', $key, 'enabled') : null;
        if (!$img) {
            return '';
        }
        [$x, $y, $w, $h] = self::NEXT[$key];

        return Ui::quad("$x $y 0.06", "$w $h", 'image="'.Ui::esc($img).'"');
    }

    public static function cardQuad(?string $env): string
    {
        $key = self::key($env);
        $img = $key ? self::image('environment', $key, 'enabled') : null;
        if (!$img) {
            return '';
        }
        [$x, $y, $w, $h] = self::CARD[$key];

        return Ui::quad("$x $y 0.06", "$w $h", 'image="'.Ui::esc($img).'"');
    }
}
