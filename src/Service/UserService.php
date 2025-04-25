<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    public function findUserById(int $id): ?User
    {
        return $this->entityManager->getRepository(User::class)->find($id);
    }

    public function updateUserRole(User $user, string $role): void
    {
        $user->setRoles([$role]);
        $this->entityManager->flush();
    }

    public function deleteUser(User $user): void
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function createUser(string $email, string $password, array $roles): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setRoles($roles);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * Finds users with specific roles.
     *
     * @param array $roles The roles to filter users by.
     * @return User[] The list of users with the specified roles.
     */
    public function findUsersWithRoles(array $roles): array
    {
        return $this->entityManager->getRepository(User::class)->createQueryBuilder('u')
            ->where('u.roles LIKE :roles')
            ->setParameter('roles', '%' . implode('%', $roles) . '%')
            ->getQuery()
            ->getResult();
    }
}
