<?php

namespace App\Controller\Api;

use App\Repository\PageRepository;
use App\Repository\PageRevisionRepository;
use App\Security\Voter\PageVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/pages', requirements: ['id' => '\d{1,18}', 'pageId' => '\d{1,18}', 'revisionId' => '\d{1,18}'])]
class PageRevisionController extends AbstractController
{
    public function __construct(
        private PageRepository $pageRepository,
        private PageRevisionRepository $revisionRepository,
        private SerializerInterface $serializer
    ) {
    }

    #[Route('/{id}/revisions', name: 'api_page_revisions', methods: ['GET'])]
    public function revisions(int $id): JsonResponse
    {
        $page = $this->pageRepository->find($id);

        if (!$page || !$this->isGranted(PageVoter::EDIT, $page)) {
            return $this->json([
                'error' => 'Page not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $revisions = $this->revisionRepository->findByPage($id);

        $json = $this->serializer->serialize($revisions, 'json', [
            'groups' => ['revision:read']
        ]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{pageId}/revisions/{revisionId}', name: 'api_page_revision_show', methods: ['GET'])]
    public function showRevision(int $pageId, int $revisionId): JsonResponse
    {
        $page = $this->pageRepository->find($pageId);

        if (!$page || !$this->isGranted(PageVoter::EDIT, $page)) {
            return $this->json([
                'error' => 'Page not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $revision = $this->revisionRepository->find($revisionId);

        if (!$revision || $revision->getPage()->getId() !== $pageId) {
            return $this->json([
                'error' => 'Revision not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $json = $this->serializer->serialize($revision, 'json', [
            'groups' => ['revision:read']
        ]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
