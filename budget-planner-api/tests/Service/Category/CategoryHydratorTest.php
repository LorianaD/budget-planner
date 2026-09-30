<?php

namespace App\Tests\Unit\Service\Category;

use App\Entity\Category;
use App\Entity\Household;
use App\Entity\User;
use App\Enum\CategoryEnvelope;
use App\Exception\ValidationException;
use App\Repository\HouseholdRepository;
use App\Service\Category\CategoryHydrator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CategoryHydratorTest extends TestCase
{
    private Household $household;
    private User $user;
    private CategoryHydrator $hydrator;

    protected function setUp(): void
    {
        $this->household = new Household();
        $this->user = new User();

        $householdRepository = $this->createStub(HouseholdRepository::class);
        $householdRepository->method('findOneForUser')->willReturn($this->household);

        $this->hydrator = new CategoryHydrator($householdRepository);
    }

    public function testHydrateFillsEveryFieldOnCreation(): void
    {
        $category = new Category();
        $data = [
            'householdId' => 1,
            'name' => '  Courses  ',
            'envelope' => 'essential',
        ];

        $this->hydrator->hydrate($category, $data, $this->user, false);

        self::assertSame($this->household, $category->getHousehold());
        self::assertSame('Courses', $category->getName());
        self::assertSame(CategoryEnvelope::Essential, $category->getEnvelope());
    }

    public function testEnvelopeIsOptionalOnCreation(): void
    {
        $category = new Category();
        $data = [
            'householdId' => 1,
            'name' => 'Courses',
        ];

        $this->hydrator->hydrate($category, $data, $this->user, false);

        self::assertNull($category->getEnvelope());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function requiredFieldProvider(): array
    {
        return [
            'householdId manquant' => ['householdId'],
            'name manquant' => ['name'],
        ];
    }

    #[DataProvider('requiredFieldProvider')]
    public function testCreationFailsWhenARequiredFieldIsMissing(string $missingField): void
    {
        $data = [
            'householdId' => 1,
            'name' => 'Courses',
        ];
        unset($data[$missingField]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(sprintf('Le champ "%s" est obligatoire.', $missingField));

        $this->hydrator->hydrate(new Category(), $data, $this->user, false);
    }

    public function testPartialUpdateOnlyChangesTheSentFields(): void
    {
        $category = new Category();
        $category->setHousehold($this->household);
        $category->setName('Ancien nom');
        $category->setEnvelope(CategoryEnvelope::Leisure);

        $this->hydrator->hydrate($category, ['name' => 'Nouveau nom'], $this->user, true);

        self::assertSame('Nouveau nom', $category->getName());
        self::assertSame(CategoryEnvelope::Leisure, $category->getEnvelope());
        self::assertSame($this->household, $category->getHousehold());
    }

    public function testEnvelopeCanBeRemovedWithNull(): void
    {
        $category = new Category();
        $category->setEnvelope(CategoryEnvelope::Savings);

        $this->hydrator->hydrate($category, ['envelope' => null], $this->user, true);

        self::assertNull($category->getEnvelope());
    }

    /**
     * @return array<string, array{mixed, string}>
     */
    public static function invalidDataProvider(): array
    {
        return [
            'nom vide' => [['name' => ''], 'Le nom de la catégorie est obligatoire.'],
            'nom avec seulement des espaces' => [['name' => '   '], 'Le nom de la catégorie est obligatoire.'],
            'nom trop long' => [['name' => str_repeat('a', 101)], 'Le nom de la catégorie ne doit pas dépasser 100 caractères.'],
            'enveloppe inconnue' => [['envelope' => 'vacances'], 'Enveloppe invalide (essential, leisure ou savings).'],
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
        $this->expectExceptionMessage($expectedMessage);

        $this->hydrator->hydrate(new Category(), $data, $this->user, true);
    }

    public function testNameOfExactly100CharactersIsAccepted(): void
    {
        $category = new Category();
        $name = str_repeat('é', 100);

        $this->hydrator->hydrate($category, ['name' => $name], $this->user, true);

        self::assertSame($name, $category->getName());
    }

    public function testUnknownHouseholdIsRejected(): void
    {
        $householdRepository = $this->createStub(HouseholdRepository::class);
        $householdRepository->method('findOneForUser')->willReturn(null);
        $hydrator = new CategoryHydrator($householdRepository);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Foyer introuvable.');

        $hydrator->hydrate(new Category(), ['householdId' => 999], $this->user, true);
    }
}
