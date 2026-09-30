<?php

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Response;

final class UserProfileControllerTest extends ApiTestCase
{
    public function testLoggedInUserGetsTheirProfile(): void
    {
        $user = $this->createUser('loriana@example.com', 'Loriana');
        $this->loginAs($user);

        $this->requestJson('GET', '/api/user/profile');

        self::assertResponseIsSuccessful();

        $expected = [
            'id' => $user->getId(),
            'email' => 'loriana@example.com',
            'name' => 'Loriana',
            'color' => null,
        ];
        self::assertSame($expected, $this->responseData());
    }

    public function testProfileRequiresAToken(): void
    {
        $this->requestJson('GET', '/api/user/profile');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testInvalidTokenIsRejected(): void
    {
        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer faux-token');

        $this->requestJson('GET', '/api/user/profile');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
