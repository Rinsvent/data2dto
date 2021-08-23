<?php

namespace Rinsvent\Data2DTO\Attribute;

#[\Attribute(\Attribute::IS_REPEATABLE|\Attribute::TARGET_ALL)]
class DTOMeta
{
    public function __construct(
        public string $class,
        /** @var string[] $tags */
        public array $tags = ['default']
    ) {}
}
