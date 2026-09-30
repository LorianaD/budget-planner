<?php

namespace App\Tests\Service\Account;

use App\Entity\Account;
use App\Entity\Household;
use App\Entity\User;
use App\Exception\ValidationException;
use App\Repository\HouseholdRepository;
use App\Service\Account\AccountHydrator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AccountHydratorTest extends TestCase
{
    private Household $household;
    private User $user;
    private AccountHydrator $hydrator;

    protected function setUp(): void
    {
        $this->household = new Household();
        $this->user = new User();

        $householdRepository = $this->createStub(HouseholdRepository::class);
        $householdRepository->method('findOneForUser')->willReturn($this->household);

        $this->hydrator = new AccountHydrator($householdRepository);
    }

    public function testHydrateFillsEveryFieldOnCreation(): void
    {
        $account = new Account();
        $data = [
            'householdId' => 1,
            'name' => '  Compte joint  ',
            'type' => 'joint',
            'initialBalance' => '1250.50',
        ];

        $this->hydrator->hydrate($account, $data, $this->user, false);

        self::assertSame($this->household, $account->getHousehold());
        self::assertSame('Compte joint', $account->getName());
        self::assertSame('joint', $account->getType());
        self::assertSame('1250.50', $account->getInitialBalance());
    }

    public function testInitialBalanceIsZeroByDefault(): void
    {
        $account = new Account();
        $data = [
            'householdId' => 1,
            'name' => 'Livret A',
            'type' => 'savings',
        ];

        $this->hydrator->hydrate($account, $data, $this->user, false);

        self::assertSame('0.00', $account->getInitialBalance());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function requiredFieldProvider(): array
    {
        return [
            'householdId' => ['householdId'],
            'name' => ['name'],
            'type' => ['type'],
        ];
    }

    #[DataProvider('requiredFieldProvider')]
    public function testCreationFailsWhenARequiredFieldIsMissing(string $missingField): void
    {
        $data = [
            'householdId' => 1,
            'name' => 'Livret A',
            'type' => 'savings',
        ];
        unset($data[$missingField]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIs(sprintf('Le champ "%s" est obligatoire.', $missingField));

        $this->hydrator->hydrate(new Account(), $data, $this->user, false);
    }

    public function testPartialUpdateOnlyChangesTheSentFields(): void
    {
        $account = new Account();
        $account->setHousehold($this->household);
        $account->setName('Ancien nom');
        $account->setType('checking');

        $this->hydrator->hydrate($account, ['name' => 'Nouveau nom'], $this->user, true);

        self::assertSame('Nouveau nom', $account->getName());
        self::assertSame('checking', $account->getType());
    }

    /**
     * @return array<string, array{mixed, string}>
     */
    public static function validInitialBalanceProvider(): array
    {
        return [
            'zéro' => ['0', '0.00'],
            'positif' => ['1250.5', '1250.50'],
            // A bank account can be overdrawn
            'négatif' => ['-120.30', '-120.30'],
            'nombre JSON' => [300, '300.00'],
        ];
    }

    // The balance is always stored with 2 decimals, like MySQL returns it
    #[DataProvider('validInitialBalanceProvider')]
    public function testValidInitialBalancesAreAccepted(mixed $initialBalance, string $expectedBalance): void
    {
        $account = new Account();

        $this->hydrator->hydrate($account, ['initialBalance' => $initialBalance], $this->user, true);

        self::assertSame($expectedBalance, $account->getInitialBalance());
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidDataProvider(): array
    {
        return [
            'nom vide' => [['name' => '  '], 'Le nom du compte est obligatoire.'],
            'nom trop long' => [['name' => str_repeat('a', 101)], 'Le nom du compte ne doit pas dépasser 100 caractères.'],
            'type inconnu' => [['type' => 'crypto'], 'Type de compte invalide (checking, savings ou joint).'],
            'solde avec virgule' => [['initialBalance' => '12,50'], 'Le solde initial doit être un nombre avec 2 décimales maximum.'],
            'solde avec 3 décimales' => [['initialBalance' => '12.505'], 'Le solde initial doit être un nombre avec 2 décimales maximum.'],
            'solde en texte' => [['initialBalance' => 'beaucoup'], 'Le solde initial doit être un nombre avec 2 décimales maximum.'],
            'foyer en texte' => [['householdId' => '1'], 'Le foyer est invalide.'],
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('invalidDataProvider')]
    public function testInvalidDataIsRejected(array $data, string $expectedMessage): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIs($expectedMessage);

        $this->hydrator->hydrate(new Account(), $data, $this->user, true);
    }

    public function testUnknownHouseholdIsRejected(): void
    {
        $householdRepository = $this->createStub(HouseholdRepository::class);
        $householdRepository->method('findOneForUser')->willReturn(null);
        $hydrator = new AccountHydrator($householdRepository);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIs('Foyer introuvable.');

        $hydrator->hydrate(new Account(), ['householdId' => 999], $this->user, true);
    }
}
