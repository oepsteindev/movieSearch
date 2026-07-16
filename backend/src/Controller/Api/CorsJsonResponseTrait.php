<?php

namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;

trait CorsJsonResponseTrait
{
    //set a header for CORS on localhost to negotiate the frontend and backend running on different ports
    private function jsonWithCors(array $data, int $status = 200): JsonResponse
    {
        $response = new JsonResponse($data, $status);
        $response->headers->set('Access-Control-Allow-Origin', '*');

        return $response;
    }
}
