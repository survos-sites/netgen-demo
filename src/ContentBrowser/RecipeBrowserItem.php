<?php

namespace App\ContentBrowser;

use App\Entity\Recipe;
use Netgen\ContentBrowser\Item\ItemInterface;

class RecipeBrowserItem implements ItemInterface
{
    public function __construct(private Recipe $recipe)
    {
    }

    public int|string $value { get => $this->recipe->getId(); }

    public string $name { get => $this->recipe->getName(); }

    public bool $isVisible { get => true; }

    public bool $isSelectable { get => true; }

    public function getRecipe(): Recipe
    {
        return $this->recipe;
    }
}
