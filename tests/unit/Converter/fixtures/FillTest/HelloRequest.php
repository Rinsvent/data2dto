<?php

namespace Rinsvent\Data2DTO\Tests\unit\Converter\fixtures\FillTest;

use Rinsvent\Data2DTO\Attribute\DTOMeta;
use Rinsvent\Data2DTO\Attribute\PropertyPath;
use Rinsvent\Data2DTO\Transformer\Trim;

class HelloRequest
{
    #[Trim]
    public string $surname;
    #[PropertyPath('fake_age')]
    public int $age;
    public array $emails;
    #[DTOMeta(class: Author::class)]
    public array $authors;
    public BuyRequest $buy;
    #[DTOMeta(class: Bar::class)]
    public BarInterface $bar;
}
