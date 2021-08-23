<?php

namespace Rinsvent\Data2DTO\Attribute;

#[\Attribute(\Attribute::IS_REPEATABLE|\Attribute::TARGET_ALL)]
class PropertyPath
{
    public function __construct(
        public string $path,
        /** @var string[] $tags */
        public array $tags = ['default']
    ) {}
}
