<?php

namespace Rinsvent\Data2DTO\Attribute;

#[\Attribute(\Attribute::IS_REPEATABLE|\Attribute::TARGET_ALL)]
class HandleTags
{
    public function __construct(
        public string $method,
    ) {}
}
