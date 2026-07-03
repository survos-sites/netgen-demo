<?php

namespace App\Layouts;

use App\Repository\PhotoRepository;
use Netgen\Layouts\API\Values\Collection\Query;
use Netgen\Layouts\Collection\QueryType\QueryTypeHandlerInterface;
use Netgen\Layouts\Parameters\ParameterBuilderInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * This is the "FotoStory" pattern: one Layout, reused for every album, whose
 * photos are read live from the current request's {album} route parameter -
 * exactly like RecipesByTagQueryTypeHandler reads {tag}. The "story" (the
 * layout/blocks an editor arranges) and the "content" (which album's photos
 * show up) are two independent things composed at render time.
 */
#[AutoconfigureTag('netgen_layouts.query_type_handler', ['type' => 'photos_in_album'])]
class PhotosInAlbumQueryTypeHandler implements QueryTypeHandlerInterface
{
    public function __construct(
        private PhotoRepository $photoRepository,
        private RequestStack $requestStack,
    ) {
    }

    public function buildParameters(ParameterBuilderInterface $builder): void
    {
        // no stored parameters - the album comes from the request
    }

    public function getValues(Query $query, int $offset = 0, ?int $limit = null): iterable
    {
        $albumSlug = $this->getCurrentAlbumSlug();
        if ($albumSlug === null) {
            return [];
        }

        return $this->photoRepository->createQueryBuilderForAlbum($albumSlug)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getCount(Query $query): int
    {
        $albumSlug = $this->getCurrentAlbumSlug();
        if ($albumSlug === null) {
            return 0;
        }

        return (int) $this->photoRepository->createQueryBuilderForAlbum($albumSlug)
            ->select('COUNT(photo.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function isContextual(Query $query): bool
    {
        return true;
    }

    private function getCurrentAlbumSlug(): ?string
    {
        return $this->requestStack->getCurrentRequest()?->attributes->getString('album') ?: null;
    }
}
