<?php

namespace App\Layouts;

use App\Repository\RecipeRepository;
use Netgen\Layouts\API\Values\Collection\Query;
use Netgen\Layouts\Collection\QueryType\QueryTypeHandlerInterface;
use Netgen\Layouts\Parameters\ParameterBuilderInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A contextual query type: unlike LatestRecipeQueryTypeHandler (whose "term"
 * is stored per-collection), this reads its filter from the current
 * request's route parameter. One Layout + one Rule (targeting the
 * "app_recipes_by_tag" route) then serves every tag, e.g. /recipes/tag/summer
 * and /recipes/tag/picnic both resolve the same Layout, this query just
 * returns different results depending on the live request.
 */
#[AutoconfigureTag('netgen_layouts.query_type_handler', ['type' => 'recipes_by_tag'])]
class RecipesByTagQueryTypeHandler implements QueryTypeHandlerInterface
{
    public function __construct(
        private RecipeRepository $recipeRepository,
        private RequestStack $requestStack,
    ) {
    }

    public function buildParameters(ParameterBuilderInterface $builder): void
    {
        // no stored parameters - the tag comes from the request, not from
        // per-collection configuration
    }

    public function getValues(Query $query, int $offset = 0, ?int $limit = null): iterable
    {
        $tag = $this->getCurrentTag();
        if ($tag === null) {
            return [];
        }

        return $this->recipeRepository->findByTag($tag, $offset, $limit);
    }

    public function getCount(Query $query): int
    {
        $tag = $this->getCurrentTag();

        return $tag !== null ? $this->recipeRepository->countByTag($tag) : 0;
    }

    public function isContextual(Query $query): bool
    {
        return true;
    }

    private function getCurrentTag(): ?string
    {
        return $this->requestStack->getCurrentRequest()?->attributes->getString('tag') ?: null;
    }
}
