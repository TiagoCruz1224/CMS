<?php

namespace App\Tests\Controller\Api;

use App\Entity\Block;
use App\Entity\Category;
use App\Entity\Media;
use App\Entity\Page;
use App\Entity\User;
use App\Service\PageService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Regression tests for client errors that used to answer 500.
 */
class ErrorHandlingTest extends WebTestCase
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

    public function testMalformedJsonBodiesAnswer400(): void
    {
        $admin = $this->createUser('admin@example.com', ['ROLE_ADMIN']);
        [$page, $block, $media, $category] = $this->createContent($admin);

        $endpoints = [
            ['POST', '/api/register', null],
            ['PATCH', '/api/profile', $admin],
            ['PUT', '/api/profile', $admin],
            ['POST', '/api/pages', $admin],
            ['PUT', '/api/pages/'.$page->getId(), $admin],
            ['POST', '/api/blocks', $admin],
            ['PUT', '/api/blocks/'.$block->getId(), $admin],
            ['POST', '/api/media', $admin],
            ['PUT', '/api/media/'.$media->getId(), $admin],
            ['POST', '/api/categories', $admin],
            ['PUT', '/api/categories/'.$category->getId(), $admin],
        ];

        foreach ($endpoints as [$method, $uri, $as]) {
            foreach (['{"firstName": ', '"just a string"', ''] as $body) {
                $response = $this->send($method, $uri, $as, $body);

                self::assertSame(400, $this->client->getResponse()->getStatusCode(), "$method $uri with body '$body'");
                self::assertArrayHasKey('error', $response, "$method $uri with body '$body'");
            }
        }

        // Authentication is still checked first
        $this->send('PATCH', '/api/profile', null, '{"firstName": ');
        self::assertResponseStatusCodeSame(401);
    }

    public function testChangingEmailToOneAlreadyInUseAnswers409(): void
    {
        $this->createUser('taken@example.com');
        $user = $this->createUser('user@example.com');

        $response = $this->send('PATCH', '/api/profile', $user, json_encode(['email' => 'taken@example.com', 'currentPassword' => self::PASSWORD], JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(409);
        self::assertStringNotContainsString('SQLSTATE', json_encode($response, JSON_THROW_ON_ERROR));
        self::assertNotNull($this->reloadUser('user@example.com'));
    }

    public function testRegisteringAnEmailAlreadyInUseStillAnswers409(): void
    {
        $this->createUser('taken@example.com');

        $this->send('POST', '/api/register', null, json_encode(['email' => 'taken@example.com', 'password' => self::PASSWORD], JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(409);
    }

    public function testInternalErrorsAreNotExposedButLogged(): void
    {
        // A failure the client cannot cause or fix, with internal details in its message
        $failing = $this->createStub(PageService::class);
        $failing->method('findPaginated')->willThrowException(new \RuntimeException('SQLSTATE[HY000]: simulated database failure'));
        static::getContainer()->set(PageService::class, $failing);

        $logDir = static::getContainer()->getParameter('kernel.logs_dir');
        self::assertIsString($logDir);
        $log = $logDir.'/test.log';
        $logSize = is_file($log) ? (int) filesize($log) : 0;

        $response = $this->send('GET', '/api/pages', null, '');

        self::assertResponseStatusCodeSame(500);
        self::assertSame(['error' => 'Internal server error'], $response);
        self::assertStringContainsString('simulated database failure', (string) file_get_contents($log, offset: $logSize));
    }

    public function testDuplicateSlugsAnswer409(): void
    {
        $admin = $this->createUser('admin@example.com', ['ROLE_ADMIN']);

        $this->send('POST', '/api/pages', $admin, json_encode(['title' => 'A', 'slug' => 'taken'], JSON_THROW_ON_ERROR));
        self::assertResponseStatusCodeSame(201);
        $response = $this->send('POST', '/api/pages', $admin, json_encode(['title' => 'B', 'slug' => 'taken'], JSON_THROW_ON_ERROR));
        self::assertResponseStatusCodeSame(409);
        self::assertArrayHasKey('slug', $response['errors'] ?? []);

        $other = $this->send('POST', '/api/pages', $admin, json_encode(['title' => 'C', 'slug' => 'free'], JSON_THROW_ON_ERROR));
        $this->send('PUT', '/api/pages/'.$other['id'], $admin, json_encode(['slug' => 'taken'], JSON_THROW_ON_ERROR));
        self::assertResponseStatusCodeSame(409);

        $this->send('POST', '/api/categories', $admin, json_encode(['name' => 'News', 'slug' => 'news'], JSON_THROW_ON_ERROR));
        self::assertResponseStatusCodeSame(201);
        $this->send('POST', '/api/categories', $admin, json_encode(['name' => 'More news', 'slug' => 'news'], JSON_THROW_ON_ERROR));
        self::assertResponseStatusCodeSame(409);
        $category = $this->send('POST', '/api/categories', $admin, json_encode(['name' => 'Events', 'slug' => 'events'], JSON_THROW_ON_ERROR));
        $this->send('PUT', '/api/categories/'.$category['id'], $admin, json_encode(['slug' => 'news'], JSON_THROW_ON_ERROR));
        self::assertResponseStatusCodeSame(409);
    }

    public function testInvalidPagesAreNotSaved(): void
    {
        $author = $this->createUser('author@example.com');

        $this->send('POST', '/api/pages', $author, json_encode(['title' => 'Bad', 'slug' => 'bad', 'status' => 'bogus'], JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(400);
        $this->em()->clear();
        self::assertNull($this->em()->getRepository(Page::class)->findOneBy(['slug' => 'bad']));
    }

    public function testTotalPagesIsAnInteger(): void
    {
        $author = $this->createUser('author@example.com');
        $this->createContent($author);

        $list = $this->send('GET', '/api/pages?limit=10', null, '');

        self::assertSame(1, $list['totalPages']);
    }

    public function testMalformedIdsAndPagingAreClientErrors(): void
    {
        $author = $this->createUser('author@example.com');
        [$page] = $this->createContent($author);

        // Non-numeric or oversized ids do not match the routes
        foreach (['/api/pages/abc', '/api/pages/99999999999999999999', '/api/categories/99999999999999999999'] as $uri) {
            $this->send('GET', $uri, null, '');
            self::assertResponseStatusCodeSame(404, $uri);
        }
        $this->send('GET', '/api/pages/'.$page->getId().'/revisions/zzz', $author, '');
        self::assertResponseStatusCodeSame(404);

        // Out-of-range paging is clamped to page >= 1 and 1 <= limit <= 100
        foreach (['limit=0', 'page=-1&limit=-5', 'limit=100000'] as $query) {
            $list = $this->send('GET', '/api/pages?'.$query, null, '');
            self::assertResponseStatusCodeSame(200, $query);
            self::assertGreaterThanOrEqual(1, $list['page'], $query);
            self::assertGreaterThanOrEqual(1, $list['limit'], $query);
            self::assertLessThanOrEqual(100, $list['limit'], $query);
            self::assertGreaterThanOrEqual(0, $list['totalPages'], $query);
        }

        // Values that are not integers at all are a bad request
        $this->send('GET', '/api/pages?limit=99999999999999999999', null, '');
        self::assertResponseStatusCodeSame(400);
    }

    public function testJsonListsAreNotAcceptedAsBodies(): void
    {
        $admin = $this->createUser('admin@example.com', ['ROLE_ADMIN']);
        [$page] = $this->createContent($admin);

        foreach ([['POST', '/api/pages'], ['PUT', '/api/pages/'.$page->getId()], ['PATCH', '/api/profile'], ['POST', '/api/categories']] as [$method, $uri]) {
            foreach (['[]', '[1,2,3]', '[{"title":"x"}]'] as $body) {
                $response = $this->send($method, $uri, $admin, $body);
                self::assertSame(400, $this->client->getResponse()->getStatusCode(), "$method $uri with $body");
                self::assertArrayHasKey('error', $response, "$method $uri with $body");
            }
        }

        // An empty JSON object is still a valid (if useless) body
        $this->send('PUT', '/api/pages/'.$page->getId(), $admin, '{}');
        self::assertResponseIsSuccessful();
    }

    public function testFieldsWithTheWrongTypeAnswer400(): void
    {
        $admin = $this->createUser('admin@example.com', ['ROLE_ADMIN']);
        [$page, $block, $media, $category] = $this->createContent($admin);
        $p = '/api/pages/'.$page->getId();
        $b = '/api/blocks/'.$block->getId();
        $m = '/api/media/'.$media->getId();
        $c = '/api/categories/'.$category->getId();
        $credentials = ['currentPassword' => self::PASSWORD];

        $cases = [
            ['POST', '/api/register', ['email' => ['x'], 'password' => self::PASSWORD]],
            ['POST', '/api/register', ['email' => 'new@example.com', 'password' => 123]],
            ['POST', '/api/register', ['email' => 'new@example.com', 'password' => self::PASSWORD, 'firstName' => ['x']]],
            ['PATCH', '/api/profile', ['firstName' => ['x']]],
            ['PATCH', '/api/profile', ['lastName' => 5]],
            ['PATCH', '/api/profile', ['email' => ['x']] + $credentials],
            ['PATCH', '/api/profile', ['password' => 123] + $credentials],
            ['POST', '/api/pages', ['title' => -1]],
            ['POST', '/api/pages', ['title' => ['x']]],
            ['POST', '/api/pages', ['title' => 'T', 'slug' => 5]],
            ['POST', '/api/pages', ['title' => 'T', 'content' => ['x']]],
            ['POST', '/api/pages', ['title' => 'T', 'categoryId' => 'abc']],
            ['PUT', $p, ['title' => ['x']]],
            ['PUT', $p, ['metaKeywords' => 'a,b']],
            ['PUT', $p, ['ogTags' => 'x']],
            ['PUT', $p, ['template' => 1]],
            ['PUT', $p, ['publishAt' => 123]],
            ['PUT', $p, ['publishAt' => 'not a date']],
            ['PUT', $p, ['unpublishAt' => ['x']]],
            ['PUT', $p, ['isIndexable' => 'yes']],
            ['PUT', $p, ['changeNote' => ['x']]],
            ['PUT', $p, ['category' => 5]],
            ['POST', '/api/blocks', ['type' => 1, 'pageId' => $page->getId()]],
            ['POST', '/api/blocks', ['type' => 'text', 'pageId' => 'abc']],
            ['POST', '/api/blocks', ['type' => 'text', 'pageId' => $page->getId(), 'settings' => 'x']],
            ['PUT', $b, ['position' => 'first']],
            ['PUT', $b, ['content' => ['x']]],
            ['POST', '/api/media', ['filename' => 1, 'type' => 'image', 'url' => '/a.png', 'blockId' => $block->getId()]],
            ['POST', '/api/media', ['filename' => 'a.png', 'type' => 'image', 'url' => '/a.png', 'blockId' => 'abc']],
            ['PUT', $m, ['size' => 'big']],
            ['PUT', $m, ['alt' => ['x']]],
            ['POST', '/api/categories', ['name' => ['x'], 'slug' => 'x']],
            ['PUT', $c, ['slug' => 1]],
            ['PUT', $c, ['description' => ['x']]],
        ];
        foreach ($cases as [$method, $uri, $body]) {
            $as = $uri === '/api/register' ? null : $admin;
            $response = $this->send($method, $uri, $as, json_encode($body, JSON_THROW_ON_ERROR));
            $label = "$method $uri ".json_encode($body, JSON_THROW_ON_ERROR);
            self::assertSame(400, $this->client->getResponse()->getStatusCode(), $label);
            self::assertArrayHasKey('error', $response, $label);
        }

        // Numeric strings are still accepted for ids, as forms often send them
        $this->send('POST', '/api/blocks', $admin, json_encode(['type' => 'text', 'pageId' => (string) $page->getId()], JSON_THROW_ON_ERROR));
        self::assertResponseStatusCodeSame(201);
    }

    public function testUnknownCategoryIsRejected(): void
    {
        $admin = $this->createUser('admin@example.com', ['ROLE_ADMIN']);
        [$page, , , $category] = $this->createContent($admin);

        $response = $this->send('POST', '/api/pages', $admin, json_encode(['title' => 'A', 'slug' => 'a', 'categoryId' => 999999], JSON_THROW_ON_ERROR));
        self::assertResponseStatusCodeSame(400);
        self::assertSame(['error' => 'Category not found'], $response);

        $response = $this->send('PUT', '/api/pages/'.$page->getId(), $admin, json_encode(['categoryId' => 999999], JSON_THROW_ON_ERROR));
        self::assertResponseStatusCodeSame(400);
        self::assertSame(['error' => 'Category not found'], $response);

        // An existing category is still assigned
        $created = $this->send('POST', '/api/pages', $admin, json_encode(['title' => 'B', 'slug' => 'b', 'categoryId' => $category->getId()], JSON_THROW_ON_ERROR));
        self::assertResponseStatusCodeSame(201);
        self::assertSame($category->getId(), $created['category']['id'] ?? null);
    }

    public function testRoutingErrorsAnswerJson(): void
    {
        $cases = [
            ['GET', '/api/does-not-exist', 404, 'Not Found'],
            ['DELETE', '/api/register', 405, 'Method Not Allowed'],
            ['GET', '/api/pages?page[]=1', 400, 'Bad Request'],
        ];
        foreach ($cases as [$method, $uri, $status, $error]) {
            $response = $this->send($method, $uri, null, '');
            self::assertResponseStatusCodeSame($status, "$method $uri");
            self::assertResponseHeaderSame('Content-Type', 'application/json', "$method $uri");
            self::assertSame(['error' => $error], $response, "$method $uri");
            if ($status === 405) {
                self::assertResponseHeaderSame('Allow', 'POST');
            }
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
     * @return array<mixed>
     */
    private function send(string $method, string $uri, ?User $as, string $body): array
    {
        $server = ['CONTENT_TYPE' => 'application/json'];
        if ($as) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$this->service(JWTTokenManagerInterface::class)->create($as);
        }

        $this->client->request($method, $uri, server: $server, content: $body);

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
     * @return array{Page, Block, Media, Category}
     */
    private function createContent(User $author): array
    {
        $category = (new Category())->setName('News')->setSlug('news');
        $page = (new Page())->setTitle('Page')->setSlug('page')->setStatus('published')->setAuthor($author);
        $block = (new Block())->setType('text')->setContent('Body')->setPosition(0);
        $page->addBlock($block);
        $media = (new Media())->setFilename('photo.png')->setType('image')->setUrl('/uploads/photo.png');
        $block->addMedia($media);

        foreach ([$category, $page, $block, $media] as $entity) {
            $this->em()->persist($entity);
        }
        $this->em()->flush();

        return [$page, $block, $media, $category];
    }
}
