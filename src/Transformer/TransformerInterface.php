<?php

namespace Rinsvent\Data2DTO\Transformer;

interface TransformerInterface
{
    public function transform(&$data, Meta $meta): void;
}