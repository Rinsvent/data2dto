<?php

namespace Rinsvent\Data2DTO\Tests\unit\Converter\fixtures\FillTest\Transformer;

use Rinsvent\Data2DTO\Transformer\Meta;
use Rinsvent\Data2DTO\Transformer\TransformerInterface;

class ClassDataTransformer implements TransformerInterface
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

        if (isset($data['surname'])) {
            $data['surname'] = '123454321';
        }
    }
}
