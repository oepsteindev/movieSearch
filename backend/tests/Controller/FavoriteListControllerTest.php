<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;

class FavoriteListControllerTest extends ApiTestCase
{
    private function postJson(string $uri, array $data): void
    {
        $this->client->request('POST', $uri, server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($data));
    }

    private function responseData(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true);
    }

    public function testIndexReturnsListsWithCorsHeader(): void
    {
        $this->client->request('GET', '/api/lists');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Access-Control-Allow-Origin', '*');
        $this->assertArrayHasKey('lists', $this->responseData());
    }

    public function testCreateListReturns201WithTheNewList(): void
    {
        $this->postJson('/api/lists', ['name' => 'Weekend Watchlist']);

        $this->assertResponseStatusCodeSame(201);

        $data = $this->responseData();
        $this->assertSame('Weekend Watchlist', $data['list']['name']);
        $this->assertSame(0, $data['list']['itemCount']);
    }

    public function testCreateListRejectsDuplicateNameWith409(): void
    {
        $this->postJson('/api/lists', ['name' => 'Weekend Watchlist']);
        $this->postJson('/api/lists', ['name' => 'Weekend Watchlist']);

        $this->assertResponseStatusCodeSame(409);
        $this->assertArrayHasKey('error', $this->responseData());
    }

    public function testShowReturnsListDetail(): void
    {
        $this->postJson('/api/lists', ['name' => 'Detail Test']);
        $listId = $this->responseData()['list']['id'];

        $this->client->request('GET', "/api/lists/{$listId}");

        $this->assertResponseIsSuccessful();

        $data = $this->responseData();
        $this->assertSame('Detail Test', $data['list']['name']);
        $this->assertSame([], $data['list']['items']);
    }

    public function testShowReturns404ForUnknownList(): void
    {
        $this->client->request('GET', '/api/lists/999999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteListRemovesIt(): void
    {
        $this->postJson('/api/lists', ['name' => 'Deletion Test']);
        $listId = $this->responseData()['list']['id'];

        $this->client->request('DELETE', "/api/lists/{$listId}");
        $this->assertResponseStatusCodeSame(204);

        $this->client->request('GET', "/api/lists/{$listId}");
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteUnknownListReturns404(): void
    {
        $this->client->request('DELETE', '/api/lists/999999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testAddFavoriteReturns201WithTheItem(): void
    {
        $this->postJson('/api/lists', ['name' => 'Favorites Test']);
        $listId = $this->responseData()['list']['id'];

        $this->postJson("/api/lists/{$listId}/favorites", [
            'externalId' => 'tt0133093',
            'name' => 'The Matrix',
            'image' => 'https://example.com/matrix.jpg',
            'year' => 1999,
        ]);

        $this->assertResponseStatusCodeSame(201);

        $data = $this->responseData();
        $this->assertSame('The Matrix', $data['item']['name']);
        $this->assertSame(1999, $data['item']['year']);
    }

    public function testAddFavoriteToUnknownListReturns404(): void
    {
        $this->postJson('/api/lists/999999/favorites', [
            'externalId' => 'tt0133093',
            'name' => 'The Matrix',
            'image' => 'https://example.com/matrix.jpg',
            'year' => 1999,
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testRemoveFavoriteDeletesOnlyThatItem(): void
    {
        $this->postJson('/api/lists', ['name' => 'Removal Test']);
        $listId = $this->responseData()['list']['id'];

        $this->postJson("/api/lists/{$listId}/favorites", [
            'externalId' => 'tt0000001', 'name' => 'Keep Me', 'image' => 'https://example.com/1.jpg', 'year' => 1999,
        ]);
        $keptId = $this->responseData()['item']['id'];

        $this->postJson("/api/lists/{$listId}/favorites", [
            'externalId' => 'tt0000002', 'name' => 'Remove Me', 'image' => 'https://example.com/2.jpg', 'year' => 2000,
        ]);
        $removedId = $this->responseData()['item']['id'];

        $this->client->request('DELETE', "/api/lists/{$listId}/favorites/{$removedId}");
        $this->assertResponseStatusCodeSame(204);

        $this->client->request('GET', "/api/lists/{$listId}");
        $data = $this->responseData();

        $this->assertCount(1, $data['list']['items']);
        $this->assertSame($keptId, $data['list']['items'][0]['id']);
    }
}
