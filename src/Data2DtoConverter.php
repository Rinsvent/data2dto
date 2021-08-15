<?php

namespace Rinsvent\Data2DTO;

use Rinsvent\AttributeExtractor\ClassExtractor;
use Rinsvent\AttributeExtractor\PropertyExtractor;
use Rinsvent\Data2DTO\Attribute\DTOMeta;
use Rinsvent\Data2DTO\Attribute\PropertyPath;
use Rinsvent\Data2DTO\Attribute\VirtualProperty;
use Rinsvent\Data2DTO\Resolver\TransformerResolverStorage;
use Rinsvent\Data2DTO\Transformer\Meta;
use Rinsvent\Data2DTO\Transformer\TransformerInterface;
use function Symfony\Component\String\u;

class Data2DtoConverter
{
    public function convert(array $data, string $class, array $tags = [], ?object $instance = null): object
    {
        $tags = empty($tags) ? ['default'] : $tags;
        $object = $instance ?? new $class;
        $reflectionObject = new \ReflectionObject($object);
        $this->processClassTransformers($reflectionObject, $data, $tags);
        if (is_object($data)) {
            return $data;
        }

        $properties = $reflectionObject->getProperties();
        /** @var \ReflectionProperty $property */
        foreach ($properties as $property) {
            /** @var \ReflectionNamedType $reflectionPropertyType */
            $reflectionPropertyType = $property->getType();
            $propertyType = $reflectionPropertyType->getName();

            // Для виртуальных полей добавляем пустой масиив, чтобы заполнить поля дто
            $propertyExtractor = new PropertyExtractor($property->class, $property->getName());
            if ($propertyExtractor->fetch(VirtualProperty::class)) {
                if (!array_key_exists($property->getName(), $data)) {
                    $data[$property->getName()] = [];
                }
            }

            if ($dataPath = $this->grabDataPath($property, $data, $tags)) {
                $value = $data[$dataPath];
                // Трансформируем данные
                $this->processTransformers($property, $value, $tags);

                // В данных лежит объект, то дальше его не заполняем. Только присваиваем. Например, entity, document
                if (is_object($value)) {
                    $property->setValue($object, $value);
                    continue;
                }

                if (!$this->transformArray($value, $property, $tags)) {
                    continue;
                }

                $preparedPropertyType = $propertyType;

                if (interface_exists($preparedPropertyType)) {
                    $attributedPropertyClass = $this->grabPropertyDTOClass($property);
                    // Если не указали мета информацию для интерфейса - пропустим
                    if (!$attributedPropertyClass) {
                        continue;
                    }
                    // Если класс не реализует интерфейс свойства - пропустим
                    $interfaces = class_implements($attributedPropertyClass);
                    if (!isset($interfaces[$preparedPropertyType])) {
                        continue;
                    }
                    $preparedPropertyType = $attributedPropertyClass;
                }

                // Если это class, то рекурсивно заполняем дальше
                if (class_exists($preparedPropertyType)) {
                    if ($property->isInitialized($object)) {
                        $propertyValue = $property->getValue($object);
                        $value = $this->convert($value, $preparedPropertyType, $tags, $propertyValue);
                    } else {
                        $value = $this->convert($value, $preparedPropertyType, $tags);
                    }
                }

                if ($this->checkNullRule($value, $reflectionPropertyType)) {
                    continue;
                }

                // присваиваем получившееся значение
                $property->setValue($object, $value);
            }
        }

        return $object;
    }

    protected function grabDataPath(\ReflectionProperty $property, array $data, array $tags): ?string
    {
        $propertyName = $property->getName();
        $propertyExtractor = new PropertyExtractor($property->class, $propertyName);
        /** @var PropertyPath $propertyPath */
        if ($propertyPath = $propertyExtractor->fetch(PropertyPath::class)) {
            $filteredTags = array_diff($tags, $propertyPath->tags);
            if (count($filteredTags) !== count($tags)) {
                if (key_exists($propertyPath->path, $data)) {
                    return $propertyPath->path;
                }
            }
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

    protected function processClassTransformers(\ReflectionObject $object, &$data, array $tags): void
    {
        $className = $object->getName();
        $classExtractor = new ClassExtractor($className);
        /** @var Meta $transformMeta */
        while ($transformMeta = $classExtractor->fetch(Meta::class)) {
            $transformMeta->returnType = $className;
            $filteredTags = array_diff($tags, $transformMeta->tags);
            if (count($filteredTags) === count($tags)) {
                continue;
            }

            $transformer = $this->grabTransformer($transformMeta);
            $transformer->transform($data, $transformMeta);
        }
    }

    protected function processTransformers(\ReflectionProperty $property, &$data, array $tags): void
    {
        $propertyName = $property->getName();
        $propertyExtractor = new PropertyExtractor($property->class, $propertyName);
        /** @var Meta $transformMeta */
        while ($transformMeta = $propertyExtractor->fetch(Meta::class)) {
            $filteredTags = array_diff($tags, $transformMeta->tags);
            if (count($filteredTags) === count($tags)) {
                continue;
            }
            /** @var \ReflectionNamedType $reflectionPropertyType */
            $reflectionPropertyType = $property->getType();
            $propertyType = $reflectionPropertyType->getName();
            $transformMeta->returnType = $propertyType;
            $transformMeta->allowsNull = $reflectionPropertyType->allowsNull();
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

    /**
     * Если значение в $data = null, но поле не может его принять - пропустим
     */
    private function checkNullRule($value, \ReflectionNamedType $reflectionPropertyType): bool
    {
        return $value === null && !$reflectionPropertyType->allowsNull();
    }

    private function transformArray(&$value, \ReflectionProperty $property, array $tags): bool
    {
        $attributedPropertyClass = $this->grabPropertyDTOClass($property);

        /** @var \ReflectionNamedType $reflectionPropertyType */
        $reflectionPropertyType = $property->getType();
        $propertyType = $reflectionPropertyType->getName();

        // Если массив и есть атрибут с указанием класса, то также преобразуем структуру
        if ($propertyType === 'array' && $attributedPropertyClass) {
            // Если тип у ДТО - массив, а в значении не массив - пропустим
            if (!is_array($value)) {
                return false;
            }
            $tempValue = [];
            foreach ($value as $itemValue) {
                $tempValue[] = $this->convert($itemValue, $attributedPropertyClass, $tags);
            }
            $value = $tempValue;
        }
        return true;
    }

    private function grabPropertyDTOClass(\ReflectionProperty $property): ?string
    {
        $attributedPropertyClass = null;
        $propertyName = $property->getName();
        $propertyExtractor = new PropertyExtractor($property->class, $propertyName);
        /** @var DTOMeta $dtoMeta */
        if ($dtoMeta = $propertyExtractor->fetch(DTOMeta::class)) {
            $attributedPropertyClass = $dtoMeta->class;
        }
        return $attributedPropertyClass;
    }
}