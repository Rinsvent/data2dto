<?php

namespace Rinsvent\Data2DTO\Attribute;

#[\Attribute]
class TagsResolver
{
    public function __construct(
        public string $method,
    ) {}
}
