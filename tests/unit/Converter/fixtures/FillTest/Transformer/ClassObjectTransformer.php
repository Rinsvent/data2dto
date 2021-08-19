<?php

namespace Rinsvent\Data2DTO\Tests\unit\Converter\fixtures\FillTest\Transformer;

use Rinsvent\Data2DTO\Tests\unit\Converter\fixtures\FillTest\HelloClassTransformersRequest2;
use Rinsvent\Data2DTO\Transformer\Meta;
use Rinsvent\Data2DTO\Transformer\TransformerInterface;

class ClassObjectTransformer implements TransformerInterface
{
    /**
     * @param array|null $data
     * @param ClassData $meta
     */
    public function transform(&$data, Meta $meta): void
    {
        if ($data === null) {
            return;
        }
        $object = new HelloClassTransformersRequest2();
        $object->surname = '98789';
        $data = $object;
    }
}
