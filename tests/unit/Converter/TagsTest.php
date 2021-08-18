<?php

namespace Rinsvent\Data2DTO\Tests\Converter;

use Rinsvent\Data2DTO\Data2DtoConverter;
use Rinsvent\Data2DTO\Tests\unit\Converter\fixtures\FillTest\Bar;
use Rinsvent\Data2DTO\Tests\unit\Converter\fixtures\FillTest\BuyRequest;
use Rinsvent\Data2DTO\Tests\unit\Converter\fixtures\FillTest\HelloRequest;
use Rinsvent\Data2DTO\Tests\unit\Converter\fixtures\FillTest\HelloTagsRequest;

class TagsTest extends \Codeception\Test\Unit
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
            'surname' => 'Surname1234',
            'fake_age' => 3,
            'fake_age2' => 7,
            'emails' => [
                'sfdgsa',
                'af234f',
                'asdf33333'
            ],
        ], new HelloTagsRequest);
        $this->assertInstanceOf(HelloTagsRequest::class, $dto);
        $this->assertEquals(7, $dto->age);

    }
}
