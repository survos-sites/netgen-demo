<?php

namespace App\Factory;

use App\Entity\Photo;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

use function Symfony\Component\String\u;

/**
 * @extends PersistentObjectFactory<Photo>
 */
final class PhotoFactory extends PersistentObjectFactory
{
    private ?array $availableImages = null;

    public function __construct()
    {
    }

    #[\Override]
    public static function class(): string
    {
        return Photo::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'album' => AlbumFactory::new(),
            'title' => (string) u(self::faker()->words(3, true))->title(),
            'imageFilename' => self::faker()->randomElement($this->getImages()),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        $fs = new Filesystem();

        return $this
            ->afterInstantiate(function (Photo $photo) use ($fs): void {
                $targetFile = __DIR__ . sprintf('/../DataFixtures/images/%s', $photo->getImageFilename());
                if (!file_exists($targetFile)) {
                    return;
                }

                $newFilename = self::faker()->slug(2) . '.png';
                $fs->copy(
                    $targetFile,
                    __DIR__ . '/../../public/uploads/photos/' . $newFilename,
                );
                $photo->setImageFilename($newFilename);
            })
        ;
    }

    private function getImages(): array
    {
        if ($this->availableImages === null) {
            $finder = new Finder();
            $finder->in(__DIR__ . '/../DataFixtures/images')
                ->files();

            $this->availableImages = [];
            foreach ($finder as $file) {
                $this->availableImages[] = $file->getFilename();
            }
        }

        return $this->availableImages;
    }
}
