<?php

namespace App\Entity;

use App\Entity\Contract\AuditableInterface;
use App\Entity\Trait\AuditableTrait;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Account of a person using the platform (client or administrator).
 *
 * The password is never stored in clear text: only its hash (bcrypt/argon2 chosen by Symfony) is saved.
 * A new account is inactive until its owner clicks the link received by e-mail ({@see $isVerified}).
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'Un compte existe déjà avec cette adresse e-mail.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface, AuditableInterface
{
    use AuditableTrait;

    /** Role automatically given to every registered user. */
    public const ROLE_CLIENT = 'ROLE_CLIENT';

    /** Role giving access to the back office. */
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'Veuillez saisir une adresse e-mail.')]
    #[Assert\Email(message: 'Cette adresse e-mail n\'est pas valide.')]
    #[Assert\Length(max: 180)]
    private ?string $email = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Veuillez saisir votre prénom.')]
    #[Assert\Length(max: 100)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Veuillez saisir votre nom.')]
    #[Assert\Length(max: 100)]
    private ?string $lastName = null;

    /**
     * @var list<string> roles stored in database (ROLE_CLIENT, ROLE_ADMIN)
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string|null hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private bool $isVerified = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        // E-mail addresses are compared case-insensitively: they are always stored in lower case.
        $this->email = mb_strtolower(trim($email));

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = trim($firstName);

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = trim($lastName);

        return $this;
    }

    /**
     * Full name displayed in the interface and in e-mails.
     */
    public function getFullName(): string
    {
        return trim(sprintf('%s %s', $this->firstName, $this->lastName));
    }

    /**
     * A visual identifier that represents this user (used by the security component).
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // Every authenticated user has at least ROLE_USER, as required by Symfony.
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = array_values(array_unique($roles));

        return $this;
    }

    public function isAdmin(): bool
    {
        return \in_array(self::ROLE_ADMIN, $this->roles, true);
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param string $password the already hashed password
     */
    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    /**
     * Ensures the session does not contain the real password hash (Symfony 7.3+).
     *
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', (string) $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // No plain password is ever stored on this object.
    }

    public function __toString(): string
    {
        return (string) $this->email;
    }
}
