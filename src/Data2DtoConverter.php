<?php

namespace Rinsvent\Data2DTO;

use Rinsvent\AttributeExtractor\PropertyExtractor;
use Rinsvent\Data2DTO\Attribute\PropertyPath;

class Data2DtoConverter
{
    public function convert(array $data, string $class): object
    {
        $object = new $class;

        $reflectionObject = new \ReflectionObject($object);
        $properties = $reflectionObject->getProperties();
        /** @var \ReflectionProperty $property */
        foreach ($properties as $property) {
            $propertyName = $property->getName();
            $propertyExtractor = new PropertyExtractor($object::class, $propertyName);
            /** @var PropertyPath $propertyPath */
            if ($propertyPath = $propertyExtractor->fetch(PropertyPath::class)) {
                $customPath = $propertyPath->path;
            }

            /** @var \ReflectionNamedType $reflectionPropertyType */
            $reflectionPropertyType = $property->getType();
            $propertyType = $reflectionPropertyType->getName();


            if(key_exists($propertyName, $data)) {
                $value = $data[$propertyName];
                if ($value === null && !$reflectionPropertyType->allowsNull()) {
                    continue;
                }

                if (class_exists($propertyType)) {
                    $value = $this->convert($value, $propertyType);
                }

                $property->setValue($object, $value);
            }
        }

        return $object;
    }
}