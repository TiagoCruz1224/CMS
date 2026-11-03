<?php

namespace App\Controller\Api;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/categories', requirements: ['id' => '\d{1,18}'])]
class CategoryController extends AbstractController
{
    use ValidationErrorResponse;

    public function __construct(
        private CategoryRepository $categoryRepository,
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('', name: 'api_categories_list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $categories = $this->categoryRepository->findAll();

        $json = $this->serializer->serialize($categories, 'json', [
            'groups' => ['category:read']
        ]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'api_categories_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $category = $this->categoryRepository->find($id);

        if (!$category) {
            return $this->json([
                'error' => 'Category not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $json = $this->serializer->serialize($category, 'json', [
            'groups' => ['category:read']
        ]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('', name: 'api_categories_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = json_decode($request->getContent(), true);

        if (!isset($data['name']) || !isset($data['slug'])) {
            return $this->json([
                'error' => 'Name and slug are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        $category = new Category();
        $category->setName($data['name']);
        $category->setSlug($data['slug']);

        if (isset($data['description'])) {
            $category->setDescription($data['description']);
        }

        $errors = $this->validator->validate($category);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        $json = $this->serializer->serialize($category, 'json', [
            'groups' => ['category:read']
        ]);

        return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    }

    #[Route('/{id}', name: 'api_categories_update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $category = $this->categoryRepository->find($id);

        if (!$category) {
            return $this->json([
                'error' => 'Category not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $category->setName($data['name']);
        }

        if (isset($data['slug'])) {
            $category->setSlug($data['slug']);
        }

        if (isset($data['description'])) {
            $category->setDescription($data['description']);
        }

        $errors = $this->validator->validate($category);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        $this->entityManager->flush();

        $json = $this->serializer->serialize($category, 'json', [
            'groups' => ['category:read']
        ]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'api_categories_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $category = $this->categoryRepository->find($id);

        if (!$category) {
            return $this->json([
                'error' => 'Category not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($category);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Category deleted successfully'
        ], Response::HTTP_OK);
    }
}
