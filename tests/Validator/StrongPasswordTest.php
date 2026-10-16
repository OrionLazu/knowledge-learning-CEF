<?php

namespace App\Tests\Validator;

use App\Validator\StrongPassword;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

/**
 * Password policy checked before a password can be saved.
 */
#[TestDox('Règles de format des mots de passe')]
class StrongPasswordTest extends TestCase
{
    #[TestDox('Un mot de passe conforme est accepté')]
    public function testValidPasswordIsAccepted(): void
    {
        $violations = Validation::createValidator()->validate('Guitare!2026ok', new StrongPassword());

        self::assertCount(0, $violations);
    }

    #[TestDox('Mot de passe refusé : $_dataName')]
    #[DataProvider('provideInvalidPasswords')]
    public function testInvalidPasswordIsRejected(string $password, string $expectedMessage): void
    {
        $messages = array_map(
            static fn ($violation) => $violation->getMessage(),
            iterator_to_array(Validation::createValidator()->validate($password, new StrongPassword())),
        );

        self::assertContains($expectedMessage, $messages);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideInvalidPasswords(): iterable
    {
        yield 'vide' => ['', 'Veuillez saisir un mot de passe.'];
        yield 'trop court (11 caractères)' => ['Guitare!20a', 'Le mot de passe doit contenir au moins 12 caractères.'];
        yield 'trop long (65 caractères)' => ['Aa1!'.str_repeat('x', 61), 'Le mot de passe ne peut pas dépasser 64 caractères.'];
        yield 'sans minuscule' => ['GUITARE!2026OK', 'Le mot de passe doit contenir au moins une lettre minuscule.'];
        yield 'sans majuscule' => ['guitare!2026ok', 'Le mot de passe doit contenir au moins une lettre majuscule.'];
        yield 'sans chiffre' => ['Guitare!Piano', 'Le mot de passe doit contenir au moins un chiffre.'];
        yield 'sans caractère spécial' => ['Guitare2026ok', 'Le mot de passe doit contenir au moins un caractère spécial.'];
        yield 'avec un espace' => ['Guitare! 2026ok', 'Le mot de passe ne doit pas contenir d\'espace.'];
    }
}
