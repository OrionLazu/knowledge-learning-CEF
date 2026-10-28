<?php

namespace App\Form;

use App\Entity\User;
use App\Validator\StrongPassword;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Registration form of a new client.
 *
 * The password is typed twice and is not mapped to the entity: the controller hashes it
 * only once the {@see StrongPassword} policy has been checked.
 *
 * @extends AbstractType<User>
 */
class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, ['label' => 'Prénom'])
            ->add('lastName', TextType::class, ['label' => 'Nom'])
            ->add('email', EmailType::class, ['label' => 'Adresse e-mail'])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => 'Les deux mots de passe ne sont pas identiques.',
                'first_options' => [
                    'label' => 'Mot de passe',
                    'help' => sprintf('Entre %d et %d caractères, avec au moins une minuscule, une majuscule, un chiffre et un caractère spécial.', StrongPassword::MIN_LENGTH, StrongPassword::MAX_LENGTH),
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'constraints' => [new StrongPassword()],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            // CSRF protection of the form (token checked on submission).
            'csrf_protection' => true,
            'csrf_token_id' => 'registration',
        ]);
    }
}
