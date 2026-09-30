<?php

namespace App\Tests\Unit\Service\HouseholdMember;

use App\Entity\HouseholdMember;
use App\Entity\User;
use App\Enum\HouseholdMemberRole;
use App\Exception\ValidationException;
use App\Repository\UserRepository;
use App\Service\HouseholdMember\HouseholdMemberHydrator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HouseholdMemberHydratorTest extends TestCase
{
    private User $invitedUser;
    private HouseholdMemberHydrator $hydrator;

    protected function setUp(): void
    {
        $this->invitedUser = new User();

        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('findOneByEmail')->willReturn($this->invitedUser);

        $this->hydrator = new HouseholdMemberHydrator($userRepository);
    }

    public function testNewMemberIsAViewerByDefault(): void
    {
        $member = new HouseholdMember();

        $this->hydrator->hydrate($member, ['email' => '  proche@example.com '], false);

        self::assertSame($this->invitedUser, $member->getUser());
        self::assertSame(HouseholdMemberRole::Viewer, $member->getRole());
    }

    public function testNewMemberCanBeAddedAsAdmin(): void
    {
        $member = new HouseholdMember();

        $this->hydrator->hydrate($member, ['email' => 'proche@example.com', 'role' => 'admin'], false);

        self::assertSame(HouseholdMemberRole::Admin, $member->getRole());
    }

    public function testCreationFailsWithoutEmail(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Le champ "email" est obligatoire.');

        $this->hydrator->hydrate(new HouseholdMember(), ['role' => 'viewer'], false);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidEmailProvider(): array
    {
        return [
            'vide' => [''],
            'sans arobase' => ['proche.example.com'],
            'sans domaine' => ['proche@'],
        ];
    }

    #[DataProvider('invalidEmailProvider')]
    public function testInvalidEmailIsRejected(string $email): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Email invalide.');

        $this->hydrator->hydrate(new HouseholdMember(), ['email' => $email], false);
    }

    public function testEmailWithoutAccountIsRejected(): void
    {
        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('findOneByEmail')->willReturn(null);
        $hydrator = new HouseholdMemberHydrator($userRepository);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Aucun compte n\'est associé à cet email.');

        $hydrator->hydrate(new HouseholdMember(), ['email' => 'inconnu@example.com'], false);
    }

    public function testRoleCanBeChangedOnUpdate(): void
    {
        $member = new HouseholdMember();
        $member->setUser($this->invitedUser);
        $member->setRole(HouseholdMemberRole::Viewer);

        $this->hydrator->hydrate($member, ['role' => 'admin'], true);

        self::assertSame(HouseholdMemberRole::Admin, $member->getRole());
        self::assertSame($this->invitedUser, $member->getUser());
    }

    public function testUserCannotBeReplacedOnUpdate(): void
    {
        $this->expectException(ValidationException::class);

        $this->hydrator->hydrate(new HouseholdMember(), ['email' => 'autre@example.com'], true);
    }

    public function testUnknownRoleIsRejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Rôle invalide (admin ou viewer).');

        $this->hydrator->hydrate(new HouseholdMember(), ['role' => 'owner'], true);
    }
}
