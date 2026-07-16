<?php

namespace App\Controller\Api;

use App\Service\Exception\DuplicateListNameException;
use App\Service\Exception\ListNotFoundException;
use App\Service\FavoritesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/lists')]
final class FavoriteListController extends AbstractController
{
    use CorsJsonResponseTrait;

    public function __construct(private readonly FavoritesService $favoritesService)
    {
    }

    #[Route('', name: 'api_lists_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->jsonWithCors(['lists' => $this->favoritesService->listLists()]);
    }

    #[Route('', name: 'api_lists_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $name = trim((string) ($this->decode($request)['name'] ?? ''));

        try {
            $list = $this->favoritesService->createList($name);
        } catch (DuplicateListNameException $e) {
            return $this->jsonWithCors(['error' => $e->getMessage()], 409);
        }

        return $this->jsonWithCors(['list' => $list], 201);
    }

    #[Route('/{id<\d+>}', name: 'api_lists_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $detail = $this->favoritesService->getListDetail($id);
        } catch (ListNotFoundException $e) {
            return $this->jsonWithCors(['error' => $e->getMessage()], 404);
        }

        return $this->jsonWithCors(['list' => $detail]);
    }

    #[Route('/{id<\d+>}', name: 'api_lists_delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        try {
            $this->favoritesService->deleteList($id);
        } catch (ListNotFoundException $e) {
            return $this->jsonWithCors(['error' => $e->getMessage()], 404);
        }

        return $this->noContentWithCors();
    }

    #[Route('/{id<\d+>}/favorites', name: 'api_lists_add_favorite', methods: ['POST'])]
    public function addFavorite(int $id, Request $request): JsonResponse
    {
        $data = $this->decode($request);

        try {
            $item = $this->favoritesService->addItemToList(
                $id,
                (string) ($data['externalId'] ?? ''),
                (string) ($data['name'] ?? ''),
                (string) ($data['image'] ?? ''),
                isset($data['year']) ? (int) $data['year'] : null,
            );
        } catch (ListNotFoundException $e) {
            return $this->jsonWithCors(['error' => $e->getMessage()], 404);
        }

        return $this->jsonWithCors(['item' => $item], 201);
    }

    #[Route('/{listId<\d+>}/favorites/{favoriteId<\d+>}', name: 'api_lists_remove_favorite', methods: ['DELETE'])]
    public function removeFavorite(int $listId, int $favoriteId): Response
    {
        try {
            $this->favoritesService->removeItem($listId, $favoriteId);
        } catch (ListNotFoundException $e) {
            return $this->jsonWithCors(['error' => $e->getMessage()], 404);
        }

        return $this->noContentWithCors();
    }

    private function decode(Request $request): array
    {
        return json_decode($request->getContent(), true) ?? [];
    }
}
