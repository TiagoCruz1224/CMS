<?php

namespace App\Security\Voter;

use App\Entity\Page;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * VIEW: published pages are public; anything else only for its author or an admin.
 * EDIT: author or admin.
 */
class PageVoter extends Voter
{
    public const VIEW = 'PAGE_VIEW';
    public const EDIT = 'PAGE_EDIT';

    public function __construct(private Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT], true) && $subject instanceof Page;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var Page $subject */
        if ($attribute === self::VIEW && $subject->isPublished()) {
            return true;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return $this->security->isGranted('ROLE_ADMIN') || $subject->getAuthor()?->getId() === $user->getId();
    }
}
