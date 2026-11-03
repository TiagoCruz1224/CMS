<?php

namespace App\Controller\Api;

use App\Entity\Media;
use App\Repository\BlockRepository;
use App\Repository\MediaRepository;
use App\Security\Voter\PageVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/media', requirements: ['id' => '\d{1,18}'])]
class MediaController extends AbstractController
{
    public function __construct(
        private MediaRepository $mediaRepository,
        private BlockRepository $blockRepository,
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('', name: 'api_media_list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        // ponytail: filters in PHP after findAll(); move the rule into a query if the library grows large
        $media = array_values(array_filter(
            $this->mediaRepository->findAll(),
            fn (Media $item) => $this->isGranted(PageVoter::VIEW, $item->getBlock()?->getPage())
        ));

        $json = $this->serializer->serialize($media, 'json', [
            'groups' => ['media:read']
        ]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'api_media_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $media = $this->mediaRepository->find($id);

        if (!$media || !$this->isGranted(PageVoter::VIEW, $media->getBlock()?->getPage())) {
            return $this->json([
                'error' => 'Media not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $json = $this->serializer->serialize($media, 'json', [
            'groups' => ['media:read']
        ]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('', name: 'api_media_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['filename']) || !isset($data['type']) || !isset($data['url']) || !isset($data['blockId'])) {
            return $this->json([
                'error' => 'Filename, type, url and blockId are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        $block = $this->blockRepository->find($data['blockId']);

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

        $media = new Media();
        $media->setFilename($data['filename']);
        $media->setType($data['type']);
        $media->setUrl($data['url']);
        $media->setBlock($block);

        if (isset($data['mimeType'])) {
            $media->setMimeType($data['mimeType']);
        }

        if (isset($data['size'])) {
            $media->setSize($data['size']);
        }

        if (isset($data['alt'])) {
            $media->setAlt($data['alt']);
        }

        $errors = $this->validator->validate($media);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json([
                'errors' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($media);
        $this->entityManager->flush();

        $json = $this->serializer->serialize($media, 'json', [
            'groups' => ['media:read']
        ]);

        return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    }

    #[Route('/{id}', name: 'api_media_update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $media = $this->mediaRepository->find($id);

        if (!$media) {
            return $this->json([
                'error' => 'Media not found'
            ], Response::HTTP_NOT_FOUND);
        }

        if (!$this->isGranted(PageVoter::EDIT, $media->getBlock()?->getPage())) {
            return $this->json([
                'error' => 'Access denied'
            ], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['filename'])) {
            $media->setFilename($data['filename']);
        }

        if (isset($data['type'])) {
            $media->setType($data['type']);
        }

        if (isset($data['url'])) {
            $media->setUrl($data['url']);
        }

        if (isset($data['alt'])) {
            $media->setAlt($data['alt']);
        }

        if (isset($data['mimeType'])) {
            $media->setMimeType($data['mimeType']);
        }

        if (isset($data['size'])) {
            $media->setSize($data['size']);
        }

        $errors = $this->validator->validate($media);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json([
                'errors' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        $json = $this->serializer->serialize($media, 'json', [
            'groups' => ['media:read']
        ]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'api_media_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $media = $this->mediaRepository->find($id);

        if (!$media) {
            return $this->json([
                'error' => 'Media not found'
            ], Response::HTTP_NOT_FOUND);
        }

        if (!$this->isGranted(PageVoter::EDIT, $media->getBlock()?->getPage())) {
            return $this->json([
                'error' => 'Access denied'
            ], Response::HTTP_FORBIDDEN);
        }

        $this->entityManager->remove($media);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Media deleted successfully'
        ], Response::HTTP_OK);
    }
}
