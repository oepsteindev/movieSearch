<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MovieSearchControllerTest extends WebTestCase
{
    private function pageResponse(array $entries): MockResponse
    {
        return new MockResponse(json_encode([
            'Search' => $entries,
            'totalResults' => (string) count($entries),
            'Response' => 'True',
        ]));
    }

    public function testSearchReturnsNormalizedResultsWithCorsHeader(): void
    {
        $client = static::createClient();
        $mockHttpClient = new MockHttpClient([
            $this->pageResponse([
                ['Title' => 'Batman Begins', 'Year' => '2005', 'imdbID' => 'tt0372784', 'Poster' => 'https://example.com/1.jpg'],
                ['Title' => 'No Poster Batman', 'Year' => '2006', 'imdbID' => 'tt0000002', 'Poster' => 'N/A'],
            ]),
        ]);
        static::getContainer()->set(HttpClientInterface::class, $mockHttpClient);

        $client->request('GET', '/api/movies?search=batman');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Access-Control-Allow-Origin', '*');

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame(1, $data['count']);
        $this->assertCount(1, $data['results']);
        $this->assertSame('tt0372784', $data['results'][0]['id']);
        $this->assertSame('Batman Begins', $data['results'][0]['name']);
        $this->assertSame(2005, $data['results'][0]['year']);
    }

    public function testShortSearchTermIsRejectedWithoutCallingUpstream(): void
    {
        $client = static::createClient();
        $mockHttpClient = new MockHttpClient([]);
        static::getContainer()->set(HttpClientInterface::class, $mockHttpClient);

        $client->request('GET', '/api/movies?search=abc');

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertSame(0, $mockHttpClient->getRequestsCount());
    }

    public function testEmptySearchReturnsTheDefaultList(): void
    {
        $client = static::createClient();
        $mockHttpClient = new MockHttpClient([
            $this->pageResponse([
                ['Title' => 'Some Movie', 'Year' => '2000', 'imdbID' => 'tt0000001', 'Poster' => 'https://example.com/1.jpg'],
            ]),
        ]);
        static::getContainer()->set(HttpClientInterface::class, $mockHttpClient);

        $client->request('GET', '/api/movies');

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertNotEmpty($data['results']);
    }

    public function testUpstreamFailureReturnsBadGateway(): void
    {
        $client = static::createClient();
        $mockHttpClient = new MockHttpClient([
            new MockResponse('', ['http_code' => 500]),
        ]);
        static::getContainer()->set(HttpClientInterface::class, $mockHttpClient);

        $client->request('GET', '/api/movies?search=batman');

        $this->assertResponseStatusCodeSame(502);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }
}
