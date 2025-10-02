<?php

namespace App\Service;

use App\Entity\Page;
use App\Entity\PageRevision;
use App\Entity\User;
use App\Repository\PageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class PageService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PageRepository $pageRepository,
        private SluggerInterface $slugger
    ) {
    }

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

        $this->entityManager->persist($page);
        $this->entityManager->flush();

        // Create initial revision
        $this->createRevision($page, $author, 'Initial version');

        return $page;
    }

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

    public function findByAuthor(User $author): array
    {
        return $this->pageRepository->findByAuthor($author);
    }

    public function findPublished(): array
    {
        return $this->pageRepository->findPublished();
    }

    public function findPaginated(int $page = 1, int $limit = 10): array
    {
        return [
            'data' => $this->pageRepository->findPaginated($page, $limit),
            'total' => $this->pageRepository->countAll(),
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($this->pageRepository->countAll() / $limit)
        ];
    }

    public function createRevision(Page $page, User $user, ?string $changeNote = null): PageRevision
    {
        $revision = new PageRevision();
        $revision->setPage($page);
        $revision->setTitle($page->getTitle());
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
}
