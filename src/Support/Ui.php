<?php
namespace Funseco\Widgets\Support;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\ManiaLink\Facades\ManiaLink;

/** Small XML builders and style lookups shared by all widgets and windows. */
final class Ui
{
    public static function esc(?string $text): string
    {
        return htmlspecialchars((string) $text, ENT_QUOTES);
    }

    public static function bool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /** config/manialinks.php value of one widget, e.g. cfg('gamemode_widget', 'pos_x'). */
    public static function cfg(string $widget, string $key, mixed $default = null): mixed
    {
        return config("manialinks.$widget.$key", $default);
    }

    public static function enabled(string $widget, string $key = 'enabled'): bool
    {
        return self::bool(self::cfg($widget, $key, 'true'));
    }

    /** @return array<string, mixed> widget_race or widget_score style */
    public static function style(string $kind): array
    {
        return (array) config('manialinks.style.widget_'.$kind, []);
    }

    public static function colour(string $kind, string $name): string
    {
        return (string) config("manialinks.style.widget_$kind.colors.$name", 'FFFF');
    }

    /** Prefix of every score-widget / race text (the "formatting codes" option). */
    public static function codes(string $kind): string
    {
        return (string) config("manialinks.style.widget_$kind.formatting_codes", '');
    }

    public static function window(string $key): string
    {
        return (string) config("manialinks.style.window.$key", '');
    }

    /** Image URL from widgets.images (base_url + file), or null without a base URL. */
    public static function image(string $key, string ...$args): ?string
    {
        $base = config('widgets.images.base_url');
        $file = config("widgets.images.files.$key");
        if (!$base || !$file) {
            return null;
        }

        return rtrim($base, '/').'/'.($args ? sprintf($file, ...$args) : $file);
    }

    public static function imageAttr(string $key, string $attr = 'image', string ...$args): string
    {
        $url = self::image($key, ...$args);

        return $url ? ' '.$attr.'="'.self::esc($url).'"' : '';
    }

    public static function quad(string $pos, string $size, string $attrs = ''): string
    {
        return '<quad posn="'.$pos.'" sizen="'.$size.'" '.$attrs.'/>';
    }

    public static function styled(string $pos, string $size, string $style, string $substyle, string $extra = ''): string
    {
        return '<quad posn="'.$pos.'" sizen="'.$size.'" style="'.$style.'" substyle="'.$substyle.'"'.($extra ? ' '.$extra : '').'/>';
    }

    public static function label(string $pos, string $size, string $text, string $attrs = ''): string
    {
        return '<label posn="'.$pos.'" '.($size !== '' ? 'sizen="'.$size.'" ' : '').$attrs.' text="'.self::esc($text).'"/>';
    }

    public static function manialink(string $name, string $inner): string
    {
        return '<manialink id="'.Ids::ml($name).'">'.$inner.'</manialink>';
    }

    public static function clear(string $name): string
    {
        return '<manialink id="'.Ids::ml($name).'"></manialink>';
    }

    public static function sendAll(string $xml): void
    {
        ManiaLink::displayAll($xml);
    }

    public static function sendTo(Player $player, string $xml): void
    {
        ManiaLink::displayLogin($player, $xml);
    }

    /** Strip colour codes, keep it XML-safe. */
    public static function plain(?string $text): string
    {
        return Format::stripColors($text);
    }
}
