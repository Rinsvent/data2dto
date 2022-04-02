<?php

namespace Rinsvent\Data2DTO;

use ReflectionProperty;
use Rinsvent\AttributeExtractor\ClassExtractor;
use Rinsvent\AttributeExtractor\PropertyExtractor;
use Rinsvent\Data2DTO\Attribute\DTOMeta;
use Rinsvent\Data2DTO\Attribute\PropertyPath;
use Rinsvent\Data2DTO\Attribute\HandleTags;
use Rinsvent\Data2DTO\Attribute\VirtualProperty;
use Rinsvent\Transformer\Transformer;
use Rinsvent\Transformer\Transformer\Meta;
use function Symfony\Component\String\u;

class Data2DtoConverter
{
    private Transformer $transformer;

    public function __construct()
    {
        $this->transformer = new Transformer();
    }

    public function getTags(array $data, object $object, array $tags = []): array
    {
        return $this->processTags($object, $data, $tags);
    }

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
            // todo добавить атрибут на пропуск обработки и еще атрибут на допустимые поля

            /** @var \ReflectionNamedType $reflectionPropertyType */
            $reflectionPropertyType = $property->getType();
            $propertyType = $reflectionPropertyType->getName();

            if ($this->processVirtualProperty($object, $property, $data, $tags)) {
                continue;
            }

            $value = $this->grabValue($property, $data, $tags);

            $this->processTransformers($property, $value, $tags);

            if ($this->processDataObject($object, $property, $value)) {
                continue;
            }

            if ($this->processArray($property, $value, $tags)) {
                continue;
            }

            $preparedPropertyType = $propertyType;
            if ($this->processInterface($property, $preparedPropertyType)) {
                continue;
            }

            if ($this->processClass($object, $property, $preparedPropertyType, $value, $tags)) {
                continue;
            }

            if ($this->processNull($reflectionPropertyType, $value)) {
                continue;
            }

            $this->setValue($object, $property, $value);
        }

        return $object;
    }

    /**
     * Для виртуальных полей добавляем пустой масиив, чтобы заполнить поля дто
     */
    protected function processVirtualProperty(
        object $object,
        \ReflectionProperty $property,
        array $data,
        array $tags
    ): bool {
        $propertyExtractor = new PropertyExtractor($property->class, $property->getName());
        if ($propertyExtractor->fetch(VirtualProperty::class)) {
            $propertyValue = $this->getValue($object, $property);
            if ($property->isInitialized($object) && $propertyValue) {
                $value = $this->convert($data, $propertyValue, $tags);
            } else {
                $propertyType = $property->getType()->getName();
                $value = $this->convert($data, new $propertyType, $tags);
            }
            // присваиваем получившееся значение
            $this->setValue($object, $property, $value);
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
            $this->setValue($object, $property, $value);
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
    protected function processClass(
        object $object,
        ReflectionProperty $property,
        string $preparedPropertyType,
        &$value,
        array $tags
    ): bool {
        if (class_exists($preparedPropertyType)) {
            $propertyValue = $this->getValue($object, $property);
            if (!is_array($value)) {
                return true;
            }
            if ($property->isInitialized($object) && $propertyValue) {
                $value = $this->convert($value, $propertyValue, $tags);
            } else {
                $value = $this->convert($value, new $preparedPropertyType, $tags);
            }
        }
        return false;
    }

    protected function grabValue(\ReflectionProperty $property, array $data, array $tags)
    {
        if ($dataPath = $this->grabDataPath($property, $data, $tags)) {
            return $data[$dataPath] ?? null;
        }

        return null;
    }

    protected function grabDataPath(\ReflectionProperty $property, array $data, array $tags): ?string
    {
        $propertyName = $property->getName();
        $propertyExtractor = new PropertyExtractor($property->class, $propertyName);
        /** @var PropertyPath $propertyPath */
        if ($propertyPath = $propertyExtractor->fetch(PropertyPath::class)) {
            $filteredTags = array_diff($tags, $propertyPath->tags);
            if (count($filteredTags) !== count($tags)) {
                if (array_key_exists($propertyPath->path, $data)) {
                    return $propertyPath->path;
                }
            }
        }

        if (array_key_exists($propertyName, $data)) {
            return $propertyName;
        }

        $variants = [
            u($propertyName)->camel()->toString(),
            u($propertyName)->snake()->toString(),
            u($propertyName)->snake()->upper()->toString(),
        ];
        foreach ($variants as $variant) {
            if (array_key_exists($variant, $data)) {
                return $variant;
            }
        }

        return null;
    }

    /**
     * Получаем теги для обработки
     */
    protected function processTags(object $object, array $data, array $tags): array
    {
        $classExtractor = new ClassExtractor($object::class);
        /** @var HandleTags $tagsMeta */
        if ($tagsMeta = $classExtractor->fetch(HandleTags::class)) {
            if (method_exists($object, $tagsMeta->method)) {
                $reflectionMethod = new \ReflectionMethod($object, $tagsMeta->method);
                if (!$reflectionMethod->isPublic()) {
                    $reflectionMethod->setAccessible(true);
                }
                $methodTags = $reflectionMethod->invoke($object, ...[$data, $tags]);
                if (!$reflectionMethod->isPublic()) {
                    $reflectionMethod->setAccessible(false);
                }
                return $methodTags;
            }
        }

        return $tags;
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
            $data = $this->transformer->transform($data, $transformMeta, $tags);
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
            /** @var \ReflectionNamedType $reflectionPropertyType */
            $reflectionPropertyType = $property->getType();
            $propertyType = $reflectionPropertyType->getName();
            $transformMeta->retrnType = $propertyType;
            $transformMeta->allowsNull = $reflectionPropertyType->allowsNull();
            $data = $this->transformer->transform($data, $transformMeta, $tags);
        }
    }

    /**
     * Если значение в $data = null, но поле не может его принять - пропустим
     */
    private function processNull(\ReflectionNamedType $reflectionPropertyType, $value): bool
    {
        return $value === null && !$reflectionPropertyType->allowsNull();
    }

    private function processArray(\ReflectionProperty $property, &$value, array $tags): bool
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
                if (!is_array($itemValue)) {
                    continue;
                }
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

    private function setValue(object $object, \ReflectionProperty $property, $value)
    {
        if (!$property->isPublic()) {
            $property->setAccessible(true);
        }

        $property->setValue($object, $value);

        if (!$property->isPublic()) {
            $property->setAccessible(false);
        }
    }

    private function getValue(object $object, \ReflectionProperty $property)
    {
        if (!$property->isPublic()) {
            $property->setAccessible(true);
        }

        if (!$property->isInitialized($object)) {
            if (!$property->isPublic()) {
                $property->setAccessible(false);
            }
            return null;
        }

        $value = $property->getValue($object);

        if (!$property->isPublic()) {
            $property->setAccessible(false);
        }

        return $value;
    }
}
