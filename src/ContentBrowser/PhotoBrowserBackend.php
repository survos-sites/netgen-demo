<?php

namespace App\ContentBrowser;

use App\Entity\Photo;
use App\Repository\AlbumRepository;
use App\Repository\PhotoRepository;
use Netgen\ContentBrowser\Backend\BackendInterface;
use Netgen\ContentBrowser\Backend\SearchQuery;
use Netgen\ContentBrowser\Backend\SearchResult;
use Netgen\ContentBrowser\Backend\SearchResultInterface;
use Netgen\ContentBrowser\Item\ItemInterface;
use Netgen\ContentBrowser\Item\LocationInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Unlike RecipeBrowserBackend (flat, single root), this backend has a real
 * two-level hierarchy: the root lists Albums, and each Album lists its
 * Photos - showing how Netgen's content browser models a folder-like
 * structure, not just a flat list.
 */
#[AutoconfigureTag('netgen_content_browser.backend', ['item_type' => 'doctrine_photo'])]
class PhotoBrowserBackend implements BackendInterface
{
    public function __construct(
        private AlbumRepository $albumRepository,
        private PhotoRepository $photoRepository,
    ) {
    }

    public function getSections(): iterable
    {
        return [new AlbumsRootLocation()];
    }

    public function loadLocation(int|string $id): LocationInterface
    {
        if ($id === '0' || $id === 0) {
            return new AlbumsRootLocation();
        }

        $album = $this->albumRepository->find($id);
        if ($album === null) {
            throw new \InvalidArgumentException(sprintf('Invalid location "%s"', $id));
        }

        return new AlbumBrowserLocation($album);
    }

    public function loadItem(int|string $value): ItemInterface
    {
        return new PhotoBrowserItem($this->photoRepository->find($value));
    }

    public function getSubLocations(LocationInterface $location): iterable
    {
        if (!$location instanceof AlbumsRootLocation) {
            return [];
        }

        return array_map(
            fn ($album) => new AlbumBrowserLocation($album),
            $this->albumRepository->createQueryBuilderOrderedByNewest()->getQuery()->getResult(),
        );
    }

    public function getSubLocationsCount(LocationInterface $location): int
    {
        if (!$location instanceof AlbumsRootLocation) {
            return 0;
        }

        return $this->albumRepository->createQueryBuilderOrderedByNewest()
            ->select('COUNT(album.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getSubItems(LocationInterface $location, int $offset = 0, int $limit = 25): iterable
    {
        if (!$location instanceof AlbumBrowserLocation) {
            return [];
        }

        $photos = $this->photoRepository->createQueryBuilderForAlbum($location->getAlbum()->getSlug())
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return array_map(fn (Photo $photo) => new PhotoBrowserItem($photo), $photos);
    }

    public function getSubItemsCount(LocationInterface $location): int
    {
        if (!$location instanceof AlbumBrowserLocation) {
            return 0;
        }

        return $this->photoRepository->createQueryBuilderForAlbum($location->getAlbum()->getSlug())
            ->select('COUNT(photo.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function searchItems(SearchQuery $searchQuery): SearchResultInterface
    {
        $photos = $this->photoRepository->createQueryBuilderOrderedByNewest($searchQuery->searchText)
            ->setFirstResult($searchQuery->offset)
            ->setMaxResults($searchQuery->limit)
            ->getQuery()
            ->getResult();

        return new SearchResult(array_map(fn (Photo $photo) => new PhotoBrowserItem($photo), $photos));
    }

    public function searchItemsCount(SearchQuery $searchQuery): int
    {
        return $this->photoRepository->createQueryBuilderOrderedByNewest($searchQuery->searchText)
            ->select('COUNT(photo.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
