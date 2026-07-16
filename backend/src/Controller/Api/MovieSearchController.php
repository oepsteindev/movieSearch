<?php

namespace App\Controller\Api;

use App\Service\Exception\ExternalMovieSearchException;
use App\Service\ExternalMovieSearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class MovieSearchController extends AbstractController
{
    use CorsJsonResponseTrait;

    private const MIN_SEARCH_LENGTH = 4;// so we dont hammer the api on every keystroke

    #[Route('/api/movies', name: 'api_movies_search', methods: ['GET'])]
    public function search(Request $request, ExternalMovieSearchService $service): JsonResponse
    {
        $search = trim((string) $request->query->get('search', ''));

        if ($search !== '' && mb_strlen($search) < self::MIN_SEARCH_LENGTH) {
            return $this->jsonWithCors([
                'error' => sprintf('Search term must be at least %d characters.', self::MIN_SEARCH_LENGTH),
            ], 400);
        }

        try {
            $movies = $service->search($search);
        } catch (ExternalMovieSearchException) {
            return $this->jsonWithCors([
                'error' => 'Unable to fetch movies right now. Please try again later.',
            ], 502);
        }

        return $this->jsonWithCors([
            'results' => $movies,
            'count' => count($movies),
        ]);
    }
}
