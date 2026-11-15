<?php
namespace Funseco\Widgets\Tests;

class FakeExchange implements \Funseco\Core\SharedKernel\TrackExchange
{
    public function __construct(private ?\Funseco\Core\SharedKernel\Values\TmxTrack $track) {}

    public function current(): ?\Funseco\Core\SharedKernel\Values\TmxTrack { return $this->track; }

    public function forUid(string $uid, ?string $section = null): \React\Promise\PromiseInterface { return \React\Promise\resolve($this->track); }

    public function forId(int $id, ?string $section = null): \React\Promise\PromiseInterface { return \React\Promise\resolve($this->track); }
}
