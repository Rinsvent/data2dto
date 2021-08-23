<?php

namespace Rinsvent\Data2DTO\Attribute;

/** @property string[] $tags */
#[\Attribute(\Attribute::IS_REPEATABLE|\Attribute::TARGET_ALL)]
class VirtualProperty
{
    public function __construct(
        public array $tags = ['default']
    ) {}
}
