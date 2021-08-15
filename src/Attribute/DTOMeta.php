<?php

namespace Rinsvent\Data2DTO\Attribute;

#[\Attribute]
class DTOMeta
{
    public function __construct(
        public string $class,
        /** @var string[] $tags */
        public array $tags = ['default']
    ) {}
}