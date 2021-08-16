<?php

namespace Rinsvent\Data2DTO;

use ReflectionProperty;
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
    public function convert(array $data, object $object, array $tags = []): object
    {
        $tags = empty($tags) ? ['default'] : $tags;
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

            if ($this->processVirtualProperty($object, $property, $data, $tags)) {
                continue;
            }

            if ($dataPath = $this->grabDataPath($property, $data, $tags)) {
                $value = $data[$dataPath];

                $this->processTransformers($property, $value, $tags);

                if ($this->processDataObject($object, $property, $value)) {
                    continue;
                }

                if ($this->processArray($value, $property, $tags)) {
                    continue;
                }

                $preparedPropertyType = $propertyType;
                if ($this->processInterface($property, $preparedPropertyType)) {
                    continue;
                }

                $this->processClass($object, $property, $preparedPropertyType, $value, $tags);

                if ($this->processNull($value, $reflectionPropertyType)) {
                    continue;
                }

                $property->setValue($object, $value);
            }
        }

        return $object;
    }

    /**
     * Для виртуальных полей добавляем пустой масиив, чтобы заполнить поля дто
     */
    protected function processVirtualProperty(object $object, \ReflectionProperty $property, array $data, array $tags): bool
    {
        $propertyExtractor = new PropertyExtractor($property->class, $property->getName());
        if ($propertyExtractor->fetch(VirtualProperty::class)) {
            if ($property->isInitialized($object)) {
                $propertyValue = $property->getValue($object);
                $value = $this->convert($data, $propertyValue, $tags);
            } else {
                $propertyType = $property->getType()->getName();
                $value = $this->convert($data, new $propertyType, $tags);
            }
            // присваиваем получившееся значение
            $property->setValue($object, $value);
            return true;
        }
        return false;
    }

    /**
     * В данных лежит объект, то дальше его не заполняем. Только присваиваем. Например, entity, document
     */
    protected function processDataObject(object $object, \ReflectionProperty $property, $value): bool
    {
        if (is_object($value)) {
            $property->setValue($object, $value);
            return true;
        }
        return false;
    }

    protected function processInterface(ReflectionProperty $property, &$preparedPropertyType): bool
    {
        if (interface_exists($preparedPropertyType)) {
            $attributedPropertyClass = $this->grabPropertyDTOClass($property);
            // Если не указали мета информацию для интерфейса - пропустим
            if (!$attributedPropertyClass) {
                return true;
            }
            // Если класс не реализует интерфейс свойства - пропустим
            $interfaces = class_implements($attributedPropertyClass);
            if (!isset($interfaces[$preparedPropertyType])) {
                return true;
            }
            $preparedPropertyType = $attributedPropertyClass;
        }
        return false;
    }

    /**
     * Если это class, то рекурсивно заполняем дальше
     */
    protected function processClass(object $object, ReflectionProperty $property, string $preparedPropertyType, &$value, array $tags)
    {
        if (class_exists($preparedPropertyType)) {
            if ($property->isInitialized($object)) {
                $propertyValue = $property->getValue($object);
                $value = $this->convert($value, $propertyValue, $tags);
            } else {
                $value = $this->convert($value, new $preparedPropertyType, $tags);
            }
        }
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

    /**
     * Трнансформируем на уровне класса
     */
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

    /**
     * Трнансформируем на уровне свойст объекта
     */
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
    private function processNull($value, \ReflectionNamedType $reflectionPropertyType): bool
    {
        return $value === null && !$reflectionPropertyType->allowsNull();
    }

    private function processArray(&$value, \ReflectionProperty $property, array $tags): bool
    {
        $attributedPropertyClass = $this->grabPropertyDTOClass($property);

        /** @var \ReflectionNamedType $reflectionPropertyType */
        $reflectionPropertyType = $property->getType();
        $propertyType = $reflectionPropertyType->getName();

        // Если массив и есть атрибут с указанием класса, то также преобразуем структуру
        if ($propertyType === 'array' && $attributedPropertyClass) {
            // Если тип у ДТО - массив, а в значении не массив - пропустим
            if (!is_array($value)) {
                return true;
            }
            $tempValue = [];
            foreach ($value as $itemValue) {
                $tempValue[] = $this->convert($itemValue, new $attributedPropertyClass, $tags);
            }
            $value = $tempValue;
        }
        return false;
    }

    private function grabPropertyDTOClass(\ReflectionProperty $property): ?string
    {
        $propertyName = $property->getName();
        $propertyExtractor = new PropertyExtractor($property->class, $propertyName);
        /** @var DTOMeta $dtoMeta */
        if ($dtoMeta = $propertyExtractor->fetch(DTOMeta::class)) {
            return $dtoMeta->class;
        }
        return null;
    }
}
