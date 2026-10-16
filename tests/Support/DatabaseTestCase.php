<?php

namespace App\Tests\Support;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\LessonAccess;
use App\Entity\Theme;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Base class of the tests that use the MySQL test database.
 *
 * Each test starts from an empty database and runs inside a transaction that is rolled back
 * at the end (DAMA DoctrineTestBundle), so tests never depend on each other.
 */
abstract class DatabaseTestCase extends KernelTestCase
{
    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * Returns a service from the test container.
     *
     * @template T of object
     *
     * @param class-string<T> $id
     *
     * @return T
     */
    protected function service(string $id): object
    {
        return self::getContainer()->get($id);
    }

    /**
     * Creates and saves an account (activated by default) with the given role.
     */
    protected function createUser(string $email = 'client@test.fr', bool $verified = true, string $role = User::ROLE_CLIENT): User
    {
        $user = (new User())
            ->setEmail($email)
            ->setFirstName('Test')
            ->setLastName('User')
            ->setRoles([$role])
            ->setIsVerified($verified)
            ->setPassword('$2y$04$hashNotUsedInTheseTests......................');
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * Creates a theme with one course per entry of $lessonsPerCourse.
     * Example: createTheme('Musique', [2, 2]) -> 2 courses of 2 lessons.
     *
     * @param list<int> $lessonsPerCourse
     */
    protected function createTheme(string $name = 'Musique', array $lessonsPerCourse = [2]): Theme
    {
        $theme = (new Theme())->setName($name);
        $this->em->persist($theme);

        foreach ($lessonsPerCourse as $courseIndex => $lessonCount) {
            $course = (new Course())->setTitle(sprintf('%s - cursus %d', $name, $courseIndex + 1))->setPrice(5000);
            $theme->addCourse($course);
            $this->em->persist($course);

            for ($position = 1; $position <= $lessonCount; ++$position) {
                $lesson = (new Lesson())
                    ->setTitle(sprintf('Leçon %d', $position))
                    ->setPosition($position)
                    ->setPrice(2600)
                    ->setContent('Lorem ipsum');
                $course->addLesson($lesson);
                $this->em->persist($lesson);
            }
        }

        $this->em->flush();

        return $theme;
    }

    /**
     * Gives the user access to the lesson, as a paid purchase would.
     */
    protected function grantAccess(User $user, Lesson $lesson): void
    {
        $this->em->persist(new LessonAccess($user, $lesson));
        $this->em->flush();
    }

    /**
     * First course of a theme.
     */
    protected function firstCourse(Theme $theme): Course
    {
        return $theme->getCourses()->first();
    }

    /**
     * Lesson of the first course of a theme, by position (1-based).
     */
    protected function lesson(Theme $theme, int $position = 1, int $courseIndex = 0): Lesson
    {
        return $theme->getCourses()->get($courseIndex)->getLessons()->get($position - 1);
    }
}
