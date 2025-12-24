<?php

namespace App\Repository;

use App\Entity\GithubRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GithubRepoRepository>
 */
class GithubRepoRepository extends ServiceEntityRepository implements Selectable
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GithubRepository::class);
    }

    //    /**
    //     * @return ProjectRepository[] Returns an array of ProjectRepository objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ProjectRepository
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function findReposByIds(array $ids): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.id IN (:ids)')
            ->orderBy('q.stars_count', 'DESC')
            ->setParameter('ids', $ids)
            ->getQuery()->getResult();
    }

    public function countByFilter(array $filter = []): int
    {
        $qb = $this->createQueryBuilder('g')
            ->select('COUNT(g.id)');

        if (!empty($search)) {
            $qb->andWhere('g.name LIKE :search')
                ->setParameter('search', "%{$search}%");
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
