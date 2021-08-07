<?php

namespace Rinsvent\Data2DTO\Resolver;

class TransformerResolverStorage
{
    private array $items = [];

    public static function getInstance(): self
    {
        static $instance = null;

        if ($instance) {
            return $instance;
        }

        $instance = new self();
        $instance->add('simple', new SimpleResolver());

        return $instance;
    }

    public function add(string $code, TransformerResolverInterface $transformerResolver): void
    {
        $this->items[$code] = $transformerResolver;
    }

    public function get(string $code): TransformerResolverInterface
    {
        return $this->items[$code];
    }
}