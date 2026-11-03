<?php

namespace App\Service;

use App\Entity\Block;
use App\Entity\Page;
use Doctrine\ORM\EntityManagerInterface;

class BlockService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createBlock(string $type, Page $page, array $data = []): Block
    {
        $block = new Block();
        $block->setType($type);
        $block->setPage($page);
        
        if (isset($data['content'])) {
            $block->setContent($data['content']);
        }

        if (isset($data['position'])) {
            $block->setPosition($data['position']);
        } else {
            // Set position to last
            $block->setPosition($page->getBlocks()->count());
        }

        if (isset($data['settings'])) {
            $block->setSettings($data['settings']);
        }

        $this->entityManager->persist($block);
        $this->entityManager->flush();

        return $block;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateBlock(Block $block, array $data): Block
    {
        if (isset($data['type'])) {
            $block->setType($data['type']);
        }

        if (isset($data['content'])) {
            $block->setContent($data['content']);
        }

        if (isset($data['position'])) {
            $block->setPosition($data['position']);
        }

        if (isset($data['settings'])) {
            $block->setSettings($data['settings']);
        }

        $this->entityManager->flush();

        return $block;
    }

    public function deleteBlock(Block $block): void
    {
        $this->entityManager->remove($block);
        $this->entityManager->flush();
    }
}
