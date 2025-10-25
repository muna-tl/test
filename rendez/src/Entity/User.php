<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'Un compte avec cette adresse email existe déjà.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'L\'email ne peut pas être vide.')]
    #[Assert\Email(message: 'Veuillez entrer une adresse email valide.')]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le prénom ne peut pas être vide.')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Le prénom doit faire au moins {{ limit }} caractères.')]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom ne peut pas être vide.')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Le nom doit faire au moins {{ limit }} caractères.')]
    private ?string $lastName = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Regex(pattern: '/^\+?[0-9\s\-\(\)]{8,15}$/', message: 'Veuillez entrer un numéro de téléphone valide.')]
    private ?string $phone = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank(message: 'Le sexe ne peut pas être vide.')]
    #[Assert\Choice(choices: ['male', 'female'], message: 'Veuillez choisir un sexe valide.')]
    private ?string $gender = null;

    // Constantes pour les rôles
    public const ROLE_PATIENT = 'ROLE_PATIENT';
    public const ROLE_DOCTOR = 'ROLE_DOCTOR';
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->roles = [self::ROLE_PATIENT]; // Rôle par défaut
    }

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
        $this->email = $email;
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
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

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(string $gender): static
    {
        $this->gender = $gender;
        return $this;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles());
    }

    public function getRoleLabel(): string
    {
        if ($this->hasRole(self::ROLE_ADMIN)) {
            return 'Administrateur';
        }
        if ($this->hasRole(self::ROLE_DOCTOR)) {
            return 'Docteur';
        }
        return 'Patient';
    }

    /**
     * Récupérer le profil docteur associé à cet utilisateur (si c'est un docteur)
     */
    public function getDoctorProfile(): ?DoctorProfile
    {
        if (!$this->hasRole(self::ROLE_DOCTOR)) {
            return null;
        }
        
        // Cette méthode sera complétée par Doctrine automatiquement
        // via la relation OneToOne dans DoctorProfile
        return null;
    }
}

