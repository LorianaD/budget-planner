<?php

namespace App\Service\User;

use App\Entity\User;
use App\Exception\EmailAlreadyUsedException;
use App\Exception\ValidationException;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserRegisterService
{
    private const MIN_PASSWORD_LENGTH = 8;

    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager
    ) {}

    public function register(array $data): User
    {
        $this->validate($data);

        if ($this->userRepository->isEmailTaken($data['email'])) {
            throw new EmailAlreadyUsedException();
        }

        $user = $this->createUser($data);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function validate(array $data): void
    {
        if (!isset($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException(
                'Email invalide.'
            );
        }

        if (!isset($data['password']) || strlen($data['password']) < self::MIN_PASSWORD_LENGTH) {
            throw new ValidationException(
                'Le mot de passe doit contenir au moins 8 caractères.'
            );
        }

        if (!isset($data['name']) || trim($data['name']) === '') {
            throw new ValidationException(
                'Le nom est obligatoire.'
            );
        }
    }

    private function createUser(array $data): User
    {
        $user = new User();
        $user->setEmail($data['email']);
        $user->setName(trim($data['name']));

        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        return $user;
    }
}