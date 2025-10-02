<?php

namespace App\Controller\Api;

use App\Entity\Page;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\PageRepository;
use App\Service\PageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/pages')]
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
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        $status = $request->query->get('status');

        if ($status === 'published') {
            $pages = $this->pageService->findPublished();
            $result = [
                'data' => $pages,
                'total' => count($pages),
            ];
        } else {
            $result = $this->pageService->findPaginated($page, $limit);
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

        if (!$page) {
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

        try {
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
                if ($category) {
                    $pageData['category'] = $category;
                }
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

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to create page: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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

        // Check if user is the author or admin
        if ($page->getAuthor()->getId() !== $user->getId() && !in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->json([
                'error' => 'Access denied'
            ], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        try {
            if (isset($data['categoryId'])) {
                $category = $this->categoryRepository->find($data['categoryId']);
                if ($category) {
                    $data['category'] = $category;
                }
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

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to update page: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'api_pages_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $page = $this->pageRepository->find($id);

        if (!$page) {
            return $this->json([
                'error' => 'Page not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check if user is the author or admin
        if ($page->getAuthor()->getId() !== $user->getId() && !in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->json([
                'error' => 'Access denied'
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $this->pageService->deletePage($page);

            return $this->json([
                'message' => 'Page deleted successfully'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to delete page: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
