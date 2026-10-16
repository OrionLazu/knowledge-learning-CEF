<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\Theme;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Demonstration data: the beta catalogue of the specification and two accounts.
 *
 * Load it with: php bin/console doctrine:fixtures:load
 */
class AppFixtures extends Fixture
{
    public const ADMIN_EMAIL = 'admin@knowledge-learning.test';
    public const ADMIN_PASSWORD = 'Admin!Knowledge2026';
    public const CLIENT_EMAIL = 'client@knowledge-learning.test';
    public const CLIENT_PASSWORD = 'Client!Knowledge2026';

    /** Free (CC0) sample video used by every lesson of the prototype. */
    public const SAMPLE_VIDEO = 'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4';

    /**
     * Beta catalogue: theme => [course title, price in cents, [[lesson title, price in cents], ...]].
     */
    private const CATALOGUE = [
        'Musique' => [
            ['Cursus d\'initiation à la guitare', 5000, [['Découverte de l\'instrument', 2600], ['Les accords et les gammes', 2600]]],
            ['Cursus d\'initiation au piano', 5000, [['Découverte de l\'instrument', 2600], ['Les accords et les gammes', 2600]]],
        ],
        'Informatique' => [
            ['Cursus d\'initiation au développement web', 6000, [['Les langages Html et CSS', 3200], ['Dynamiser votre site avec Javascript', 3200]]],
        ],
        'Jardinage' => [
            ['Cursus d\'initiation au jardinage', 3000, [['Les outils du jardinier', 1600], ['Jardiner avec la lune', 1600]]],
        ],
        'Cuisine' => [
            ['Cursus d\'initiation à la cuisine', 4400, [['Les modes de cuisson', 2300], ['Les saveurs', 2300]]],
            ['Cursus d\'initiation à l\'art du dressage culinaire', 4800, [['Mettre en œuvre le style dans l\'assiette', 2600], ['Harmoniser un repas à quatre plats', 2600]]],
        ],
    ];

    private const LOREM_IPSUM = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed non risus. Suspendisse lectus tortor, dignissim sit amet, adipiscing nec, ultricies sed, dolor. Cras elementum ultrices diam. Maecenas ligula massa, varius a, semper congue, euismod non, mi.\n\nProin porttitor, orci nec nonummy molestie, enim est eleifend mi, non fermentum diam nisl sit amet erat. Duis semper. Duis arcu massa, scelerisque vitae, consequat in, pretium a, enim. Pellentesque congue. Ut in risus volutpat libero pharetra tempor.\n\nCras vestibulum bibendum augue. Praesent egestas leo in pede. Praesent blandit odio eu enim. Pellentesque sed dui ut augue blandit sodales. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae.";

    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = $this->createUser(self::ADMIN_EMAIL, self::ADMIN_PASSWORD, 'Alice', 'Admin', [User::ROLE_ADMIN]);
        $manager->persist($admin);
        $manager->persist($this->createUser(self::CLIENT_EMAIL, self::CLIENT_PASSWORD, 'Jean', 'Client', [User::ROLE_CLIENT]));

        foreach (self::CATALOGUE as $themeName => $courses) {
            $theme = (new Theme())->setName($themeName)->setCreatedBy($admin);
            $manager->persist($theme);

            foreach ($courses as [$courseTitle, $coursePrice, $lessons]) {
                $course = (new Course())->setTitle($courseTitle)->setPrice($coursePrice)->setCreatedBy($admin);
                $theme->addCourse($course);
                $manager->persist($course);

                foreach ($lessons as $index => [$lessonTitle, $lessonPrice]) {
                    $lesson = (new Lesson())
                        ->setTitle($lessonTitle)
                        ->setPosition($index + 1)
                        ->setPrice($lessonPrice)
                        ->setContent(self::LOREM_IPSUM)
                        ->setVideoUrl(self::SAMPLE_VIDEO)
                        ->setCreatedBy($admin);
                    $course->addLesson($lesson);
                    $manager->persist($lesson);
                }
            }
        }

        $manager->flush();
    }

    /**
     * Builds an already activated account.
     *
     * @param list<string> $roles
     */
    private function createUser(string $email, string $password, string $firstName, string $lastName, array $roles): User
    {
        $user = (new User())
            ->setEmail($email)
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setRoles($roles)
            ->setIsVerified(true);

        return $user->setPassword($this->passwordHasher->hashPassword($user, $password));
    }
}
