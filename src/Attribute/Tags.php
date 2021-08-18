<?php

namespace Rinsvent\Data2DTO\Attribute;

#[\Attribute]
class Tags
{
    public function __construct(
        public string $method,
    ) {}
}
