<?php

namespace App\ContentBrowser;

use Netgen\ContentBrowser\Item\LocationInterface;

class AlbumsRootLocation implements LocationInterface
{
    public int|string $locationId { get => 0; }

    public string $name { get => 'Albums'; }

    public int|string|null $parentId { get => null; }
}
