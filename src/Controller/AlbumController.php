<?php

namespace App\Controller;

use App\Entity\Album;
use App\Repository\AlbumRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AlbumController extends AbstractController
{
    #[Route('/albums', name: 'app_albums')]
    public function albums(AlbumRepository $albumRepository): Response
    {
        return $this->render('albums/list.html.twig', [
            'albums' => $albumRepository->createQueryBuilderOrderedByNewest()->getQuery()->getResult(),
        ]);
    }

    #[Route('/albums/{slug}', name: 'app_albums_show')]
    public function albumsShow(#[MapEntity(mapping: ['slug' => 'slug'])] Album $album): Response
    {
        return $this->render('albums/show.html.twig', [
            'album' => $album,
        ]);
    }

    /**
     * The "FotoStory" page: one Layout, reused for every album. Photos come
     * from the "photos_in_album" contextual query type reading {album}
     * live from this route - see PhotosInAlbumQueryTypeHandler.
     */
    #[Route('/stories/{album}', name: 'app_story')]
    public function story(string $album, AlbumRepository $albumRepository): Response
    {
        return $this->render('albums/story.html.twig', [
            'album' => $albumRepository->findOneBy(['slug' => $album]),
        ]);
    }
}
