<?php

namespace App\Entity;

use App\Repository\FavoriteItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FavoriteItemRepository::class)]
#[ORM\Table(name: 'favorite_items')]
class FavoriteItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32)]
    private string $externalId;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 512)]
    private string $image;

    #[ORM\Column(nullable: true)]
    private ?int $year = null;

    #[ORM\ManyToOne(targetEntity: FavoriteList::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private FavoriteList $favoriteList;

    // No edit action exists for a favorite once added, so "modified date"
    // sorting (per part2.md) just means "most recently added first".
    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $externalId, string $name, string $image, ?int $year, FavoriteList $favoriteList)
    {
        $this->externalId = $externalId;
        $this->name = $name;
        $this->image = $image;
        $this->year = $year;
        $this->favoriteList = $favoriteList;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function getFavoriteList(): FavoriteList
    {
        return $this->favoriteList;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
