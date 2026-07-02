<?php

namespace App\DataFixtures;

use App\Factory\RecipeFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        RecipeFactory::createMany(25);

        $manager->flush();
    }
}
