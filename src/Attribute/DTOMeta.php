<?php

namespace Rinsvent\Data2DTO\Attribute;

#[\Attribute]
class DTOMeta
{
    public function __construct(
        public string $class
    ) {}
}