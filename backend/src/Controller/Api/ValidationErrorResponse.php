<?php

namespace App\Controller\Api;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

trait ValidationErrorResponse
{
    /**
     * 400 with the violations keyed by field; a value that is already taken (a slug) is a conflict, 409,
     * as a taken email is in AuthController.
     */
    private function validationErrorResponse(ConstraintViolationListInterface $violations): JsonResponse
    {
        $errors = [];
        $status = Response::HTTP_BAD_REQUEST;
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
            if ($violation->getCode() === UniqueEntity::NOT_UNIQUE_ERROR) {
                $status = Response::HTTP_CONFLICT;
            }
        }

        return new JsonResponse(['errors' => $errors], $status);
    }
}
