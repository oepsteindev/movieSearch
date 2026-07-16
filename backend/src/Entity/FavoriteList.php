<?php

namespace App\Entity;

use App\Repository\FavoriteListRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FavoriteListRepository::class)]
#[ORM\Table(name: 'favorite_lists')]
#[ORM\UniqueConstraint(name: 'uniq_owner_name', columns: ['owner_id', 'name'])]
class FavoriteList
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $owner;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $name, User $owner)
    {
        $this->name = $name;
        $this->owner = $owner;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
