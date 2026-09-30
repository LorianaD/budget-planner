<?php

namespace App\Tests\Controller;

use App\Entity\Account;
use App\Entity\Category;
use App\Entity\Household;
use App\Entity\HouseholdMember;
use App\Entity\Transaction;
use App\Entity\User;
use App\Enum\HouseholdMemberRole;
use App\Enum\TransactionType;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

// Shared helpers for the endpoint tests: clean database, fixtures, JWT login and JSON requests
abstract class ApiTestCase extends WebTestCase
{
    protected const PASSWORD = 'motdepasse123';

    // Children first, otherwise the foreign keys block the delete
    private const ENTITIES_IN_DELETE_ORDER = [
        'App\Entity\ScenarioTransaction',
        'App\Entity\Transaction',
        'App\Entity\Scenario',
        'App\Entity\Category',
        'App\Entity\Account',
        'App\Entity\HouseholdMember',
        'App\Entity\Household',
        'App\Entity\User',
    ];

    protected KernelBrowser $client;
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $this->resetDatabase();
    }

    private function resetDatabase(): void
    {
        foreach (self::ENTITIES_IN_DELETE_ORDER as $entityClass) {
            $this->entityManager->createQuery('DELETE FROM ' . $entityClass)->execute();
        }
    }

    // ---------- Fixtures ----------

    protected function createUser(string $email, string $name = 'Utilisateur test'): User
    {
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setPassword($passwordHasher->hashPassword($user, self::PASSWORD));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    protected function createHousehold(User $admin, string $name = 'Foyer test'): Household
    {
        $household = new Household();
        $household->setName($name);
        $this->entityManager->persist($household);

        $this->addMember($household, $admin, HouseholdMemberRole::Admin);

        return $household;
    }

    protected function addMember(Household $household, User $user, HouseholdMemberRole $role): HouseholdMember
    {
        $member = new HouseholdMember();
        $member->setUser($user);
        $member->setRole($role);
        $household->addHouseholdMember($member);

        $this->entityManager->persist($member);
        $this->entityManager->flush();

        return $member;
    }

    protected function createAccount(Household $household, string $name = 'Compte courant'): Account
    {
        $account = new Account();
        $account->setHousehold($household);
        $account->setName($name);
        $account->setType('checking');

        $this->entityManager->persist($account);
        $this->entityManager->flush();

        return $account;
    }

    protected function createCategory(Household $household, string $name = 'Courses'): Category
    {
        $category = new Category();
        $category->setHousehold($household);
        $category->setName($name);

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }

    protected function createTransaction(User $user, Account $account, Category $category, string $label = 'Supermarché'): Transaction
    {
        $transaction = new Transaction();
        $transaction->setUser($user);
        $transaction->setAccount($account);
        $transaction->setCategory($category);
        $transaction->setType(TransactionType::Expense);
        $transaction->setAmount('45.90');
        $transaction->setDate(new \DateTimeImmutable('2026-09-15'));
        $transaction->setLabel($label);
        $transaction->setIsRecurring(false);

        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        return $transaction;
    }

    // ---------- Authentication and requests ----------

    // Generates the token directly: the /api/login_check route is tested in RegisterControllerTest
    protected function loginAs(User $user): void
    {
        $tokenManager = static::getContainer()->get(JWTTokenManagerInterface::class);
        $token = $tokenManager->create($user);

        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer ' . $token);
    }

    /**
     * @param array<string, mixed>|null $data
     */
    protected function requestJson(string $method, string $uri, ?array $data = null): void
    {
        $content = null;
        if ($data !== null) {
            $content = json_encode($data);
        }

        // Forgets the fixtures kept in memory: the API must read everything from the database, like in real life
        $this->entityManager->clear();

        $this->client->request(
            $method,
            $uri,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            $content
        );
    }

    /**
     * @return array<mixed>
     */
    protected function responseData(): array
    {
        $content = $this->client->getResponse()->getContent();

        return json_decode($content, true);
    }

    protected function assertResponseMessage(string $expectedMessage): void
    {
        $data = $this->responseData();

        self::assertSame($expectedMessage, $data['message']);
    }
}
