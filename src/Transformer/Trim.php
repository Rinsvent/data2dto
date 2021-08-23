<?php

namespace Rinsvent\Data2DTO\Transformer;

#[\Attribute(\Attribute::IS_REPEATABLE|\Attribute::TARGET_ALL)]
class Trim extends Meta
{
    public function __construct(
        public array $tags = ['default'],
        public string $characters = " \t\n\r\0\x0B"
    ) {
        parent::__construct(...func_get_args());
    }
}
