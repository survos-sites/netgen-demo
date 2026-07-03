<?php

namespace App\ContentBrowser;

use App\Entity\Photo;
use Netgen\ContentBrowser\Item\ItemInterface;

class PhotoBrowserItem implements ItemInterface
{
    public function __construct(private Photo $photo)
    {
    }

    public int|string $value { get => $this->photo->getId(); }

    public string $name { get => $this->photo->getTitle(); }

    public bool $isVisible { get => true; }

    public bool $isSelectable { get => true; }

    public function getPhoto(): Photo
    {
        return $this->photo;
    }
}
