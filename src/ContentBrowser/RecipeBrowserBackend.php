<?php

namespace App\ContentBrowser;

use App\Entity\Recipe;
use App\Repository\RecipeRepository;
use Netgen\ContentBrowser\Backend\BackendInterface;
use Netgen\ContentBrowser\Backend\SearchQuery;
use Netgen\ContentBrowser\Backend\SearchResult;
use Netgen\ContentBrowser\Backend\SearchResultInterface;
use Netgen\ContentBrowser\Item\ItemInterface;
use Netgen\ContentBrowser\Item\LocationInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('netgen_content_browser.backend', [ 'item_type' => 'doctrine_recipe' ])]
class RecipeBrowserBackend implements BackendInterface
{
    public function __construct(private RecipeRepository $recipeRepository)
    {
    }

    public function getSections(): iterable
    {
        return [new BrowserRootLocation()];
    }

    public function loadLocation(int|string $id): LocationInterface
    {
        if ($id === '0' || $id === 0) {
            return new BrowserRootLocation();
        }

        throw new \InvalidArgumentException(sprintf('Invalid location "%s"', $id));
    }

    public function loadItem(int|string $value): ItemInterface
    {
        return new RecipeBrowserItem($this->recipeRepository->find($value));
    }

    public function getSubLocations(LocationInterface $location): iterable
    {
        return [];
    }

    public function getSubLocationsCount(LocationInterface $location): int
    {
        return 0;
    }

    public function getSubItems(LocationInterface $location, int $offset = 0, int $limit = 25): iterable
    {
        $recipes = $this->recipeRepository
            ->createQueryBuilderOrderedByNewest()
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return array_map(fn(Recipe $recipe) => new RecipeBrowserItem($recipe), $recipes);
    }

    public function getSubItemsCount(LocationInterface $location): int
    {
        return $this->recipeRepository
            ->createQueryBuilderOrderedByNewest()
            ->select('COUNT(recipe.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function searchItems(SearchQuery $searchQuery): SearchResultInterface
    {
        $recipes = $this->recipeRepository
            ->createQueryBuilderOrderedByNewest($searchQuery->searchText)
            ->setFirstResult($searchQuery->offset)
            ->setMaxResults($searchQuery->limit)
            ->getQuery()
            ->getResult();

        return new SearchResult(array_map(fn(Recipe $recipe) => new RecipeBrowserItem($recipe), $recipes));
    }

    public function searchItemsCount(SearchQuery $searchQuery): int
    {
        return $this->recipeRepository
            ->createQueryBuilderOrderedByNewest($searchQuery->searchText)
            ->select('COUNT(recipe.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
