[![pipeline status](https://git.rinsvent.ru/rinsvent/data2dto/badges/master/pipeline.svg)](https://git.rinsvent.ru/rinsvent/data2dto/-/commits/master)
[![coverage report](https://git.rinsvent.ru/rinsvent/data2dto/badges/master/coverage.svg)](https://git.rinsvent.ru/rinsvent/data2dto/-/commits/master)

Data2dto
===

## Установка
```php
composer require rinsvent/data2dto
```

## Пример

### Описания ДТО
```php
class BuyRequest
{
    public string $phrase;
    public int $length;
    public bool $isFirst;
}

interface BarInterface
{

}

class Bar implements BarInterface
{
    public float $barField;
}

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
```
### Использование
```php
use Rinsvent\Data2DTO\Data2DtoConverter;

$data2DtoConverter = new Data2DtoConverter();
$dto = $data2DtoConverter->convert([
    'surname' => '   asdf',
    'fake_age' => 3,
    'emails' => [
        'sfdgsa',
        'af234f',
        'asdf33333'
    ],
    'authors' => [
        [
            'name' => 'Tolkien',
        ],
        [
            'name' => 'Sapkovsky'
        ]
    ],
    'buy' => [
        'phrase' => 'Buy buy!!!',
        'length' => 10,
        'isFirst' => true,
        'extraData2' => '1234'
    ],
    'bar' => [
        'barField' => 32
    ],
    'extraData1' => 'qwer'
], HelloRequest::class);
```

