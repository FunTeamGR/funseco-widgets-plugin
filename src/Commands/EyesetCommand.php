<?php
namespace Funseco\Widgets\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\Commands\Command;
use Funseco\Core\SharedKernel\Commands\DeclaresCommand;
use Funseco\Core\SharedKernel\Commands\Described;
use Funseco\Core\SharedKernel\Commands\Guarded;
use Funseco\Core\SharedKernel\Enums\Role;
use Funseco\Widgets\Listeners\Lifecycle;
use Funseco\Widgets\Panels\PlacementWidgets;
use Funseco\Widgets\Support\State;
use Funseco\Widgets\Support\Text;
use Funseco\Widgets\Support\Ui;
use Funseco\Core\Services\Cache\CurrentPlayers;
use Funseco\Widgets\Windows\WindowManager;

/** /eyeset <param>: adjust widget settings until the next restart (master admins only). */
class EyesetCommand implements Command, Guarded, Described
{
    use DeclaresCommand;

    protected string $name = 'eyeset';
    protected ?Role $role = Role::MasterAdmin;
    protected string $description = 'Adjust some settings for the widgets plugin (see: /eyepiece)';
    protected string $usage = 'reload|lfresh|hfresh|llimit|ulimit|forcenice|playermarker <value>';

    public function handle(Player $player, array $args): void
    {
        $param = strtolower($args[0] ?? '');
        $value = $args[1] ?? '';
        $number = max(0, (int) $value);
        $bool = fn () => in_array(strtolower($value), ['true', '1', 'on', 'yes'], true) ? 'true' : 'false';

        $reply = match ($param) {
            'reload'       => $this->reload(),
            'lfresh'       => $this->set('lfresh', $number, $number),
            'hfresh'       => $this->set('hfresh', $number, $number),
            'llimit'       => $this->set('nice_lower', $number, $number),
            'ulimit'       => $this->set('nice_upper', $number, $number),
            'forcenice'    => $this->forceNice($bool()),
            'playermarker' => $this->set('playermarker', $bool(), $bool()),
            default        => Text::get('eyeset_unknown'),
        };
        Text::tell($player, $reply);
    }

    private function set(string $key, mixed $stored, mixed $shown): string
    {
        State::put($key, $stored);
        $text = [
            'lfresh' => 'eyeset_lfresh', 'hfresh' => 'eyeset_hfresh', 'nice_lower' => 'eyeset_llimit',
            'nice_upper' => 'eyeset_ulimit', 'playermarker' => 'eyeset_playermarker',
        ][$key];
        if (str_starts_with($key, 'nice_')) {
            State::updateNice();
        }

        return Text::get($text, (string) $shown);
    }

    private function forceNice(string $value): string
    {
        State::put('force_nice', $value);
        State::updateNice();

        return Text::get('eyeset_forcenice', $value);
    }

    /** Close and re-send every widget (the configuration itself is read at boot). */
    private function reload(): string
    {
        if (State::atScore()) {
            return Text::get('eyeset_reload_score');
        }
        WindowManager::closeAll();
        app(PlacementWidgets::class)->forget();
        foreach (CurrentPlayers::all() as $player) {
            app(Lifecycle::class)->sendRaceTo($player);
        }

        return Text::get('eyeset_reload');
    }
}
