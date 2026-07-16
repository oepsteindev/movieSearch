<?php

namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait CorsJsonResponseTrait
{
    //set a header for CORS on localhost to negotiate the frontend and backend running on different ports
    private function jsonWithCors(array $data, int $status = 200): JsonResponse
    {
        $response = new JsonResponse($data, $status);
        $response->headers->set('Access-Control-Allow-Origin', '*');

        return $response;
    }

    private function noContentWithCors(): Response
    {
        $response = new Response(status: 204);
        $response->headers->set('Access-Control-Allow-Origin', '*');

        return $response;
    }
}
