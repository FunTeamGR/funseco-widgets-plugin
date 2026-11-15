<?php
namespace Funseco\Widgets\Models;

use Illuminate\Database\Eloquent\Model;
use Funseco\Core\Models\Player;

/**
 * Persistent per-player widget data: visits, clock timezone, record-widget preference,
 * finish count and the rounds/cup points total.
 *
 * @property int $player_id
 * @property int $visits
 * @property ?string $timezone  "<display name>|<IANA zone>"
 * @property bool $display_widgets
 * @property int $finishes
 * @property int $round_points
 */
class PlayerStats extends Model
{
    protected $table = 'widget_player_stats';
    protected $guarded = ['id'];
    protected $casts = ['display_widgets' => 'boolean'];

    public static function of(Player $player): self
    {
        return self::firstOrCreate(['player_id' => $player->id], ['visits' => 0, 'display_widgets' => true]);
    }

    /** IANA zone of the stored choice, or the configured default. */
    public function zone(): string
    {
        $zone = $this->timezone ? (explode('|', $this->timezone)[1] ?? '') : '';

        return $zone !== '' && in_array($zone, \DateTimeZone::listIdentifiers(\DateTimeZone::ALL_WITH_BC), true)
            ? $zone
            : (string) config('manialinks.clock_widget.default_timezone', 'Europe/Berlin');
    }

    public function zoneName(): string
    {
        return $this->timezone ? (explode('|', $this->timezone)[0] ?? '') : '';
    }
}
