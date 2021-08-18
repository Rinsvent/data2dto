<?php

namespace Rinsvent\Data2DTO\Tests\unit\Converter\fixtures\FillTest;

use Rinsvent\Data2DTO\Attribute\PropertyPath;
use Rinsvent\Data2DTO\Attribute\TagsResolver;

#[TagsResolver(method: 'getTags')]
class HelloTagsRequest extends HelloRequest
{
    #[PropertyPath('fake_age2', tags: ['surname-group'])]
    public int $age;

    public function getTags(array $data)
    {
        return 'Surname1234' === ($data['surname'] ?? null) ? ['surname-group'] : ['default'];
    }
}
