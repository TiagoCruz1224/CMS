<?php

namespace App\Entity;

use App\Repository\PageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PageRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Page
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['page:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 200)]
    #[Groups(['page:read', 'page:write'])]
    private ?string $title = null;

    #[ORM\Column(length: 200, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 200)]
    #[Groups(['page:read', 'page:write'])]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['page:read', 'page:write'])]
    private ?string $content = null;

    #[ORM\ManyToOne(inversedBy: 'pages')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['page:read'])]
    private ?User $author = null;

    #[ORM\Column]
    #[Groups(['page:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['page:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: ['draft', 'published', 'archived', 'scheduled'])]
    #[Groups(['page:read', 'page:write'])]
    private ?string $status = 'draft';

    #[ORM\ManyToOne(inversedBy: 'pages')]
    #[Groups(['page:read', 'page:write'])]
    private ?Category $category = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Groups(['page:read', 'page:write'])]
    private ?string $metaTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 500)]
    #[Groups(['page:read', 'page:write'])]
    private ?string $metaDescription = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['page:read', 'page:write'])]
    private ?array $metaKeywords = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Choice(choices: ['page', 'post', 'landing', 'custom'])]
    #[Groups(['page:read', 'page:write'])]
    private ?string $template = 'page';

    #[ORM\Column(nullable: true)]
    #[Groups(['page:read', 'page:write'])]
    private ?\DateTimeImmutable $publishAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['page:read', 'page:write'])]
    private ?\DateTimeImmutable $unpublishAt = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['page:read', 'page:write'])]
    private ?array $ogTags = null;

    #[ORM\Column(options: ['default' => true])]
    #[Groups(['page:read', 'page:write'])]
    private ?bool $isIndexable = true;

    /**
     * @var Collection<int, Block>
     */
    #[ORM\OneToMany(targetEntity: Block::class, mappedBy: 'page', orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Groups(['page:read', 'page:write'])]
    private Collection $blocks;

    public function __construct()
    {
        $this->blocks = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function setMetaTitle(?string $metaTitle): static
    {
        $this->metaTitle = $metaTitle;

        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): static
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    public function getMetaKeywords(): ?array
    {
        return $this->metaKeywords;
    }

    public function setMetaKeywords(?array $metaKeywords): static
    {
        $this->metaKeywords = $metaKeywords;

        return $this;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function setTemplate(?string $template): static
    {
        $this->template = $template;

        return $this;
    }

    public function getPublishAt(): ?\DateTimeImmutable
    {
        return $this->publishAt;
    }

    public function setPublishAt(?\DateTimeImmutable $publishAt): static
    {
        $this->publishAt = $publishAt;

        return $this;
    }

    public function getUnpublishAt(): ?\DateTimeImmutable
    {
        return $this->unpublishAt;
    }

    public function setUnpublishAt(?\DateTimeImmutable $unpublishAt): static
    {
        $this->unpublishAt = $unpublishAt;

        return $this;
    }

    public function getOgTags(): ?array
    {
        return $this->ogTags;
    }

    public function setOgTags(?array $ogTags): static
    {
        $this->ogTags = $ogTags;

        return $this;
    }

    public function isIndexable(): ?bool
    {
        return $this->isIndexable;
    }

    public function setIsIndexable(bool $isIndexable): static
    {
        $this->isIndexable = $isIndexable;

        return $this;
    }

    public function isPublished(): bool
    {
        $now = new \DateTimeImmutable();
        
        if ($this->status !== 'published' && $this->status !== 'scheduled') {
            return false;
        }

        if ($this->publishAt && $this->publishAt > $now) {
            return false;
        }

        if ($this->unpublishAt && $this->unpublishAt < $now) {
            return false;
        }

        return true;
    }

    /**
     * @return Collection<int, Block>
     */
    public function getBlocks(): Collection
    {
        return $this->blocks;
    }

    public function addBlock(Block $block): static
    {
        if (!$this->blocks->contains($block)) {
            $this->blocks->add($block);
            $block->setPage($this);
        }

        return $this;
    }

    public function removeBlock(Block $block): static
    {
        if ($this->blocks->removeElement($block)) {
            // set the owning side to null (unless already changed)
            if ($block->getPage() === $this) {
                $block->setPage(null);
            }
        }

        return $this;
    }
}
