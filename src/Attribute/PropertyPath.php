<?php

namespace Rinsvent\Data2DTO\Attribute;

#[\Attribute]
class PropertyPath
{
    public function __construct(
        public string $path
    ) {}
}