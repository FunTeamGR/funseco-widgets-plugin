<?php
namespace Funseco\Widgets\Panels;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\UI\Theme\ThemeKit;
use Funseco\Widgets\Support\Ids;
use Funseco\Widgets\Support\Panel;
use Funseco\Widgets\Support\State;
use Funseco\Widgets\Support\StatusBar;
use Funseco\Widgets\Support\Ui;

/** Donation buttons at score (spec 22). Buttons run /donate (billed). */
class DonationWidget extends Panel
{
    public function id(): string
    {
        return 'donation';
    }

    public function enabled(): bool
    {
        return Ui::enabled('donation_widget') && State::atScore() && count($this->amounts()) >= 7;
    }

    /** @return int[] */
    public function amounts(): array
    {
        return array_slice(array_values(array_filter(array_map('intval', explode(',', (string) Ui::cfg('donation_widget', 'amounts', ''))))), 0, Ids::DONATIONS);
    }

    protected function xml(?Player $player): ?string
    {
        $w = (array) Ui::cfg('donation_widget', 'widget', []);
        $amounts = $this->amounts();
        $height = 6.55 + 1.85 * count($amounts);
        $buttons = '';
        $kit = ThemeKit::current();
        foreach ($amounts as $i => $amount) {
            $y = -(6.75 + 1.8 * $i);
            $buttons .= $kit->surface('bar_button', 4.2, 1.7, $w['button_style'] ?? 'Bgs1InRace', $w['button_substyle'] ?? 'BgIconBorder', 'action="'.StatusBar::action('donate_'.$i).'"', 0.2, -$y, 0.2)
                .Ui::label('2.2 '.($y - 0.25).' 0.3', '', $amount.'$n $mC', 'halign="center" scale="0.8" textcolor="'.($w['button_color'] ?? '000F').'"');
        }

        return '<frame posn="'.($w['pos_x'] ?? -47.9).' '.($w['pos_y'] ?? 18.2).' 0"><format textsize="1"/>'
            .$kit->surface('bar', 4.6, $height, $w['background_style'] ?? 'BgsPlayerCard', $w['background_substyle'] ?? 'BgRacePlayerName')
            .StatusBar::icon($w['icon_style'] ?? 'Icons128x128_1', $w['icon_substyle'] ?? 'Coppers', '0.7 -0.3 0.002', '3.2 2.7')
            .StatusBar::value('PLEASE', 'scale="0.9"')
            .StatusBar::caption('donation_widget', 'DONATE', '2.3 -4.9 0.1')
            .$buttons.'</frame>';
    }
}
