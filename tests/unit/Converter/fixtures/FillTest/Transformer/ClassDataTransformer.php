<?php

namespace Rinsvent\Data2DTO\Tests\unit\Converter\fixtures\FillTest\Transformer;

use Rinsvent\Transformer\Transformer\Meta;
use Rinsvent\Transformer\Transformer\TransformerInterface;

class ClassDataTransformer implements TransformerInterface
{
    /**
     * @param array|null $data
     * @param ClassData $meta
     */
    public function transform(mixed $data, Meta $meta): mixed
    {
        if ($data === null) {
            return $data;
        }

        if (isset($data['surname'])) {
            $data['surname'] = '123454321';
        }
        return $data;
    }
}
