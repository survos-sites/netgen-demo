<?php

namespace App\Layouts;

use App\Repository\PhotoRepository;
use Netgen\Layouts\Item\ValueLoaderInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('netgen_layouts.cms_value_loader', ['value_type' => 'doctrine_photo'])]
class PhotoValueLoader implements ValueLoaderInterface
{
    public function __construct(private PhotoRepository $photoRepository)
    {
    }

    public function load(int|string $id): ?object
    {
        return $this->photoRepository->find($id);
    }

    public function loadByRemoteId(int|string $remoteId): ?object
    {
        return $this->load($remoteId);
    }
}
