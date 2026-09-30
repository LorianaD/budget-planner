<?php

namespace App\Tests\Service\Household;

use App\Entity\Household;
use App\Exception\ValidationException;
use App\Service\Household\HouseholdHydrator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HouseholdHydratorTest extends TestCase
{
    private HouseholdHydrator $hydrator;

    protected function setUp(): void
    {
        $this->hydrator = new HouseholdHydrator();
    }

    public function testNameIsTrimmedOnCreation(): void
    {
        $household = new Household();

        $this->hydrator->hydrate($household, ['name' => '  Famille Martin  '], false);

        self::assertSame('Famille Martin', $household->getName());
    }

    public function testCreationFailsWithoutName(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIs('Le champ "name" est obligatoire.');

        $this->hydrator->hydrate(new Household(), [], false);
    }

    public function testEmptyPatchChangesNothing(): void
    {
        $household = new Household();
        $household->setName('Famille Martin');

        $this->hydrator->hydrate($household, [], true);

        self::assertSame('Famille Martin', $household->getName());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidNameProvider(): array
    {
        return [
            'nom vide' => ['', 'Le nom du foyer est obligatoire.'],
            'seulement des espaces' => ['   ', 'Le nom du foyer est obligatoire.'],
            'trop long' => [str_repeat('a', 101), 'Le nom du foyer ne doit pas dépasser 100 caractères.'],
        ];
    }

    #[DataProvider('invalidNameProvider')]
    public function testInvalidNameIsRejected(string $name, string $expectedMessage): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIs($expectedMessage);

        $this->hydrator->hydrate(new Household(), ['name' => $name], true);
    }
}
