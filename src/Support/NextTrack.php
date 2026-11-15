<?php
namespace Funseco\Widgets\Support;

use Funseco\Core\Models\Map;
use Funseco\Core\SharedKernel\JukeboxQueue;

/** Which track comes next: the first jukeboxed one, else the server's next (spec 4.3). */
final class NextTrack
{
    /** Resolve the next track, store it in State 'next' and call $done with the card. */
    public static function load(?callable $done = null): void
    {
        $store = function (TrackCard $card) use ($done) {
            State::put('next', $card->toArray());
            $done && $done($card);
        };

        $first = app(JukeboxQueue::class)->upcoming()[0] ?? null;
        if ($first && ($map = Map::where('uid', $first['uid'])->first())) {
            $store(TrackCard::fromMap($map));

            return;
        }

        \Server::query()->GetNextChallengeInfo(function ($result) use ($store) {
            $store(TrackCard::fromServer($result[0] ?? null));
        });
    }
}
