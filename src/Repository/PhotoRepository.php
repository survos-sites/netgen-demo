<?php

namespace App\Repository;

use App\Entity\Photo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Photo>
 *
 * @method Photo|null find($id, $lockMode = null, $lockVersion = null)
 * @method Photo|null findOneBy(array $criteria, array $orderBy = null)
 * @method Photo[]    findAll()
 * @method Photo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Photo::class);
    }

    public function createQueryBuilderOrderedByNewest(?string $search = null): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('photo')
            ->orderBy('photo.createdAt', 'DESC');

        if ($search) {
            $queryBuilder->andWhere('photo.title LIKE :term')
                ->setParameter('term', '%' . $search . '%');
        }

        return $queryBuilder;
    }

    public function createQueryBuilderForAlbum(string $albumSlug): QueryBuilder
    {
        return $this->createQueryBuilder('photo')
            ->innerJoin('photo.album', 'album')
            ->andWhere('album.slug = :slug')
            ->setParameter('slug', $albumSlug)
            ->orderBy('photo.createdAt', 'ASC');
    }
}
