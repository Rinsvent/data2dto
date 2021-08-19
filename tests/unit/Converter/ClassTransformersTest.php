<?php

namespace Rinsvent\Data2DTO\Tests\Converter;

use Rinsvent\Data2DTO\Data2DtoConverter;
use Rinsvent\Data2DTO\Tests\unit\Converter\fixtures\FillTest\HelloClassTransformersRequest;
use Rinsvent\Data2DTO\Tests\unit\Converter\fixtures\FillTest\HelloClassTransformersRequest2;
use Rinsvent\Data2DTO\Tests\unit\Converter\fixtures\FillTest\HelloTagsRequest;

class ClassTransformersTest extends \Codeception\Test\Unit
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
    public function testSuccessWithReturnData()
    {
        $data2DtoConverter = new Data2DtoConverter();

        $data = [
            'surname' => 'Surname1234',
            'age' => 3,
        ];
        $dto = $data2DtoConverter->convert($data, new HelloClassTransformersRequest);
        $this->assertInstanceOf(HelloClassTransformersRequest::class, $dto);
        $this->assertEquals(123454321, $dto->surname);
    }

    public function testSuccessWithReturnObject()
    {
        $data2DtoConverter = new Data2DtoConverter();

        $data = [
            'surname' => 'Surname1234',
            'age' => 3,
        ];
        $dto = $data2DtoConverter->convert($data, new HelloClassTransformersRequest2());
        $this->assertInstanceOf(HelloClassTransformersRequest2::class, $dto);
        $this->assertEquals(98789, $dto->surname);
    }
}
