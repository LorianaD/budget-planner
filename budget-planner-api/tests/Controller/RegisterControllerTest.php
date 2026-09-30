<?php

namespace App\Tests\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;

final class RegisterControllerTest extends ApiTestCase
{
    public function testUserCanRegister(): void
    {
        $this->requestJson('POST', '/api/register', [
            'email' => 'loriana@example.com',
            'password' => self::PASSWORD,
            'name' => '  Loriana  ',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = $this->responseData();
        self::assertIsInt($data['id']);
        self::assertSame('loriana@example.com', $data['email']);
        self::assertSame('Loriana', $data['name']);
        self::assertArrayNotHasKey('password', $data);
    }

    public function testEmailAlreadyUsedIsRejected(): void
    {
        $this->createUser('loriana@example.com');

        $this->requestJson('POST', '/api/register', [
            'email' => 'loriana@example.com',
            'password' => self::PASSWORD,
            'name' => 'Loriana',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    /**
     * @return array<string, array{array<string, string>, string}>
     */
    public static function invalidDataProvider(): array
    {
        return [
            'email invalide' => [
                ['email' => 'pas-un-email', 'password' => 'motdepasse123', 'name' => 'Loriana'],
                'Email invalide.',
            ],
            'mot de passe trop court' => [
                ['email' => 'loriana@example.com', 'password' => 'court', 'name' => 'Loriana'],
                'Le mot de passe doit contenir au moins 8 caractères.',
            ],
            'nom manquant' => [
                ['email' => 'loriana@example.com', 'password' => 'motdepasse123'],
                'Le nom est obligatoire.',
            ],
        ];
    }

    /**
     * @param array<string, string> $data
     */
    #[DataProvider('invalidDataProvider')]
    public function testInvalidDataIsRejected(array $data, string $expectedMessage): void
    {
        $this->requestJson('POST', '/api/register', $data);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertResponseMessage($expectedMessage);
    }

    public function testBodyMustBeJson(): void
    {
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], 'pas du json');

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testRegisteredUserCanLogIn(): void
    {
        $this->requestJson('POST', '/api/register', [
            'email' => 'loriana@example.com',
            'password' => self::PASSWORD,
            'name' => 'Loriana',
        ]);

        $this->requestJson('POST', '/api/login_check', [
            'email' => 'loriana@example.com',
            'password' => self::PASSWORD,
        ]);

        self::assertResponseIsSuccessful();
        $data = $this->responseData();
        self::assertArrayHasKey('token', $data);
    }

    public function testLoginWithWrongPasswordIsRejected(): void
    {
        $this->createUser('loriana@example.com');

        $this->requestJson('POST', '/api/login_check', [
            'email' => 'loriana@example.com',
            'password' => 'mauvais-mot-de-passe',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
