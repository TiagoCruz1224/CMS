<?php

namespace App\Controller\Api;

use App\Entity\Page;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\PageRepository;
use App\Security\Voter\PageVoter;
use App\Service\PageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/pages', requirements: ['id' => '\d{1,18}'])]
class PageController extends AbstractController
{
    public function __construct(
        private PageService $pageService,
        private PageRepository $pageRepository,
        private CategoryRepository $categoryRepository,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('', name: 'api_pages_list', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $page = $request->query->filter('page', 1, \FILTER_VALIDATE_INT, ['flags' => \FILTER_NULL_ON_FAILURE]);
        $limit = $request->query->filter('limit', 10, \FILTER_VALIDATE_INT, ['flags' => \FILTER_NULL_ON_FAILURE]);
        if ($page === null || $limit === null) {
            return $this->json([
                'error' => 'page and limit must be integers'
            ], Response::HTTP_BAD_REQUEST);
        }
        $page = max(1, $page);
        $limit = min(100, max(1, $limit));
        $status = $request->query->get('status');

        if ($status === 'published') {
            $pages = $this->pageService->findPublished();
            $result = [
                'data' => $pages,
                'total' => count($pages),
            ];
        } else {
            $user = $this->getUser();
            $result = $this->pageService->findPaginated(
                $page,
                $limit,
                $this->isGranted('ROLE_ADMIN'),
                $user instanceof User ? $user : null
            );
        }

        $json = $this->serializer->serialize($result, 'json', [
            'groups' => ['page:read']
        ]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'api_pages_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $page = $this->pageRepository->find($id);

        // Hidden pages answer 404 rather than 403 so their existence is not revealed
        if (!$page || !$this->isGranted(PageVoter::VIEW, $page)) {
            return $this->json([
                'error' => 'Page not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $json = $this->serializer->serialize($page, 'json', [
            'groups' => ['page:read']
        ]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('', name: 'api_pages_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);

        if (!isset($data['title'])) {
            return $this->json([
                'error' => 'Title is required'
            ], Response::HTTP_BAD_REQUEST);
        }

        $pageData = [];

        if (isset($data['slug'])) {
            $pageData['slug'] = $data['slug'];
        }

        if (isset($data['content'])) {
            $pageData['content'] = $data['content'];
        }

        if (isset($data['status'])) {
            $pageData['status'] = $data['status'];
        }

        if (isset($data['categoryId'])) {
            $category = $this->categoryRepository->find($data['categoryId']);
            if (!$category) {
                return $this->json([
                    'error' => 'Category not found'
                ], Response::HTTP_BAD_REQUEST);
            }
            $pageData['category'] = $category;
        }

        $page = $this->pageService->createPage($data['title'], $user, $pageData);

        $errors = $this->validator->validate($page);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json([
                'errors' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        $json = $this->serializer->serialize($page, 'json', [
            'groups' => ['page:read']
        ]);

        return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    }

    #[Route('/{id}', name: 'api_pages_update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $page = $this->pageRepository->find($id);

        if (!$page) {
            return $this->json([
                'error' => 'Page not found'
            ], Response::HTTP_NOT_FOUND);
        }

        if (!$this->isGranted(PageVoter::EDIT, $page)) {
            return $this->json([
                'error' => 'Access denied'
            ], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['categoryId'])) {
            $category = $this->categoryRepository->find($data['categoryId']);
            if (!$category) {
                return $this->json([
                    'error' => 'Category not found'
                ], Response::HTTP_BAD_REQUEST);
            }
            $data['category'] = $category;
            unset($data['categoryId']);
        }

        // Convert date strings to DateTimeImmutable
        if (isset($data['publishAt']) && is_string($data['publishAt'])) {
            $data['publishAt'] = new \DateTimeImmutable($data['publishAt']);
        }

        if (isset($data['unpublishAt']) && is_string($data['unpublishAt'])) {
            $data['unpublishAt'] = new \DateTimeImmutable($data['unpublishAt']);
        }

        $changeNote = $data['changeNote'] ?? null;
        unset($data['changeNote']);

        $this->pageService->updatePage($page, $data, $user, $changeNote);

        $errors = $this->validator->validate($page);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json([
                'errors' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        $json = $this->serializer->serialize($page, 'json', [
            'groups' => ['page:read']
        ]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'api_pages_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $page = $this->pageRepository->find($id);

        if (!$page) {
            return $this->json([
                'error' => 'Page not found'
            ], Response::HTTP_NOT_FOUND);
        }

        if (!$this->isGranted(PageVoter::EDIT, $page)) {
            return $this->json([
                'error' => 'Access denied'
            ], Response::HTTP_FORBIDDEN);
        }

        $this->pageService->deletePage($page);

        return $this->json([
            'message' => 'Page deleted successfully'
        ], Response::HTTP_OK);
    }
}
