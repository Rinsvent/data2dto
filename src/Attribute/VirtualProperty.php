<?php

namespace Rinsvent\Data2DTO\Attribute;

#[\Attribute]
class VirtualProperty
{
    public function __construct(
        /** @var string[] $tags */
        public array $tags = ['default']
    ) {}
}