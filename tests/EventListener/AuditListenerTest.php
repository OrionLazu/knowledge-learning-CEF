<?php

namespace App\Tests\EventListener;

use App\Entity\Theme;
use App\Tests\Support\DatabaseTestCase;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Automatic filling of created_at, updated_at, created_by and updated_by.
 */
#[TestDox('Colonnes d\'audit (created_at, updated_at, created_by, updated_by)')]
class AuditListenerTest extends DatabaseTestCase
{
    #[TestDox('À la création, les dates et l\'auteur (utilisateur connecté) sont renseignés')]
    public function testAuditColumnsAreFilledOnInsert(): void
    {
        $admin = $this->createUser('admin@test.fr');
        $this->logIn($admin);

        $theme = (new Theme())->setName('Photographie');
        $this->em->persist($theme);
        $this->em->flush();

        self::assertNotNull($theme->getCreatedAt());
        self::assertEquals($theme->getCreatedAt(), $theme->getUpdatedAt());
        self::assertSame($admin, $theme->getCreatedBy());
        self::assertSame($admin, $theme->getUpdatedBy());
    }

    #[TestDox('À la modification, seuls updated_at et updated_by changent')]
    public function testOnlyUpdateColumnsChangeOnUpdate(): void
    {
        $author = $this->createUser('author@test.fr');
        $editor = $this->createUser('editor@test.fr');
        $this->logIn($author);
        $theme = (new Theme())->setName('Photographie');
        $this->em->persist($theme);
        $this->em->flush();
        $createdAt = $theme->getCreatedAt();

        $this->logIn($editor);
        $theme->setName('Photographie numérique');
        $this->em->flush();
        $this->em->clear();

        $reloaded = $this->em->find(Theme::class, $theme->getId());
        self::assertSame($author->getId(), $reloaded->getCreatedBy()?->getId());
        self::assertSame($editor->getId(), $reloaded->getUpdatedBy()?->getId());
        self::assertEquals($createdAt->format('Y-m-d H:i:s'), $reloaded->getCreatedAt()->format('Y-m-d H:i:s'));
        // MySQL DATETIME columns keep seconds only: the comparison ignores microseconds.
        self::assertGreaterThanOrEqual($createdAt->format('Y-m-d H:i:s'), $reloaded->getUpdatedAt()->format('Y-m-d H:i:s'));
    }

    #[TestDox('Sans utilisateur connecté (inscription publique), les auteurs restent vides')]
    public function testAuthorsAreNullWithoutLoggedInUser(): void
    {
        $user = $this->createUser('visitor@test.fr');

        self::assertNotNull($user->getCreatedAt());
        self::assertNull($user->getCreatedBy());
        self::assertNull($user->getUpdatedBy());
    }

    private function logIn(\App\Entity\User $user): void
    {
        $this->service(TokenStorageInterface::class)->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));
    }
}
