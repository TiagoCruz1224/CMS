<?php

namespace App\Service;

use App\Entity\Page;
use App\Entity\PageRevision;
use App\Entity\User;
use App\Repository\PageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PageService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PageRepository $pageRepository,
        private SluggerInterface $slugger,
        private ValidatorInterface $validator
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createPage(string $title, User $author, array $data = []): Page
    {
        $page = new Page();
        $page->setTitle($title);
        $page->setAuthor($author);
        
        // Generate slug from title if not provided
        $slug = $data['slug'] ?? $this->generateSlug($title);
        $page->setSlug($slug);

        if (isset($data['content'])) {
            $page->setContent($data['content']);
        }

        if (isset($data['status'])) {
            $page->setStatus($data['status']);
        }

        if (isset($data['category'])) {
            $page->setCategory($data['category']);
        }

        // SEO fields
        if (isset($data['metaTitle'])) {
            $page->setMetaTitle($data['metaTitle']);
        }

        if (isset($data['metaDescription'])) {
            $page->setMetaDescription($data['metaDescription']);
        }

        if (isset($data['metaKeywords'])) {
            $page->setMetaKeywords($data['metaKeywords']);
        }

        if (isset($data['ogTags'])) {
            $page->setOgTags($data['ogTags']);
        }

        if (isset($data['template'])) {
            $page->setTemplate($data['template']);
        }

        if (isset($data['publishAt'])) {
            $page->setPublishAt($data['publishAt']);
        }

        if (isset($data['unpublishAt'])) {
            $page->setUnpublishAt($data['unpublishAt']);
        }

        if (isset($data['isIndexable'])) {
            $page->setIsIndexable($data['isIndexable']);
        }

        $this->validate($page);
        $this->entityManager->persist($page);
        $this->entityManager->flush();

        // Create initial revision
        $this->createRevision($page, $author, 'Initial version');

        return $page;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updatePage(Page $page, array $data, ?User $user = null, ?string $changeNote = null): Page
    {
        if (isset($data['title'])) {
            $page->setTitle($data['title']);
        }

        if (isset($data['slug'])) {
            $page->setSlug($data['slug']);
        }

        if (isset($data['content'])) {
            $page->setContent($data['content']);
        }

        if (isset($data['status'])) {
            $page->setStatus($data['status']);
        }

        if (isset($data['category'])) {
            $page->setCategory($data['category']);
        }

        // SEO fields
        if (isset($data['metaTitle'])) {
            $page->setMetaTitle($data['metaTitle']);
        }

        if (isset($data['metaDescription'])) {
            $page->setMetaDescription($data['metaDescription']);
        }

        if (isset($data['metaKeywords'])) {
            $page->setMetaKeywords($data['metaKeywords']);
        }

        if (isset($data['ogTags'])) {
            $page->setOgTags($data['ogTags']);
        }

        if (isset($data['template'])) {
            $page->setTemplate($data['template']);
        }

        if (isset($data['publishAt'])) {
            $page->setPublishAt($data['publishAt']);
        }

        if (isset($data['unpublishAt'])) {
            $page->setUnpublishAt($data['unpublishAt']);
        }

        if (isset($data['isIndexable'])) {
            $page->setIsIndexable($data['isIndexable']);
        }

        $this->validate($page);
        $this->entityManager->flush();

        // Create revision after update
        if ($user) {
            $this->createRevision($page, $user, $changeNote ?? 'Page updated');
        }

        return $page;
    }

    public function deletePage(Page $page): void
    {
        $this->entityManager->remove($page);
        $this->entityManager->flush();
    }

    /**
     * @return list<Page>
     */
    public function findByAuthor(User $author): array
    {
        return $this->pageRepository->findByAuthor($author);
    }

    /**
     * @return list<Page>
     */
    public function findPublished(): array
    {
        return $this->pageRepository->findPublished();
    }

    /**
     * @return array{data: list<Page>, total: int, page: int, limit: int, totalPages: int}
     */
    public function findPaginated(int $page = 1, int $limit = 10, bool $includeDrafts = false, ?User $author = null): array
    {
        $total = $this->pageRepository->countAll($includeDrafts, $author);

        return [
            'data' => $this->pageRepository->findPaginated($page, $limit, $includeDrafts, $author),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => (int) ceil($total / $limit)
        ];
    }

    public function createRevision(Page $page, User $user, ?string $changeNote = null): PageRevision
    {
        $revision = new PageRevision();
        $revision->setPage($page);
        $revision->setTitle((string) $page->getTitle());
        $revision->setContent($page->getContent());
        $revision->setCreatedBy($user);
        
        if ($changeNote) {
            $revision->setChangeNote($changeNote);
        }

        // Store blocks as JSON for the revision
        $blocks = [];
        foreach ($page->getBlocks() as $block) {
            $blocks[] = [
                'type' => $block->getType(),
                'content' => $block->getContent(),
                'position' => $block->getPosition(),
                'settings' => $block->getSettings(),
            ];
        }
        $revision->setBlocks($blocks);

        $this->entityManager->persist($revision);
        $this->entityManager->flush();

        return $revision;
    }

    private function generateSlug(string $text): string
    {
        return strtolower($this->slugger->slug($text));
    }

    // Invalid pages are rejected before anything is written
    private function validate(Page $page): void
    {
        $violations = $this->validator->validate($page);

        if (count($violations) > 0) {
            throw new ValidationFailedException($page, $violations);
        }
    }
}
