<?php

namespace App\ContentBrowser;

use Netgen\ContentBrowser\Item\LocationInterface;

class BrowserRootLocation implements LocationInterface
{
    public int|string $locationId { get => 0; }

    public string $name { get => 'All'; }

    public int|string|null $parentId { get => null; }
}
