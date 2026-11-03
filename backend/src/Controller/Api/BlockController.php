<?php

namespace App\Controller\Api;

use App\Entity\Block;
use App\Repository\BlockRepository;
use App\Repository\PageRepository;
use App\Security\Voter\PageVoter;
use App\Service\BlockService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/blocks', requirements: ['id' => '\d{1,18}'])]
class BlockController extends AbstractController
{
    public function __construct(
        private BlockService $blockService,
        private BlockRepository $blockRepository,
        private PageRepository $pageRepository,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('/{id}', name: 'api_blocks_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $block = $this->blockRepository->find($id);

        if (!$block || !$this->isGranted(PageVoter::VIEW, $block->getPage())) {
            return $this->json([
                'error' => 'Block not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $json = $this->serializer->serialize($block, 'json', [
            'groups' => ['block:read']
        ]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('', name: 'api_blocks_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['type']) || !isset($data['pageId'])) {
            return $this->json([
                'error' => 'Type and pageId are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        $page = $this->pageRepository->find($data['pageId']);

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

        $block = $this->blockService->createBlock($data['type'], $page, $data);

        $errors = $this->validator->validate($block);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json([
                'errors' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        $json = $this->serializer->serialize($block, 'json', [
            'groups' => ['block:read']
        ]);

        return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    }

    #[Route('/{id}', name: 'api_blocks_update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $block = $this->blockRepository->find($id);

        if (!$block) {
            return $this->json([
                'error' => 'Block not found'
            ], Response::HTTP_NOT_FOUND);
        }

        if (!$this->isGranted(PageVoter::EDIT, $block->getPage())) {
            return $this->json([
                'error' => 'Access denied'
            ], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        $this->blockService->updateBlock($block, $data);

        $errors = $this->validator->validate($block);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json([
                'errors' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        $json = $this->serializer->serialize($block, 'json', [
            'groups' => ['block:read']
        ]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'api_blocks_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $block = $this->blockRepository->find($id);

        if (!$block) {
            return $this->json([
                'error' => 'Block not found'
            ], Response::HTTP_NOT_FOUND);
        }

        if (!$this->isGranted(PageVoter::EDIT, $block->getPage())) {
            return $this->json([
                'error' => 'Access denied'
            ], Response::HTTP_FORBIDDEN);
        }

        $this->blockService->deleteBlock($block);

        return $this->json([
            'message' => 'Block deleted successfully'
        ], Response::HTTP_OK);
    }
}
