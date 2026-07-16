<?php

namespace App\Tests\Service;

use App\Service\ExternalMovieSearchService;
use App\Service\Exception\ExternalMovieSearchException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class ExternalMovieSearchServiceTest extends TestCase
{
    private function makeService(MockHttpClient $client): ExternalMovieSearchService
    {
        return new ExternalMovieSearchService($client, new ArrayAdapter(), 'test-api-key');
    }

    private function pageResponse(array $entries, string $totalResults = '2'): MockResponse
    {
        return new MockResponse(json_encode([
            'Search' => $entries,
            'totalResults' => $totalResults,
            'Response' => 'True',
        ]));
    }

    public function testNormalizationDropsEntriesWithoutAnImage(): void
    {
        $client = new MockHttpClient([
            $this->pageResponse([
                ['Title' => 'Has Poster', 'Year' => '1999', 'imdbID' => 'tt0000001', 'Poster' => 'https://example.com/1.jpg'],
                ['Title' => 'No Poster', 'Year' => '2000', 'imdbID' => 'tt0000002', 'Poster' => 'N/A'],
                ['Title' => 'Missing Poster Field', 'Year' => '2001', 'imdbID' => 'tt0000003'],
            ]),
        ]);

        $movies = $this->makeService($client)->search('batman');

        $this->assertCount(1, $movies);
        $this->assertSame('tt0000001', $movies[0]->id);
        $this->assertSame('Has Poster', $movies[0]->name);
    }

    public function testBackfillPaginationPullsFromASecondPage(): void
    {
        $pageOne = array_merge(
            [['Title' => 'Valid One', 'Year' => '1999', 'imdbID' => 'tt0000001', 'Poster' => 'https://example.com/1.jpg']],
            // 9 posterless entries pad this page to OMDb's 10-per-page size,
            // so it isn't mistaken for the last page.
            array_fill(0, 9, ['Title' => 'No Poster', 'Year' => '2000', 'imdbID' => 'tt0000002', 'Poster' => 'N/A']),
        );

        $pageTwo = [
            ['Title' => 'Valid Two', 'Year' => '2001', 'imdbID' => 'tt0000003', 'Poster' => 'https://example.com/3.jpg'],
        ];

        $client = new MockHttpClient([
            $this->pageResponse($pageOne),
            $this->pageResponse($pageTwo),
        ]);

        $movies = $this->makeService($client)->search('batman');

        $this->assertCount(2, $movies);
        $this->assertSame('tt0000001', $movies[0]->id);
        $this->assertSame('tt0000003', $movies[1]->id);
    }

    public function testResultsAreCappedAtOneHundred(): void
    {
        $fullPage = array_map(
            fn (int $i) => ['Title' => "Movie $i", 'Year' => '1999', 'imdbID' => "tt$i", 'Poster' => 'https://example.com/x.jpg'],
            range(0, 9),
        );

        // 11 full pages of 10 valid entries each = 110 candidates, more than
        // enough to prove the cap stops the loop at exactly 100.
        $responses = array_fill(0, 11, null);
        $client = new MockHttpClient(array_map(fn () => $this->pageResponse($fullPage), $responses));

        $movies = $this->makeService($client)->search('batman');

        $this->assertCount(100, $movies);
    }

    public function testUpstreamFailureThrowsExternalMovieSearchException(): void
    {
        $client = new MockHttpClient([
            new MockResponse('', ['http_code' => 500]),
        ]);

        $this->expectException(ExternalMovieSearchException::class);

        $this->makeService($client)->search('batman');
    }

    public function testDefaultListIsCachedAcrossCalls(): void
    {
        $client = new MockHttpClient([
            $this->pageResponse([
                ['Title' => 'Cached Movie', 'Year' => '1999', 'imdbID' => 'tt0000001', 'Poster' => 'https://example.com/1.jpg'],
            ]),
        ]);
        $service = $this->makeService($client);

        $first = $service->search('');
        $second = $service->search('');

        $this->assertSame(1, $client->getRequestsCount());
        $this->assertSame($first[0]->id, $second[0]->id);
    }

    public function testSearchTermsAreNotCached(): void
    {
        $client = new MockHttpClient([
            $this->pageResponse([
                ['Title' => 'Batman Begins', 'Year' => '2005', 'imdbID' => 'tt0000001', 'Poster' => 'https://example.com/1.jpg'],
            ]),
            $this->pageResponse([
                ['Title' => 'Batman Begins', 'Year' => '2005', 'imdbID' => 'tt0000001', 'Poster' => 'https://example.com/1.jpg'],
            ]),
        ]);
        $service = $this->makeService($client);

        $service->search('batman');
        $service->search('batman');

        $this->assertSame(2, $client->getRequestsCount());
    }
}
