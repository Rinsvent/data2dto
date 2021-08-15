<?php

namespace Rinsvent\Data2DTO\Attribute;

/** @property string[] $tags */
#[\Attribute]
class VirtualProperty
{
    public function __construct(
        public array $tags = ['default']
    ) {}
}