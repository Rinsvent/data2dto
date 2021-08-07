<?php

namespace Rinsvent\Data2DTO;

use Rinsvent\AttributeExtractor\PropertyExtractor;
use Rinsvent\Data2DTO\Attribute\PropertyPath;
use Rinsvent\Data2DTO\Resolver\TransformerResolverStorage;
use Rinsvent\Data2DTO\Transformer\Meta;
use Rinsvent\Data2DTO\Transformer\TransformerInterface;
use function Symfony\Component\String\u;

class Data2DtoConverter
{
    public function convert(array $data, string $class): object
    {
        $object = new $class;

        $reflectionObject = new \ReflectionObject($object);
        $properties = $reflectionObject->getProperties();
        /** @var \ReflectionProperty $property */
        foreach ($properties as $property) {
            /** @var \ReflectionNamedType $reflectionPropertyType */
            $reflectionPropertyType = $property->getType();
            $propertyType = $reflectionPropertyType->getName();

            if ($dataPath = $this->grabDataPath($property, $data)) {
                $value = $data[$dataPath];
                // Трансформируем данные
                $this->processTransformers($property, $value);

                // Если значение в $data = null, но поле не может его принять - пропустим
                if ($value === null && !$reflectionPropertyType->allowsNull()) {
                    continue;
                }
                // В данных лежит объект, то дальше его не заполняем. Только присваиваем. Например, entity, document
                if (is_object($value)) {
                    $property->setValue($object, $value);
                    continue;
                }
                // Если это class, то рекурсивно заполняем дальше
                if (class_exists($propertyType)) {
                    $value = $this->convert($value, $propertyType);
                }
                // присваиваем получившееся значение
                $property->setValue($object, $value);
            }
        }

        return $object;
    }

    protected function grabDataPath(\ReflectionProperty $property, array $data): ?string
    {
        $propertyName = $property->getName();
        $propertyExtractor = new PropertyExtractor($property->class, $propertyName);
        /** @var PropertyPath $propertyPath */
        if ($propertyPath = $propertyExtractor->fetch(PropertyPath::class)) {
            return $propertyPath->path;
        }

        if (key_exists($propertyName, $data)) {
            return $propertyName;
        }

        $variants = [
            u($propertyName)->camel()->toString(),
            u($propertyName)->snake()->toString(),
            u($propertyName)->snake()->upper()->toString(),
        ];
        foreach ($variants as $variant) {
            if (key_exists($variant, $data)) {
                return $variant;
            }
        }

        return null;
    }

    protected function processTransformers(\ReflectionProperty $property, &$data): void
    {
        $propertyName = $property->getName();
        $propertyExtractor = new PropertyExtractor($property->class, $propertyName);
        /** @var Meta $transformMeta */
        if ($transformMeta = $propertyExtractor->fetch(Meta::class)) {
            $transformer = $this->grabTransformer($transformMeta);
            $transformer->transform($data, $transformMeta);
        }
    }

    protected function grabTransformer(Meta $meta): TransformerInterface
    {
        $storage = TransformerResolverStorage::getInstance();
        $resolver = $storage->get($meta::TYPE);
        return $resolver->resolve($meta);
    }
}