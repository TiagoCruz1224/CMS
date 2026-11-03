<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Rejects API writes whose body is not a JSON object before they reach a controller.
 * Runs after the firewall (priority 8), so unauthenticated requests still get 401.
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 0)]
final class JsonBodyListener
{
    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest()
            || !str_starts_with($request->getPathInfo(), '/api/')
            || !in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'], true)) {
            return;
        }

        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $data = null;
        }

        if (!is_array($data)) {
            $event->setResponse(new JsonResponse(['error' => 'Request body must be a valid JSON object'], Response::HTTP_BAD_REQUEST));
        }
    }
}
