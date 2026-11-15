<?php
namespace Funseco\Widgets\Support;

use Funseco\Core\Models\Map;
use Funseco\Core\Servers\TmForever\Values\ChallengeInfo;

/** A track as the widgets show it (current, last, next). */
final class TrackCard
{
    public function __construct(
        public readonly string $uid = '',
        public readonly string $name = 'unknown',
        public readonly string $author = 'unknown',
        public readonly string $env = 'unknown',
        public readonly string $mood = 'unknown',
        public readonly int $authorTime = -1,
        public readonly int $gold = -1,
        public readonly int $silver = -1,
        public readonly int $bronze = -1,
        public readonly string $file = '',
    ) {
    }

    public static function unknown(): self
    {
        return new self();
    }

    public static function fromMap(Map $map): self
    {
        return new self((string) $map->uid, (string) $map->name, (string) $map->author, (string) $map->environment,
            (string) $map->mood, (int) $map->author_time, (int) $map->gold_time, (int) $map->silver_time,
            (int) $map->bronze_time, (string) $map->file_name);
    }

    /** From a GetNextChallengeInfo struct (named keys, or the positional list ChallengeInfo takes). */
    public static function fromServer(mixed $struct): self
    {
        if (!is_array($struct)) {
            return self::unknown();
        }
        if (isset($struct['UId']) || isset($struct['Name'])) {
            return new self((string) ($struct['UId'] ?? ''), (string) ($struct['Name'] ?? 'unknown'), (string) ($struct['Author'] ?? 'unknown'),
                (string) ($struct['Environnement'] ?? 'unknown'), (string) ($struct['Mood'] ?? 'unknown'), (int) ($struct['AuthorTime'] ?? -1),
                (int) ($struct['GoldTime'] ?? -1), (int) ($struct['SilverTime'] ?? -1), (int) ($struct['BronzeTime'] ?? -1),
                (string) ($struct['FileName'] ?? ''));
        }
        try {
            $info = new ChallengeInfo($struct);
        } catch (\Throwable) {
            return self::unknown();
        }

        return new self((string) $info->uid, (string) $info->name, (string) $info->author, (string) $info->environment,
            (string) $info->mood, (int) $info->author_time, (int) $info->gold_time, (int) $info->silver_time,
            (int) $info->bronze_time, (string) $info->file_name);
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public static function fromArray(?array $a): self
    {
        return $a ? new self(...$a) : self::unknown();
    }

    public function time(int $ms, bool $stunts): string
    {
        return $ms < 0 ? Format::unknown($stunts) : Format::time($ms, $stunts);
    }
}
