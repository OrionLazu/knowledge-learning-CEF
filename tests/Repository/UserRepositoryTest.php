<?php

namespace App\Tests\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\Support\DatabaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Data access component of the user accounts: functional and security tests.
 */
#[TestDox('Repository des utilisateurs')]
class UserRepositoryTest extends DatabaseTestCase
{
    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->service(UserRepository::class);
    }

    #[TestDox('Retrouve un compte par son e-mail, sans tenir compte des majuscules')]
    public function testFindOneByEmailIgnoresCase(): void
    {
        $user = $this->createUser('marie@test.fr');

        self::assertSame($user->getId(), $this->repository->findOneByEmail('  Marie@TEST.fr ')?->getId());
        self::assertNull($this->repository->findOneByEmail('inconnu@test.fr'));
    }

    #[TestDox('Sécurité : une tentative d\'injection SQL dans l\'e-mail ($_dataName) ne renvoie aucun compte')]
    #[DataProvider('provideSqlInjections')]
    public function testFindOneByEmailResistsSqlInjection(string $payload): void
    {
        $this->createUser('marie@test.fr');

        self::assertNull($this->repository->findOneByEmail($payload));
        // The table is untouched: the payload was treated as a plain value, never executed.
        self::assertSame(1, $this->repository->count([]));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideSqlInjections(): iterable
    {
        yield 'condition toujours vraie' => ["' OR '1'='1"];
        yield 'commentaire SQL' => ["marie@test.fr' -- "];
        yield 'requête empilée' => ["x'; DROP TABLE `user`; --"];
        yield 'union' => ["' UNION SELECT id FROM `user` -- "];
    }

    #[TestDox('Le mot de passe enregistré par upgradePassword est bien remplacé')]
    public function testUpgradePasswordStoresTheNewHash(): void
    {
        $user = $this->createUser();

        $this->repository->upgradePassword($user, '$2y$13$newHash');
        $this->em->clear();

        self::assertSame('$2y$13$newHash', $this->repository->find($user->getId())?->getPassword());
    }

    #[TestDox('Sécurité : l\'adresse e-mail est unique en base de données')]
    public function testEmailIsUniqueInDatabase(): void
    {
        $this->createUser('marie@test.fr');

        $this->expectException(\Doctrine\DBAL\Exception\UniqueConstraintViolationException::class);
        $this->createUser('MARIE@test.fr');
    }

    #[TestDox('Les rôles sont enregistrés et ROLE_USER est toujours ajouté')]
    public function testRolesArePersisted(): void
    {
        $user = $this->createUser('admin@test.fr', true, User::ROLE_ADMIN);
        $this->em->clear();

        $reloaded = $this->repository->find($user->getId());
        self::assertContains(User::ROLE_ADMIN, $reloaded->getRoles());
        self::assertContains('ROLE_USER', $reloaded->getRoles());
        self::assertTrue($reloaded->isAdmin());
    }
}
