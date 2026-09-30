<?php

namespace App\Tests\Service\Transaction;

use App\Entity\Account;
use App\Entity\Category;
use App\Entity\Transaction;
use App\Entity\User;
use App\Enum\TransactionFrequency;
use App\Enum\TransactionType;
use App\Exception\ValidationException;
use App\Repository\AccountRepository;
use App\Repository\CategoryRepository;
use App\Service\Transaction\TransactionHydrator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TransactionHydratorTest extends TestCase
{
    private Account $account;
    private Category $category;
    private User $user;
    private TransactionHydrator $hydrator;

    protected function setUp(): void
    {
        $this->account = new Account();
        $this->category = new Category();
        $this->user = new User();

        $accountRepository = $this->createStub(AccountRepository::class);
        $accountRepository->method('findOneForUser')->willReturn($this->account);

        $categoryRepository = $this->createStub(CategoryRepository::class);
        $categoryRepository->method('findOneForUser')->willReturn($this->category);

        $this->hydrator = new TransactionHydrator($accountRepository, $categoryRepository);
    }

    /**
     * @return array<string, mixed>
     */
    private function validData(): array
    {
        return [
            'accountId' => 1,
            'categoryId' => 2,
            'type' => 'expense',
            'amount' => '45.90',
            'date' => '2026-09-15',
            'label' => '  Supermarché  ',
            'isRecurring' => false,
        ];
    }

    public function testHydrateFillsEveryFieldOnCreation(): void
    {
        $transaction = new Transaction();

        $this->hydrator->hydrate($transaction, $this->validData(), $this->user, false);

        self::assertSame($this->account, $transaction->getAccount());
        self::assertSame($this->category, $transaction->getCategory());
        self::assertSame(TransactionType::Expense, $transaction->getType());
        self::assertSame('45.90', $transaction->getAmount());
        self::assertSame('2026-09-15', $transaction->getDate()->format('Y-m-d'));
        self::assertSame('Supermarché', $transaction->getLabel());
        self::assertFalse($transaction->isRecurring());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function requiredFieldProvider(): array
    {
        return [
            'accountId' => ['accountId'],
            'categoryId' => ['categoryId'],
            'type' => ['type'],
            'amount' => ['amount'],
            'date' => ['date'],
            'label' => ['label'],
        ];
    }

    #[DataProvider('requiredFieldProvider')]
    public function testCreationFailsWhenARequiredFieldIsMissing(string $missingField): void
    {
        $data = $this->validData();
        unset($data[$missingField]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIs(sprintf('Le champ "%s" est obligatoire.', $missingField));

        $this->hydrator->hydrate(new Transaction(), $data, $this->user, false);
    }

    public function testPartialUpdateOnlyChangesTheSentFields(): void
    {
        $transaction = new Transaction();
        $this->hydrator->hydrate($transaction, $this->validData(), $this->user, false);

        $this->hydrator->hydrate($transaction, ['amount' => '50'], $this->user, true);

        self::assertSame('50.00', $transaction->getAmount());
        self::assertSame('Supermarché', $transaction->getLabel());
        self::assertSame(TransactionType::Expense, $transaction->getType());
    }

    /**
     * @return array<string, array{mixed, string}>
     */
    public static function validAmountProvider(): array
    {
        return [
            'entier' => ['12', '12.00'],
            'une décimale' => ['12.5', '12.50'],
            'deux décimales' => ['12.50', '12.50'],
            'nombre JSON' => [12.5, '12.50'],
            'plus petit montant' => ['0.01', '0.01'],
            'plus grand montant' => ['99999999.99', '99999999.99'],
        ];
    }

    // The amount is always stored with 2 decimals, like MySQL returns it
    #[DataProvider('validAmountProvider')]
    public function testValidAmountsAreAccepted(mixed $amount, string $expectedAmount): void
    {
        $transaction = new Transaction();

        $this->hydrator->hydrate($transaction, ['amount' => $amount], $this->user, true);

        self::assertSame($expectedAmount, $transaction->getAmount());
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidAmountProvider(): array
    {
        return [
            'zéro' => ['0'],
            'zéro avec décimales' => ['0.00'],
            'négatif' => ['-5'],
            'trois décimales' => ['12.345'],
            'virgule française' => ['12,50'],
            'texte' => ['abc'],
            'notation scientifique' => ['1e3'],
            'trop de chiffres' => ['123456789'],
        ];
    }

    #[DataProvider('invalidAmountProvider')]
    public function testInvalidAmountsAreRejected(mixed $amount): void
    {
        $this->expectException(ValidationException::class);

        $this->hydrator->hydrate(new Transaction(), ['amount' => $amount], $this->user, true);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidDateProvider(): array
    {
        return [
            'date impossible' => ['2026-02-30'],
            'format français' => ['15/09/2026'],
            'sans zéro initial' => ['2026-9-5'],
            'avec une heure' => ['2026-09-15 10:00:00'],
            'texte' => ['demain'],
        ];
    }

    #[DataProvider('invalidDateProvider')]
    public function testInvalidDatesAreRejected(string $date): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIs('Le champ "date" doit être une date au format AAAA-MM-JJ.');

        $this->hydrator->hydrate(new Transaction(), ['date' => $date], $this->user, true);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidDataProvider(): array
    {
        return [
            'type inconnu' => [['type' => 'transfer'], 'Type de transaction invalide.'],
            'libellé vide' => [['label' => '   '], 'Le libellé est obligatoire.'],
            'libellé trop long' => [['label' => str_repeat('a', 256)], 'Le libellé ne doit pas dépasser 255 caractères.'],
            'isRecurring en texte' => [['isRecurring' => 'true'], 'Le champ "isRecurring" doit être true ou false.'],
            'fréquence inconnue' => [['frequency' => 'weekly'], 'Fréquence invalide.'],
            'compte en texte' => [['accountId' => '1'], 'Le compte est invalide.'],
            'catégorie en texte' => [['categoryId' => '2'], 'La catégorie est invalide.'],
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

        $this->hydrator->hydrate(new Transaction(), $data, $this->user, true);
    }

    public function testUnknownAccountIsRejected(): void
    {
        $accountRepository = $this->createStub(AccountRepository::class);
        $accountRepository->method('findOneForUser')->willReturn(null);
        $hydrator = new TransactionHydrator($accountRepository, $this->createStub(CategoryRepository::class));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIs('Compte introuvable.');

        $hydrator->hydrate(new Transaction(), ['accountId' => 999], $this->user, true);
    }

    public function testUnknownCategoryIsRejected(): void
    {
        $categoryRepository = $this->createStub(CategoryRepository::class);
        $categoryRepository->method('findOneForUser')->willReturn(null);
        $hydrator = new TransactionHydrator($this->createStub(AccountRepository::class), $categoryRepository);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIs('Catégorie introuvable.');

        $hydrator->hydrate(new Transaction(), ['categoryId' => 999], $this->user, true);
    }

    public function testRecurringTransactionKeepsItsFrequencyAndEndDate(): void
    {
        $transaction = new Transaction();
        $data = $this->validData();
        $data['isRecurring'] = true;
        $data['frequency'] = 'monthly';
        $data['commitmentEndDate'] = '2027-12-31';

        $this->hydrator->hydrate($transaction, $data, $this->user, false);

        self::assertTrue($transaction->isRecurring());
        self::assertSame(TransactionFrequency::Monthly, $transaction->getFrequency());
        self::assertSame('2027-12-31', $transaction->getCommitmentEndDate()->format('Y-m-d'));
    }

    public function testRecurringTransactionWithoutFrequencyIsRejected(): void
    {
        $data = $this->validData();
        $data['isRecurring'] = true;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIs('Une transaction récurrente doit avoir une fréquence.');

        $this->hydrator->hydrate(new Transaction(), $data, $this->user, false);
    }

    public function testStoppingTheRecurrenceClearsFrequencyAndEndDate(): void
    {
        $transaction = new Transaction();
        $data = $this->validData();
        $data['isRecurring'] = true;
        $data['frequency'] = 'yearly';
        $data['commitmentEndDate'] = '2027-12-31';
        $this->hydrator->hydrate($transaction, $data, $this->user, false);

        $this->hydrator->hydrate($transaction, ['isRecurring' => false], $this->user, true);

        self::assertFalse($transaction->isRecurring());
        self::assertNull($transaction->getFrequency());
        self::assertNull($transaction->getCommitmentEndDate());
    }

    public function testInvalidCommitmentEndDateMentionsTheRightField(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIs('Le champ "commitmentEndDate" doit être une date au format AAAA-MM-JJ.');

        $this->hydrator->hydrate(new Transaction(), ['commitmentEndDate' => '2027-13-01'], $this->user, true);
    }
}
