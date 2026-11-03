<?php

namespace App\Repository;

use App\Entity\PageRevision;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PageRevision>
 */
class PageRevisionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PageRevision::class);
    }

    /**
     * Find all revisions for a page
     * @return list<PageRevision>
     */
    public function findByPage(int $pageId, int $limit = 10): array
    {
        return $this->createQueryBuilder('pr')
            ->andWhere('pr.page = :pageId')
            ->setParameter('pageId', $pageId)
            ->orderBy('pr.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
