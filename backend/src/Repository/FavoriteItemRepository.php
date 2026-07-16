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
            // Secondary sort by id breaks ties between items added within
            // the same second, since createdAt only has second precision.
            ->orderBy('i.createdAt', 'DESC')
            ->addOrderBy('i.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByIdAndList(int $id, FavoriteList $list): ?FavoriteItem
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.id = :id')
            ->andWhere('i.favoriteList = :list')
            ->setParameter('id', $id)
            ->setParameter('list', $list)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countByList(FavoriteList $list): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.favoriteList = :list')
            ->setParameter('list', $list)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
