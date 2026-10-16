<?php

namespace App\Tests\Security;

use App\Entity\Lesson;
use App\Entity\User;
use App\Repository\LessonAccessRepository;
use App\Security\Voter\LessonVoter;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Access rule of the lesson pages.
 */
#[TestDox('Droit d\'accès à la page d\'une leçon')]
class LessonVoterTest extends TestCase
{
    #[TestDox('Un client qui a acheté la leçon y a accès')]
    public function testClientWithAccessIsGranted(): void
    {
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($this->user(User::ROLE_CLIENT), hasAccess: true));
    }

    #[TestDox('Un client qui n\'a pas acheté la leçon n\'y a pas accès')]
    public function testClientWithoutAccessIsDenied(): void
    {
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote($this->user(User::ROLE_CLIENT), hasAccess: false));
    }

    #[TestDox('Un administrateur a accès à toutes les leçons')]
    public function testAdminIsAlwaysGranted(): void
    {
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($this->user(User::ROLE_ADMIN), hasAccess: false));
    }

    #[TestDox('Un visiteur non connecté n\'a pas accès')]
    public function testAnonymousIsDenied(): void
    {
        $voter = new LessonVoter($this->createStub(LessonAccessRepository::class));

        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote(new NullToken(), new Lesson(), [LessonVoter::VIEW]));
    }

    private function vote(User $user, bool $hasAccess): int
    {
        $repository = $this->createStub(LessonAccessRepository::class);
        $repository->method('hasAccess')->willReturn($hasAccess);
        $voter = new LessonVoter($repository);

        return $voter->vote(new UsernamePasswordToken($user, 'main', $user->getRoles()), new Lesson(), [LessonVoter::VIEW]);
    }

    private function user(string $role): User
    {
        return (new User())->setEmail('u@test.fr')->setRoles([$role]);
    }
}
