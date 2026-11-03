<?php

namespace App\Repository;

use App\Entity\Page;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Page>
 */
class PageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Page::class);
    }

    /**
     * Find all pages by a specific user
     */
    public function findByAuthor(User $author): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.author = :author')
            ->setParameter('author', $author)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find pages that are public right now (same rule as Page::isPublished())
     */
    public function findPublished(): array
    {
        return $this->visibleTo(false, null)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find page by slug
     */
    public function findOneBySlug(string $slug): ?Page
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /**
     * Find pages with pagination. Unless $includeDrafts is set, only published
     * pages and pages written by $author are returned.
     */
    public function findPaginated(int $page = 1, int $limit = 10, bool $includeDrafts = false, ?User $author = null): array
    {
        $query = $this->visibleTo($includeDrafts, $author)
            ->orderBy('p.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery();

        return $query->getResult();
    }

    /**
     * Count the pages findPaginated() can return
     */
    public function countAll(bool $includeDrafts = false, ?User $author = null): int
    {
        return $this->visibleTo($includeDrafts, $author)
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function visibleTo(bool $includeDrafts, ?User $author): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p');

        if ($includeDrafts) {
            return $qb;
        }

        // Same rule as Page::isPublished()
        $published = "p.status IN ('published', 'scheduled')"
            . ' AND (p.publishAt IS NULL OR p.publishAt <= :now)'
            . ' AND (p.unpublishAt IS NULL OR p.unpublishAt >= :now)';
        $qb->setParameter('now', new \DateTimeImmutable());

        if ($author) {
            return $qb->andWhere("($published) OR p.author = :author")->setParameter('author', $author);
        }

        return $qb->andWhere($published);
    }

    /**
     * Find pages by category
     */
    public function findByCategory(int $categoryId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.category = :categoryId')
            ->setParameter('categoryId', $categoryId)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
