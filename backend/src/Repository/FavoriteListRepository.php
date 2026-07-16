<?php

namespace App\Repository;

use App\Entity\FavoriteList;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FavoriteList>
 */
class FavoriteListRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FavoriteList::class);
    }

    /**
     * @return FavoriteList[]
     */
    public function findByOwner(User $owner): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('l.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByIdAndOwner(int $id, User $owner): ?FavoriteList
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.id = :id')
            ->andWhere('l.owner = :owner')
            ->setParameter('id', $id)
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function nameExistsForOwner(User $owner, string $name): bool
    {
        $count = $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.owner = :owner')
            ->andWhere('l.name = :name')
            ->setParameter('owner', $owner)
            ->setParameter('name', $name)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
