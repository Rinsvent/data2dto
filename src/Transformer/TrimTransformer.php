<?php

namespace Rinsvent\Data2DTO\Transformer;

class TrimTransformer implements TransformerInterface
{
    /**
     * @param string|null $data
     * @param Trim $meta
     */
    public function transform(&$data, Meta $meta): void
    {
        if ($data === null) {
            return;
        }
        $data = trim($data, $meta->characters);
    }
}