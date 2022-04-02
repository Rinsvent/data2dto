<?php

namespace Rinsvent\Data2DTO\Tests\unit\Converter\fixtures\FillTest;

use Rinsvent\Data2DTO\Attribute\DTOMeta;
use Rinsvent\Data2DTO\Attribute\PropertyPath;
use Rinsvent\Transformer\Transformer\Trim;

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

    private BuyRequest $buy2;

    public function getBuy2(): BuyRequest
    {
        return $this->buy2;
    }

    public function setBuy2(BuyRequest $buy2): void
    {
        $this->buy2 = $buy2;
    }
}
