<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Answers every exception under /api with the API's JSON error format: HTTP exceptions (unknown route, wrong
 * method, bad request, ...) keep their status and headers; anything else becomes a generic 500, so internal
 * details (SQL, paths) never reach the client. Symfony's ErrorListener logs the exception first (priority 0).
 */
#[AsEventListener(event: KernelEvents::EXCEPTION, priority: -64)]
final class ApiExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api/')) {
            return;
        }

        $exception = $event->getThrowable();
        if ($exception instanceof HttpExceptionInterface) {
            // The status text, not the exception message, which can echo the request (e.g. "No route found for ...")
            $status = $exception->getStatusCode();
            $event->setResponse(new JsonResponse(['error' => Response::$statusTexts[$status] ?? 'Error'], $status, $exception->getHeaders()));

            return;
        }

        $event->setResponse(new JsonResponse(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR));
    }
}
