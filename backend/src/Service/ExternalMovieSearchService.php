<?php

namespace App\Service;

use App\Dto\MovieDto;
use App\Service\Exception\ExternalMovieSearchException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ExternalMovieSearchService
{
    // Free-tier OMDb API keys only support http, not https.
    private const BASE_URL = 'http://www.omdbapi.com/';

    // OMDb has no "browse all" endpoint, so the no-search-term grid is built
    // from this fixed term instead.
    private const SEED_TERM = 'movie';

    private const DEFAULT_CACHE_KEY = 'movies_default_list';

    private const CACHE_TTL_SECONDS = 3600;

    private const RESULT_CAP = 100;

    // Ceiling on how many OMDb pages we'll fetch while backfilling toward
    // RESULT_CAP after dropping entries without a poster.
    private const MAX_PAGES = 20;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        #[Autowire('%env(OMDB_API_KEY)%')]
        private readonly string $apiKey,
    ) {
    }

    /**
     * @return MovieDto[]
     */
    public function search(string $term): array
    {
        $term = trim($term);

        if ($term === '') {
            return $this->cache->get(self::DEFAULT_CACHE_KEY, function (ItemInterface $item): array {
                $item->expiresAfter(self::CACHE_TTL_SECONDS);

                return $this->fetchAndNormalize(self::SEED_TERM);
            });
        }

        return $this->fetchAndNormalize($term);
    }

    /**
     * @return MovieDto[]
     */
    private function fetchAndNormalize(string $term): array
    {
        $collected = [];
        $page = 1;

        while (count($collected) < self::RESULT_CAP && $page <= self::MAX_PAGES) {
            $rawResults = $this->fetchPage($term, $page);

            if ($rawResults === null) {
                break;
            }

            foreach ($rawResults as $entry) {
                $movie = $this->normalize($entry);

                if ($movie === null) {
                    continue;
                }

                $collected[] = $movie;

                if (count($collected) >= self::RESULT_CAP) {
                    break;
                }
            }

            // OMDb returns up to 10 results per page; fewer than that means
            // there's nothing left to backfill from.
            if (count($rawResults) < 10) {
                break;
            }

            ++$page;
        }

        return $collected;
    }

    /**
     * @return array<int, array<string, mixed>>|null Raw OMDb "Search" entries, or null when OMDb reports no match
     */
    private function fetchPage(string $term, int $page): ?array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URL, [
                'query' => [
                    'apikey' => $this->apiKey,
                    's' => $term,
                    'page' => $page,
                ],
                'timeout' => 5,
                'max_duration' => 8,
            ]);

            $data = $response->toArray();
        } catch (ExceptionInterface $e) {
            throw new ExternalMovieSearchException('Failed to reach the OMDb API.', previous: $e);
        }

        if (($data['Response'] ?? null) !== 'True') {
            return null;
        }

        return $data['Search'] ?? [];
    }

    /**
     * @param array<string, mixed> $entry Raw OMDb search result entry
     */
    private function normalize(array $entry): ?MovieDto
    {
        $poster = $entry['Poster'] ?? null;

        // OMDb uses the literal string "N/A" as its sentinel for "no poster".
        if (!is_string($poster) || $poster === '' || $poster === 'N/A') {
            return null;
        }

        $id = $entry['imdbID'] ?? null;
        $name = $entry['Title'] ?? null;

        if (!is_string($id) || $id === '' || !is_string($name) || $name === '') {
            return null;
        }

        $year = null;
        // Series entries can report a range like "2014–2016"; take the
        // leading 4 digits.
        if (isset($entry['Year']) && is_string($entry['Year']) && preg_match('/\d{4}/', $entry['Year'], $matches)) {
            $year = (int) $matches[0];
        }

        return new MovieDto(id: $id, name: $name, image: $poster, year: $year);
    }
}
