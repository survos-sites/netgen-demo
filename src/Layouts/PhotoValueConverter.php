<?php

namespace App\Layouts;

use App\Entity\Photo;
use Netgen\Layouts\Item\ValueConverterInterface;

class PhotoValueConverter implements ValueConverterInterface
{
    public function supports(object $object): bool
    {
        return $object instanceof Photo;
    }

    public function getValueType(object $object): string
    {
        return 'doctrine_photo';
    }

    public function getId(object $object): int|string
    {
        assert($object instanceof Photo);

        return $object->getId();
    }

    public function getRemoteId(object $object): int|string
    {
        return $this->getId($object);
    }

    public function getName(object $object): string
    {
        assert($object instanceof Photo);

        return $object->getTitle();
    }

    public function getIsVisible(object $object): bool
    {
        return true;
    }

    public function getObject(object $object): object
    {
        return $object;
    }
}
