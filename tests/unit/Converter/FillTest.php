<?php

namespace Rinsvent\Data2DTO\Tests\Converter;

use Rinsvent\Data2DTO\Data2DtoConverter;
use Rinsvent\Data2DTO\Tests\unit\Converter\fixtures\FillTest\BuyRequest;
use Rinsvent\Data2DTO\Tests\unit\Converter\fixtures\FillTest\HelloRequest;

class FillTest extends \Codeception\Test\Unit
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
        $dto = $data2DtoConverter->convert([
            'surname' => '   asdf',
            'fake_age' => 3,
            'emails' => [
                'sfdgsa',
                'af234f',
                'asdf33333'
            ],
            'buy' => [
                'phrase' => 'Buy buy!!!',
                'length' => 10,
                'isFirst' => true,
                'extraData2' => '1234'
            ],
            'extraData1' => 'qwer'
        ], HelloRequest::class);
        $this->assertInstanceOf(HelloRequest::class, $dto);
        $this->assertEquals('asdf', $dto->surname);
        $this->assertEquals(3, $dto->age);
        $this->assertEquals([
            'sfdgsa',
            'af234f',
            'asdf33333'
        ], $dto->emails);
        $this->assertInstanceOf(BuyRequest::class, $dto->buy);
        $this->assertEquals('Buy buy!!!', $dto->buy->phrase);
        $this->assertEquals(10, $dto->buy->length);
        $this->assertEquals(true, $dto->buy->isFirst);
    }
}
