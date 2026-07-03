<?php

namespace App\DataFixtures;

use App\Factory\AlbumFactory;
use App\Factory\PhotoFactory;
use App\Factory\RecipeFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        RecipeFactory::createMany(25);

        foreach (['Summer Vacation', 'Family Reunion', 'City Trip', 'Garden Party', 'Winter Getaway'] as $albumName) {
            $album = AlbumFactory::createOne(['name' => $albumName]);
            PhotoFactory::createMany(random_int(4, 7), ['album' => $album]);
        }

        $manager->flush();
    }
}
