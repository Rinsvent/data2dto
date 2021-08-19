<?php

namespace Rinsvent\Data2DTO\Tests\unit\Converter\fixtures\FillTest;

use Rinsvent\Data2DTO\Tests\unit\Converter\fixtures\FillTest\Transformer\ClassData;

#[ClassData]
class HelloClassTransformersRequest
{
    public string $surname;
    public int $age;
}
