<?php

namespace App\DataFixtures;

use App\Entity\Account;
use App\Entity\Category;
use App\Entity\Household;
use App\Entity\HouseholdMember;
use App\Entity\User;
use App\Enum\CategoryEnvelope;
use App\Enum\HouseholdMemberRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $admin = $this->createUser('loriana@test.fr', 'Loriana', '#0070C0');
        $viewer = $this->createUser('viewer@test.fr', 'Viewer', '#83E28E');
        $manager->persist($admin);
        $manager->persist($viewer);

        $household = new Household();
        $household->setName('Mon foyer');
        $manager->persist($household);

        $manager->persist($this->createMember($admin, $household, HouseholdMemberRole::Admin));
        $manager->persist($this->createMember($viewer, $household, HouseholdMemberRole::Viewer));

        $account = new Account();
        $account->setHousehold($household);
        $account->setName('Compte courant');
        $account->setType('checking');
        $account->setInitialBalance('1500.00');
        $manager->persist($account);

        // Parent category, then a child that inherits its envelope
        $rent = new Category();
        $rent->setHousehold($household);
        $rent->setName('Loyer');
        $rent->setEnvelope(CategoryEnvelope::Essential);
        $manager->persist($rent);

        $rent = new Category();
        $rent->setHousehold($household);
        $rent->setName('Loyer');
        $manager->persist($rent);

        $manager->flush();
    }

    private function createUser(string $email, string $name, string $colour): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setColour($colour);

        $hashedPassword = $this->passwordHasher->hashPassword($user, 'MotDePasse123');
        $user->setPassword($hashedPassword);

        return $user;
    }

    private function createMember(User $user, Household $household, HouseholdMemberRole $role): HouseholdMember
    {
        $member = new HouseholdMember();
        $member->setUser($user);
        $member->setHousehold($household);
        $member->setRole($role);

        return $member;
    }
}
