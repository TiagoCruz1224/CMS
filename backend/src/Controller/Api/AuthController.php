<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\UserService;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[Route('/api')]
class AuthController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->json([
                'error' => 'Email and password are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Check if user already exists
        if ($this->userService->findByEmail($data['email'])) {
            return $this->json([
                'error' => 'User with this email already exists'
            ], Response::HTTP_CONFLICT);
        }

        try {
            // Roles are never taken from the request: new accounts only get ROLE_USER
            $user = $this->userService->createUser(
                $data['email'],
                $data['password'],
                firstName: $data['firstName'] ?? null,
                lastName: $data['lastName'] ?? null
            );

            return $this->json([
                'message' => 'User registered successfully',
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                ]
            ], Response::HTTP_CREATED);

        } catch (ValidationFailedException $e) {
            return $this->validationErrors($e);
        }
    }

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // This method is handled by the JWT authenticator
        // It will never be reached, but is required for the route to exist
        return $this->json(['message' => 'Login successful']);
    }

    #[Route('/profile', name: 'api_profile', methods: ['GET'])]
    public function profile(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'error' => 'Not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'roles' => $user->getRoles(),
        ]);
    }

    #[Route('/profile', name: 'api_profile_update', methods: ['PUT', 'PATCH'])]
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'error' => 'Not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        $changesEmail = isset($data['email']) && $data['email'] !== $user->getEmail();
        if (($changesEmail || isset($data['password']))
            && !$this->passwordHasher->isPasswordValid($user, (string) ($data['currentPassword'] ?? ''))) {
            return $this->json([
                'error' => 'Current password is required to change email or password'
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $this->userService->updateUser($user, $data);

            return $this->json([
                'message' => 'Profile updated successfully',
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                ]
            ]);

        } catch (ValidationFailedException $e) {
            return $this->validationErrors($e);
        }
    }

    private function validationErrors(ValidationFailedException $e): JsonResponse
    {
        $errorMessages = [];
        // A taken email is a conflict, as in register()
        $status = Response::HTTP_BAD_REQUEST;
        foreach ($e->getViolations() as $violation) {
            $errorMessages[] = $violation->getMessage();
            if ($violation->getCode() === UniqueEntity::NOT_UNIQUE_ERROR) {
                $status = Response::HTTP_CONFLICT;
            }
        }

        return $this->json([
            'errors' => $errorMessages
        ], $status);
    }
}
