<?php

namespace App\Tests\Controller\Api;

use App\Entity\Block;
use App\Entity\Media;
use App\Entity\Page;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Regression tests for role assignment, credential changes and draft visibility.
 */
class PermissionsTest extends WebTestCase
{
    private const PASSWORD = 'secret-password';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $metadata = $this->em()->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->em());
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function testRegisterIgnoresRequestedRoles(): void
    {
        $this->request('POST', '/api/register', body: ['email' => 'eve@example.com', 'password' => self::PASSWORD, 'roles' => ['ROLE_ADMIN']]);

        self::assertResponseStatusCodeSame(201);
        self::assertSame(['ROLE_USER'], $this->reloadUser('eve@example.com')?->getRoles());
    }

    public function testRegisterWithNameIgnoresRequestedRoles(): void
    {
        $this->request('POST', '/api/register', body: ['email' => 'eve@example.com', 'password' => self::PASSWORD, 'firstName' => 'Eve', 'roles' => ['ROLE_ADMIN']]);

        self::assertResponseStatusCodeSame(201);
        $user = $this->reloadUser('eve@example.com');
        self::assertNotNull($user);
        self::assertSame(['ROLE_USER'], $user->getRoles());
        self::assertSame('Eve', $user->getFirstName());
    }

    public function testProfileUpdateCannotGrantRoles(): void
    {
        $user = $this->createUser('user@example.com');

        $this->request('PATCH', '/api/profile', $user, ['firstName' => 'User', 'roles' => ['ROLE_ADMIN']]);

        self::assertResponseIsSuccessful();
        self::assertSame(['ROLE_USER'], $this->reloadUser('user@example.com')?->getRoles());
    }

    public function testCredentialChangesRequireCurrentPassword(): void
    {
        $user = $this->createUser('user@example.com');

        $this->request('PATCH', '/api/profile', $user, ['password' => 'new-password-123']);
        self::assertResponseStatusCodeSame(403);

        $this->request('PATCH', '/api/profile', $user, ['email' => 'other@example.com']);
        self::assertResponseStatusCodeSame(403);

        $this->request('PATCH', '/api/profile', $user, ['password' => 'new-password-123', 'currentPassword' => self::PASSWORD]);
        self::assertResponseIsSuccessful();
        $hasher = $this->service(UserPasswordHasherInterface::class);
        $user = $this->reloadUser('user@example.com');
        self::assertNotNull($user);
        self::assertTrue($hasher->isPasswordValid($user, 'new-password-123'));
    }

    public function testInvalidRegistrationIsNotPersisted(): void
    {
        $this->request('POST', '/api/register', body: ['email' => 'not-an-email', 'password' => self::PASSWORD]);

        self::assertResponseStatusCodeSame(400);
        self::assertNull($this->reloadUser('not-an-email'));
    }

    public function testInvalidProfileUpdateIsNotPersisted(): void
    {
        $user = $this->createUser('user@example.com');

        $this->request('PATCH', '/api/profile', $user, ['firstName' => str_repeat('x', 101)]);

        self::assertResponseStatusCodeSame(400);
        self::assertNull($this->reloadUser('user@example.com')?->getFirstName());
    }

    public function testAnonymousVisitorsOnlySeePublishedPages(): void
    {
        $author = $this->createUser('author@example.com');
        [$published] = $this->createPage($author, 'published');
        [$draft] = $this->createPage($author, 'draft');

        $list = $this->request('GET', '/api/pages');
        self::assertResponseIsSuccessful();
        self::assertSame([$published->getId()], array_column($list['data'], 'id'));
        self::assertSame(1, $list['total']);

        $this->request('GET', '/api/pages/'.$draft->getId());
        self::assertResponseStatusCodeSame(404);

        $this->request('GET', '/api/pages/'.$published->getId());
        self::assertResponseIsSuccessful();
    }

    public function testDraftsAreVisibleToTheirAuthorAndAdminsOnly(): void
    {
        $author = $this->createUser('author@example.com');
        $admin = $this->createUser('admin@example.com', ['ROLE_ADMIN']);
        $other = $this->createUser('other@example.com');
        [$draft] = $this->createPage($author, 'draft');

        foreach ([[$author, 200], [$admin, 200], [$other, 404]] as [$user, $status]) {
            $this->request('GET', '/api/pages/'.$draft->getId(), $user);
            self::assertResponseStatusCodeSame($status, (string) $user->getEmail());

            $expected = $status === 200 ? [$draft->getId()] : [];
            self::assertSame($expected, array_column($this->request('GET', '/api/pages', $user)['data'], 'id'), (string) $user->getEmail());
        }
    }

    public function testPublicationWindowIsEnforcedEverywhere(): void
    {
        $author = $this->createUser('author@example.com');
        $admin = $this->createUser('admin@example.com', ['ROLE_ADMIN']);
        [$live] = $this->createPage($author, 'published');
        [$scheduledLive] = $this->createPage($author, 'scheduled', publishAt: new \DateTimeImmutable('-1 day'));
        [$future] = $this->createPage($author, 'published', publishAt: new \DateTimeImmutable('+1 day'));
        [$expired] = $this->createPage($author, 'published', unpublishAt: new \DateTimeImmutable('-1 day'));
        $public = [$live->getId(), $scheduledLive->getId()];
        $all = [...$public, $future->getId(), $expired->getId()];

        // Normal and status-filtered lists
        foreach (['/api/pages', '/api/pages?status=published'] as $uri) {
            self::assertEqualsCanonicalizing($public, array_column($this->request('GET', $uri)['data'], 'id'), "anonymous $uri");
        }
        self::assertEqualsCanonicalizing($all, array_column($this->request('GET', '/api/pages', $author)['data'], 'id'));
        self::assertEqualsCanonicalizing($all, array_column($this->request('GET', '/api/pages', $admin)['data'], 'id'));
        // The status filter means "currently public", for every viewer
        self::assertEqualsCanonicalizing($public, array_column($this->request('GET', '/api/pages?status=published', $admin)['data'], 'id'));

        // Single page
        foreach ([$future, $expired] as $hidden) {
            $this->request('GET', '/api/pages/'.$hidden->getId());
            self::assertResponseStatusCodeSame(404);

            foreach ([$author, $admin] as $user) {
                $this->request('GET', '/api/pages/'.$hidden->getId(), $user);
                self::assertResponseIsSuccessful();
            }
        }
        $this->request('GET', '/api/pages/'.$scheduledLive->getId());
        self::assertResponseIsSuccessful();
    }

    public function testDraftContentDoesNotLeakThroughBlocksMediaOrRevisions(): void
    {
        $author = $this->createUser('author@example.com');
        $other = $this->createUser('other@example.com');
        [$draft, $block, $media] = $this->createPage($author, 'draft');

        $this->request('GET', '/api/blocks/'.$block->getId(), $other);
        self::assertResponseStatusCodeSame(404);

        $this->request('GET', '/api/media/'.$media->getId(), $other);
        self::assertResponseStatusCodeSame(404);

        self::assertSame([], array_column($this->request('GET', '/api/media', $other), 'id'));

        $this->request('GET', '/api/pages/'.$draft->getId().'/revisions', $other);
        self::assertResponseStatusCodeSame(404);

        $this->request('GET', '/api/blocks/'.$block->getId(), $author);
        self::assertResponseIsSuccessful();

        self::assertSame([$media->getId()], array_column($this->request('GET', '/api/media', $author), 'id'));

        $this->request('GET', '/api/pages/'.$draft->getId().'/revisions', $author);
        self::assertResponseIsSuccessful();
    }

    public function testLoginAndCategoryPermissionsStillWork(): void
    {
        $this->createUser('admin@example.com', ['ROLE_ADMIN']);
        $user = $this->createUser('user@example.com');

        $login = $this->request('POST', '/api/login', body: ['email' => 'admin@example.com', 'password' => self::PASSWORD]);
        self::assertResponseIsSuccessful();
        self::assertArrayHasKey('token', $login);

        $category = ['name' => 'News', 'slug' => 'news'];
        $this->request('POST', '/api/categories', $user, $category);
        self::assertResponseStatusCodeSame(403);

        $this->client->request('POST', '/api/categories', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$login['token'],
        ], content: json_encode($category, JSON_THROW_ON_ERROR));
        self::assertResponseStatusCodeSame(201);
    }

    public function testOnlyTheAuthorOrAnAdminCanChangeContent(): void
    {
        $author = $this->createUser('author@example.com');
        $admin = $this->createUser('admin@example.com', ['ROLE_ADMIN']);
        $other = $this->createUser('other@example.com');
        [$page, $block, $media] = $this->createPage($author, 'published');

        $writes = [
            ['PUT', '/api/pages/'.$page->getId(), ['title' => 'Changed']],
            ['POST', '/api/blocks', ['type' => 'text', 'pageId' => $page->getId(), 'content' => 'More']],
            ['PUT', '/api/blocks/'.$block->getId(), ['content' => 'Changed']],
            ['POST', '/api/media', ['filename' => 'b.png', 'type' => 'image', 'url' => '/uploads/b.png', 'blockId' => $block->getId()]],
            ['PUT', '/api/media/'.$media->getId(), ['alt' => 'Changed']],
        ];
        foreach ($writes as [$method, $uri, $body]) {
            $this->request($method, $uri, $other, $body);
            self::assertResponseStatusCodeSame(403, "other user: $method $uri");

            foreach ([$author, $admin] as $user) {
                $this->request($method, $uri, $user, $body);
                self::assertResponseIsSuccessful("$method $uri as ".$user->getEmail());
            }
        }

        // Deletes, innermost first; the other user is refused every time
        foreach (['/api/media/'.$media->getId(), '/api/blocks/'.$block->getId(), '/api/pages/'.$page->getId()] as $uri) {
            $this->request('DELETE', $uri, $other);
            self::assertResponseStatusCodeSame(403, "other user: DELETE $uri");

            $this->request('DELETE', $uri, $author);
            self::assertResponseIsSuccessful("author: DELETE $uri");
        }
    }

    /**
     * @template T of object
     * @param class-string<T> $id
     * @return T
     */
    private function service(string $id): object
    {
        $service = static::getContainer()->get($id);
        self::assertInstanceOf($id, $service);

        return $service;
    }

    private function em(): EntityManagerInterface
    {
        return $this->service(EntityManagerInterface::class);
    }

    /**
     * @param array<string, mixed>|null $body
     * @return array<mixed>
     */
    private function request(string $method, string $uri, ?User $as = null, ?array $body = null): array
    {
        $server = ['CONTENT_TYPE' => 'application/json'];
        if ($as) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$this->service(JWTTokenManagerInterface::class)->create($as);
        }

        $this->client->request($method, $uri, server: $server, content: $body === null ? null : json_encode($body, JSON_THROW_ON_ERROR));

        return json_decode((string) $this->client->getResponse()->getContent(), true) ?? [];
    }

    /**
     * @param list<string> $roles
     */
    private function createUser(string $email, array $roles = []): User
    {
        $user = (new User())->setEmail($email)->setRoles($roles);
        $user->setPassword($this->service(UserPasswordHasherInterface::class)->hashPassword($user, self::PASSWORD));

        $this->em()->persist($user);
        $this->em()->flush();

        return $user;
    }

    private function reloadUser(string $email): ?User
    {
        $this->em()->clear();

        return $this->em()->getRepository(User::class)->findOneBy(['email' => $email]);
    }

    /**
     * @return array{Page, Block, Media}
     */
    private function createPage(User $author, string $status, ?\DateTimeImmutable $publishAt = null, ?\DateTimeImmutable $unpublishAt = null): array
    {
        $page = (new Page())->setTitle(ucfirst($status))->setSlug($status.'-'.bin2hex(random_bytes(4)))->setStatus($status)->setAuthor($author)
            ->setPublishAt($publishAt)->setUnpublishAt($unpublishAt);
        $block = (new Block())->setType('text')->setContent('Body')->setPosition(0);
        $page->addBlock($block);
        $media = (new Media())->setFilename('photo.png')->setType('image')->setUrl('/uploads/photo.png');
        $block->addMedia($media);

        $this->em()->persist($page);
        $this->em()->persist($block);
        $this->em()->persist($media);
        $this->em()->flush();

        return [$page, $block, $media];
    }
}
