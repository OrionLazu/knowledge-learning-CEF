<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

/**
 * Password policy of the platform, checked before a password is hashed and saved.
 *
 * A password must contain between 12 and 64 characters, with at least one lower-case letter,
 * one upper-case letter, one digit and one special character, and no spaces.
 * Grouping the rules in one compound constraint keeps the policy in a single place.
 */
#[\Attribute]
final class StrongPassword extends Compound
{
    public const MIN_LENGTH = 12;
    public const MAX_LENGTH = 64;

    /**
     * @param array<string, mixed> $options
     *
     * @return list<\Symfony\Component\Validator\Constraint>
     */
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(message: 'Veuillez saisir un mot de passe.'),
            new Assert\Length(
                min: self::MIN_LENGTH,
                max: self::MAX_LENGTH,
                minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                maxMessage: 'Le mot de passe ne peut pas dépasser {{ limit }} caractères.',
            ),
            new Assert\Regex(pattern: '/[a-z]/', message: 'Le mot de passe doit contenir au moins une lettre minuscule.'),
            new Assert\Regex(pattern: '/[A-Z]/', message: 'Le mot de passe doit contenir au moins une lettre majuscule.'),
            new Assert\Regex(pattern: '/\d/', message: 'Le mot de passe doit contenir au moins un chiffre.'),
            new Assert\Regex(pattern: '/[^a-zA-Z\d\s]/', message: 'Le mot de passe doit contenir au moins un caractère spécial.'),
            new Assert\Regex(pattern: '/\s/', match: false, message: 'Le mot de passe ne doit pas contenir d\'espace.'),
        ];
    }
}
