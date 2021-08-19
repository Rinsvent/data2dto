<?php

namespace Rinsvent\Data2DTO\Tests\Converter;

use Rinsvent\Data2DTO\Data2DtoConverter;
use Rinsvent\Data2DTO\Tests\unit\Converter\fixtures\FillTest\Bar;
use Rinsvent\Data2DTO\Tests\unit\Converter\fixtures\FillTest\BuyRequest;
use Rinsvent\Data2DTO\Tests\unit\Converter\fixtures\FillTest\HelloRequest;

class DataObjectTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testSuccessFillRequestData()
    {
        $data2DtoConverter = new Data2DtoConverter();

        $buy = new BuyRequest();
        $buy->phrase = 'Buy buy!!!';
        $buy->length = 10;
        $buy->isFirst = true;

        $dto = $data2DtoConverter->convert([
            'surname' => '   asdf',
            'buy' => $buy
        ], new HelloRequest);

        $this->assertInstanceOf(HelloRequest::class, $dto);
        $this->assertEquals($buy, $dto->buy);
    }
}
