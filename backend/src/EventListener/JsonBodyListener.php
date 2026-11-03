<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Rejects API writes whose body is not a JSON object, or whose known fields have the wrong type,
 * before they reach a controller (which would otherwise fail with a TypeError and a 500).
 * Runs after the firewall (priority 8), so unauthenticated requests still get 401.
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 0)]
final class JsonBodyListener
{
    private const STRING = 'a string';
    private const INT = 'an integer';
    private const ARRAY = 'an array or object';
    private const BOOL = 'true or false';
    private const DATE = 'a date string';
    private const NOT_ACCEPTED = 'left out (use categoryId)';

    // Fields each write route reads from the body; null values are ignored, as the controllers do
    private const FIELD_TYPES = [
        'api_register' => ['email' => self::STRING, 'password' => self::STRING, 'firstName' => self::STRING, 'lastName' => self::STRING],
        'api_profile_update' => ['email' => self::STRING, 'password' => self::STRING, 'currentPassword' => self::STRING, 'firstName' => self::STRING, 'lastName' => self::STRING],
        'api_pages_create' => ['title' => self::STRING, 'slug' => self::STRING, 'content' => self::STRING, 'status' => self::STRING, 'categoryId' => self::INT],
        'api_pages_update' => [
            'title' => self::STRING, 'slug' => self::STRING, 'content' => self::STRING, 'status' => self::STRING,
            'categoryId' => self::INT, 'category' => self::NOT_ACCEPTED,
            'metaTitle' => self::STRING, 'metaDescription' => self::STRING, 'metaKeywords' => self::ARRAY, 'ogTags' => self::ARRAY,
            'template' => self::STRING, 'publishAt' => self::DATE, 'unpublishAt' => self::DATE, 'isIndexable' => self::BOOL,
            'changeNote' => self::STRING,
        ],
        'api_blocks_create' => ['type' => self::STRING, 'pageId' => self::INT, 'content' => self::STRING, 'position' => self::INT, 'settings' => self::ARRAY],
        'api_blocks_update' => ['type' => self::STRING, 'content' => self::STRING, 'position' => self::INT, 'settings' => self::ARRAY],
        'api_media_create' => ['filename' => self::STRING, 'type' => self::STRING, 'url' => self::STRING, 'blockId' => self::INT, 'mimeType' => self::STRING, 'size' => self::INT, 'alt' => self::STRING],
        'api_media_update' => ['filename' => self::STRING, 'type' => self::STRING, 'url' => self::STRING, 'mimeType' => self::STRING, 'size' => self::INT, 'alt' => self::STRING],
        'api_categories_create' => ['name' => self::STRING, 'slug' => self::STRING, 'description' => self::STRING],
        'api_categories_update' => ['name' => self::STRING, 'slug' => self::STRING, 'description' => self::STRING],
    ];

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest()
            || !str_starts_with($request->getPathInfo(), '/api/')
            || !in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'], true)) {
            return;
        }

        // Decoded without "assoc", so a JSON object ({} included) is a stdClass and a list ([...]) is an array
        try {
            $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $data = null;
        }

        if (!$data instanceof \stdClass) {
            $event->setResponse(new JsonResponse(['error' => 'Request body must be a valid JSON object'], Response::HTTP_BAD_REQUEST));

            return;
        }

        $fields = (array) json_decode($request->getContent(), true);
        foreach (self::FIELD_TYPES[$request->attributes->get('_route')] ?? [] as $field => $type) {
            if (isset($fields[$field]) && !self::hasType($fields[$field], $type)) {
                $event->setResponse(new JsonResponse(['error' => \sprintf('Field "%s" must be %s', $field, $type)], Response::HTTP_BAD_REQUEST));

                return;
            }
        }
    }

    private static function hasType(mixed $value, string $type): bool
    {
        return match ($type) {
            self::STRING => is_string($value),
            // Ids may come as numeric strings from HTML forms; 18 digits always fit in an int
            self::INT => is_int($value) || (is_string($value) && preg_match('/^\d{1,18}$/', $value) === 1),
            self::ARRAY => is_array($value),
            self::BOOL => is_bool($value),
            self::DATE => is_string($value) && self::isDate($value),
            default => false,
        };
    }

    private static function isDate(string $value): bool
    {
        try {
            new \DateTimeImmutable($value);

            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
