<?php

namespace App\Tests\Controller\Api;

use App\Entity\Block;
use App\Entity\Category;
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

        $response = $this->send('PATCH', '/api/profile', $user, json_encode(['email' => 'taken@example.com', 'currentPassword' => self::PASSWORD]));

        self::assertResponseStatusCodeSame(409);
        self::assertStringNotContainsString('SQLSTATE', json_encode($response));
        self::assertNotNull($this->reloadUser('user@example.com'));
    }

    public function testRegisteringAnEmailAlreadyInUseStillAnswers409(): void
    {
        $this->createUser('taken@example.com');

        $this->send('POST', '/api/register', null, json_encode(['email' => 'taken@example.com', 'password' => self::PASSWORD]));

        self::assertResponseStatusCodeSame(409);
    }

    private function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    private function send(string $method, string $uri, ?User $as, string $body): array
    {
        $server = ['CONTENT_TYPE' => 'application/json'];
        if ($as) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.static::getContainer()->get(JWTTokenManagerInterface::class)->create($as);
        }

        $this->client->request($method, $uri, server: $server, content: $body);

        return json_decode($this->client->getResponse()->getContent(), true) ?? [];
    }

    private function createUser(string $email, array $roles = []): User
    {
        $user = (new User())->setEmail($email)->setRoles($roles);
        $user->setPassword(static::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($user, self::PASSWORD));

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
