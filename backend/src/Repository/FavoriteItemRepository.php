<?php

namespace App\Repository;

use App\Entity\FavoriteItem;
use App\Entity\FavoriteList;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FavoriteItem>
 */
class FavoriteItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FavoriteItem::class);
    }

    /**
     * @return FavoriteItem[]
     */
    public function findByListOrderedByModified(FavoriteList $list): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.favoriteList = :list')
            ->setParameter('list', $list)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
