<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

// Part 1 only ever used GET, which browsers treat as a "simple request" (no
// preflight). Part 2 adds POST/DELETE with a JSON body, which does trigger a
// CORS preflight OPTIONS request — nothing matches that route otherwise, so
// this answers it directly before routing runs (see README caveats).
final class CorsPreflightSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            // Higher priority than RouterListener (32), so this runs first
            // and short-circuits before the router 404s an OPTIONS request.
            KernelEvents::REQUEST => ['onKernelRequest', 250],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || $event->getRequest()->getMethod() !== 'OPTIONS') {
            return;
        }

        $response = new Response(status: 204);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');

        $event->setResponse($response);
    }
}
