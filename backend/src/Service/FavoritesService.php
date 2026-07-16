<?php

namespace App\Service;

use App\Dto\FavoriteItemDto;
use App\Dto\FavoriteListDetailDto;
use App\Dto\FavoriteListDto;
use App\Entity\FavoriteItem;
use App\Entity\FavoriteList;
use App\Entity\User;
use App\Repository\FavoriteItemRepository;
use App\Repository\FavoriteListRepository;
use App\Repository\UserRepository;
use App\Service\Exception\DuplicateListNameException;
use App\Service\Exception\ListNotFoundException;
use Doctrine\ORM\EntityManagerInterface;

class FavoritesService
{
    // There's no real login (see fixtures.md) — every request acts as this
    // one demo user, seeded by AppFixtures.
    private const DEMO_USER_EMAIL = 'demo@example.com';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FavoriteListRepository $favoriteListRepository,
        private readonly FavoriteItemRepository $favoriteItemRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    /**
     * @return FavoriteListDto[]
     */
    public function listLists(): array
    {
        $lists = $this->favoriteListRepository->findByOwner($this->getCurrentUser());

        return array_map($this->toListDto(...), $lists);
    }

    public function createList(string $name): FavoriteListDto
    {
        $user = $this->getCurrentUser();
        $name = trim($name);

        if ($this->favoriteListRepository->nameExistsForOwner($user, $name)) {
            throw new DuplicateListNameException($name);
        }

        $list = new FavoriteList($name, $user);
        $this->entityManager->persist($list);
        $this->entityManager->flush();

        return $this->toListDto($list);
    }

    public function deleteList(int $id): void
    {
        $list = $this->findOwnedList($id);

        $this->entityManager->remove($list);
        $this->entityManager->flush();
    }

    public function getListDetail(int $id): FavoriteListDetailDto
    {
        $list = $this->findOwnedList($id);
        $items = $this->favoriteItemRepository->findByListOrderedByModified($list);

        return new FavoriteListDetailDto(
            $list->getId(),
            $list->getName(),
            array_map($this->toItemDto(...), $items),
        );
    }

    public function addItemToList(int $listId, string $externalId, string $name, string $image, ?int $year): FavoriteItemDto
    {
        $list = $this->findOwnedList($listId);

        $item = new FavoriteItem($externalId, $name, $image, $year, $list);
        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $this->toItemDto($item);
    }

    public function removeItem(int $listId, int $itemId): void
    {
        $list = $this->findOwnedList($listId);
        $item = $this->favoriteItemRepository->findOneByIdAndList($itemId, $list);

        if ($item === null) {
            throw new ListNotFoundException();
        }

        $this->entityManager->remove($item);
        $this->entityManager->flush();
    }

    private function findOwnedList(int $id): FavoriteList
    {
        $list = $this->favoriteListRepository->findOneByIdAndOwner($id, $this->getCurrentUser());

        if ($list === null) {
            throw new ListNotFoundException();
        }

        return $list;
    }

    private function getCurrentUser(): User
    {
        $user = $this->userRepository->findByEmail(self::DEMO_USER_EMAIL);

        if ($user === null) {
            throw new \RuntimeException('Demo user not found — have fixtures been loaded?');
        }

        return $user;
    }

    private function toListDto(FavoriteList $list): FavoriteListDto
    {
        return new FavoriteListDto(
            $list->getId(),
            $list->getName(),
            $this->favoriteItemRepository->countByList($list),
        );
    }

    private function toItemDto(FavoriteItem $item): FavoriteItemDto
    {
        return new FavoriteItemDto(
            $item->getId(),
            $item->getExternalId(),
            $item->getName(),
            $item->getImage(),
            $item->getYear(),
            $item->getCreatedAt()->format(\DATE_ATOM),
        );
    }
}
