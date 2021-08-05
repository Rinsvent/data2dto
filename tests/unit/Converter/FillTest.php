<?php

namespace Rinsvent\Data2DTO\Tests\Listener;

use Rinsvent\Data2DTO\Data2DtoConverter;
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
        $data2DtoConverter->convert([
            'surname' => 'asdf',
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
        // $this->assertEquals('Hello igor', $response->getContent());
    }
}
