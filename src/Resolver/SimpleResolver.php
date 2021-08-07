<?php

namespace Rinsvent\Data2DTO\Resolver;

use Rinsvent\Data2DTO\Transformer\Meta;
use Rinsvent\Data2DTO\Transformer\TransformerInterface;

class SimpleResolver implements TransformerResolverInterface
{
    public function resolve(Meta $meta): TransformerInterface
    {
        $metaClass = $meta::class;
        $transformerClass = $metaClass . 'Transformer';
        return new $transformerClass;
    }
}