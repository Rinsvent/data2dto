<?php

namespace Rinsvent\Data2DTO\Tests\unit\Converter\fixtures\FillTest;

use Rinsvent\Data2DTO\Tests\unit\Converter\fixtures\FillTest\Transformer\ClassObject;

#[ClassObject]
class HelloClassTransformersRequest2
{
    public string $surname;
    public int $age;
}
