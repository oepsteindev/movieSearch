<?php

namespace App\Tests\Service;

use App\Entity\FavoriteList;
use App\Entity\User;
use App\Repository\FavoriteItemRepository;
use App\Repository\FavoriteListRepository;
use App\Repository\UserRepository;
use App\Service\Exception\DuplicateListNameException;
use App\Service\Exception\ListNotFoundException;
use App\Service\FavoritesService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FavoritesServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private FavoritesService $favoritesService;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);

        // FavoritesService isn't wired into a controller yet at this point
        // in the build, so nothing references it — the container compiler
        // would prune it as unused. Construct it directly instead, using
        // the real (container-provided) EntityManager/repositories.
        $this->favoritesService = new FavoritesService(
            $this->entityManager,
            $container->get(FavoriteListRepository::class),
            $container->get(FavoriteItemRepository::class),
            $container->get(UserRepository::class),
        );

        // There's no separate test database configured (see README), so
        // these tests run against the real dev database. Wrap each test in
        // a transaction and roll it back afterward to avoid polluting the
        // fixture data other tests/manual checks rely on.
        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->entityManager->getConnection()->rollBack();

        parent::tearDown();
    }

    public function testCreateListPersistsAndReturnsDto(): void
    {
        $dto = $this->favoritesService->createList('Weekend Watchlist');

        $this->assertSame('Weekend Watchlist', $dto->name);
        $this->assertSame(0, $dto->itemCount);
    }

    public function testCreateListRejectsDuplicateNameForSameOwner(): void
    {
        $this->favoritesService->createList('Weekend Watchlist');

        $this->expectException(DuplicateListNameException::class);

        $this->favoritesService->createList('Weekend Watchlist');
    }

    public function testListListsReturnsListsAlphabetically(): void
    {
        $this->favoritesService->createList('Zeta List');
        $this->favoritesService->createList('Alpha List');

        $names = array_map(
            fn ($list) => $list->name,
            $this->favoritesService->listLists(),
        );

        $this->assertLessThan(
            array_search('Zeta List', $names, true),
            array_search('Alpha List', $names, true),
        );
    }

    public function testItemCountReflectsAddedItems(): void
    {
        $list = $this->favoritesService->createList('Watchlist With Items');

        $this->favoritesService->addItemToList($list->id, 'tt0000001', 'Movie One', 'https://example.com/1.jpg', 1999);
        $this->favoritesService->addItemToList($list->id, 'tt0000002', 'Movie Two', 'https://example.com/2.jpg', 2000);

        $lists = $this->favoritesService->listLists();
        $updated = current(array_filter($lists, fn ($l) => $l->id === $list->id));

        $this->assertSame(2, $updated->itemCount);
    }

    public function testItemsAreSortedMostRecentlyAddedFirst(): void
    {
        $list = $this->favoritesService->createList('Sorting Test');

        $this->favoritesService->addItemToList($list->id, 'tt0000001', 'First Added', 'https://example.com/1.jpg', 1999);
        $this->favoritesService->addItemToList($list->id, 'tt0000002', 'Second Added', 'https://example.com/2.jpg', 2000);

        $detail = $this->favoritesService->getListDetail($list->id);

        $this->assertSame('Second Added', $detail->items[0]->name);
        $this->assertSame('First Added', $detail->items[1]->name);
    }

    public function testRemoveItemDeletesOnlyThatItem(): void
    {
        $list = $this->favoritesService->createList('Removal Test');

        $kept = $this->favoritesService->addItemToList($list->id, 'tt0000001', 'Keep Me', 'https://example.com/1.jpg', 1999);
        $removed = $this->favoritesService->addItemToList($list->id, 'tt0000002', 'Remove Me', 'https://example.com/2.jpg', 2000);

        $this->favoritesService->removeItem($list->id, $removed->id);

        $detail = $this->favoritesService->getListDetail($list->id);

        $this->assertCount(1, $detail->items);
        $this->assertSame($kept->id, $detail->items[0]->id);
    }

    public function testDeleteListRemovesItAndItsItems(): void
    {
        $list = $this->favoritesService->createList('Deletion Test');
        $this->favoritesService->addItemToList($list->id, 'tt0000001', 'Doomed Movie', 'https://example.com/1.jpg', 1999);

        $this->favoritesService->deleteList($list->id);

        $this->expectException(ListNotFoundException::class);

        $this->favoritesService->getListDetail($list->id);
    }

    public function testListOwnedByAnotherUserIsNotAccessible(): void
    {
        $otherUser = new User('other-user@example.com', 'placeholder');
        $this->entityManager->persist($otherUser);

        $otherList = new FavoriteList('Someone Else\'s List', $otherUser);
        $this->entityManager->persist($otherList);
        $this->entityManager->flush();

        $this->expectException(ListNotFoundException::class);

        $this->favoritesService->getListDetail($otherList->getId());
    }

    public function testGetCurrentUserThrowsWhenDemoUserIsMissing(): void
    {
        $userRepository = static::getContainer()->get(UserRepository::class);
        $favoriteListRepository = static::getContainer()->get(FavoriteListRepository::class);

        $demoUser = $userRepository->findByEmail('demo@example.com');
        $this->assertNotNull($demoUser, 'Fixtures must be loaded for this test to be meaningful.');

        foreach ($favoriteListRepository->findByOwner($demoUser) as $list) {
            $this->entityManager->remove($list);
        }
        $this->entityManager->flush();

        $this->entityManager->remove($demoUser);
        $this->entityManager->flush();

        $this->expectException(\RuntimeException::class);

        $this->favoritesService->listLists();
    }
}
