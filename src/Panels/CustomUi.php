<?php
namespace Funseco\Widgets\Panels;

use Funseco\Core\Models\Player;
use Funseco\Widgets\Support\Ids;
use Funseco\Widgets\Support\Ui;

/** Hides parts of the game's own interface that widgets replace (spec 19). */
class CustomUi
{
    private const PARTS = ['notice', 'challenge_info', 'net_infos', 'chat', 'checkpoint_list', 'round_scores', 'scoretable'];

    public function enabled(): bool
    {
        return Ui::enabled('custom_ui');
    }

    /** @return array<string, bool> part => visible */
    public function parts(): array
    {
        $parts = [];
        foreach (self::PARTS as $part) {
            $parts[$part] = Ui::bool(Ui::cfg('custom_ui', $part, 'true'));
        }
        if (Ui::enabled('challenge_widget')) {
            $parts['challenge_info'] = false;
        }
        $parts['round_scores'] = $parts['round_scores'] && !$this->roundScoreWidget();

        return $parts;
    }

    private function roundScoreWidget(): bool
    {
        foreach (['rounds', 'team', 'cup'] as $mode) {
            if (Ui::bool(config("manialinks.round_score.gamemode.$mode.enabled", 'false'))) {
                return true;
            }
        }

        return false;
    }

    public function xml(): string
    {
        $xml = '<custom_ui>';
        foreach ($this->parts() as $part => $visible) {
            $xml .= '<'.$part.' visible="'.($visible ? 'true' : 'false').'"/>';
        }

        return $xml.'<global visible="true"/></custom_ui>';
    }

    public function send(Player $player): void
    {
        if ($this->enabled()) {
            // custom_ui is only honoured inside a <manialinks> page, like the admin plugin's GamePanels sends it
            Ui::sendTo($player, '<manialinks><manialink id="'.Ids::ml('custom_ui').'"></manialink>'.$this->xml().'</manialinks>');
        }
    }
}
