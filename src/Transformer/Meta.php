<?php

namespace Rinsvent\Data2DTO\Transformer;

#[\Attribute]
abstract class Meta
{
    public const TYPE = 'simple';
    public ?string $returnType = null;
    public ?bool $allowsNull = null;

    public function __construct(
        public array $tags = ['default']
    ) {}
}