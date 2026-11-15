<?php
namespace Funseco\Widgets\Support;

use Funseco\Core\Models\Player;

/** Chat messages: widgets.messages first, then the eyepiece texts in manialinks.messages. */
final class Text
{
    public static function get(string $key, string|int ...$values): string
    {
        $text = config("widgets.messages.$key") ?? config("manialinks.messages.$key", '');
        $text = format_colors($text);   // colour tokens first: values must not expand {#..}
        $map = [];
        foreach ($values as $i => $value) {
            $map['{'.($i + 1).'}'] = (string) $value;
        }

        return strtr($text, $map);
    }

    /** The configured text with its colour tokens still unresolved. */
    public static function raw(string $key): string
    {
        return (string) (config("widgets.messages.$key") ?? config("manialinks.messages.$key", ''));
    }

    public static function tell(Player $player, string $message): void
    {
        \Server::query()->ChatSendServerMessageToLogin($message, $player->login);
    }

    public static function broadcast(string $message): void
    {
        \Server::query()->ChatSendServerMessage($message);
    }
}
