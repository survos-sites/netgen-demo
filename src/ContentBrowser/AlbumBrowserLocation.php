<?php

namespace App\ContentBrowser;

use App\Entity\Album;
use Netgen\ContentBrowser\Item\LocationInterface;

class AlbumBrowserLocation implements LocationInterface
{
    public function __construct(private Album $album)
    {
    }

    public int|string $locationId { get => $this->album->getId(); }

    public string $name { get => $this->album->getName(); }

    public int|string|null $parentId { get => 0; }

    public function getAlbum(): Album
    {
        return $this->album;
    }
}
