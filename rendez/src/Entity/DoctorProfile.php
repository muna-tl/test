<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\DoctorProfileRepository;

#[ORM\Entity(repositoryClass: DoctorProfileRepository::class)]
class DoctorProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: \App\Entity\User::class, cascade: ['persist'], fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Specialty::class, inversedBy: 'doctorProfiles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Specialty $specialty = null;

    #[ORM\Column(length: 20, unique: true)]
    private ?string $doctorId = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getSpecialty(): ?Specialty
    {
        return $this->specialty;
    }

    public function setSpecialty(?Specialty $specialty): static
    {
        $this->specialty = $specialty;
        return $this;
    }

    public function getDoctorId(): ?string
    {
        return $this->doctorId;
    }

    public function setDoctorId(string $doctorId): static
    {
        $this->doctorId = $doctorId;
        return $this;
    }
}
