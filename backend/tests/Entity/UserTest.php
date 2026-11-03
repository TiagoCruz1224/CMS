<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testRolesAreAListWithoutDuplicates(): void
    {
        $user = (new User())->setRoles(['ROLE_ADMIN', 'ROLE_ADMIN']);

        // A list (keys 0..n-1) serializes to a JSON array, not an object like {"0": ..., "2": ...}
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
        self::assertSame('["ROLE_ADMIN","ROLE_USER"]', json_encode($user->getRoles()));
    }
}
