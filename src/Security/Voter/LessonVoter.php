<?php

namespace App\Security\Voter;

use App\Entity\Lesson;
use App\Entity\User;
use App\Repository\LessonAccessRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Decides who can open the page of a lesson.
 *
 * - an administrator can open every lesson (to check the content);
 * - a client can open a lesson only after buying it, alone or with its course.
 *
 * Controllers and templates only ask "is_granted('LESSON_VIEW', lesson)": the rule lives here only.
 *
 * @extends Voter<string, Lesson>
 */
final class LessonVoter extends Voter
{
    public const VIEW = 'LESSON_VIEW';

    public function __construct(private readonly LessonAccessRepository $lessonAccessRepository)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::VIEW === $attribute && $subject instanceof Lesson;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $this->lessonAccessRepository->hasAccess($user, $subject);
    }
}
