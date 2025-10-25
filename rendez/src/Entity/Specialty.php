<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\SpecialtyRepository;

#[ORM\Entity(repositoryClass: SpecialtyRepository::class)]
class Specialty
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $name = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'specialty', targetEntity: DoctorProfile::class)]
    private Collection $doctorProfiles;

    public function __construct()
    {
        $this->doctorProfiles = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
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

    /**
     * @return Collection<int, DoctorProfile>
     */
    public function getDoctorProfiles(): Collection
    {
        return $this->doctorProfiles;
    }

    public function addDoctorProfile(DoctorProfile $doctorProfile): static
    {
        if (!$this->doctorProfiles->contains($doctorProfile)) {
            $this->doctorProfiles->add($doctorProfile);
            $doctorProfile->setSpecialtyEntity($this);
        }

        return $this;
    }

    public function removeDoctorProfile(DoctorProfile $doctorProfile): static
    {
        if ($this->doctorProfiles->removeElement($doctorProfile)) {
            // set the owning side to null (unless already changed)
            if ($doctorProfile->getSpecialtyEntity() === $this) {
                $doctorProfile->setSpecialtyEntity(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}