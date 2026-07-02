<?php

namespace App\Layouts;

use App\Repository\RecipeRepository;
use Netgen\Layouts\Item\ValueLoaderInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('netgen_layouts.cms_value_loader', ['value_type' => 'doctrine_recipe'])]
class RecipeValueLoader implements ValueLoaderInterface
{
    public function __construct(private RecipeRepository $recipeRepository)
    {
    }

    public function load($id): ?object
    {
        return $this->recipeRepository->find($id);
    }

    public function loadByRemoteId($remoteId): ?object
    {
        return $this->load($remoteId);
    }
}
