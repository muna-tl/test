<?php

namespace App\Entity;

use App\Repository\MedicalRecordRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MedicalRecordRepository::class)]
class MedicalRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $patient = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $bloodType = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $allergies = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $chronicDiseases = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $currentMedications = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $emergencyContact = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * @var Collection<int, MedicalDocument>
     */
    #[ORM\OneToMany(targetEntity: MedicalDocument::class, mappedBy: 'medicalRecord', cascade: ['persist', 'remove'])]
    private Collection $documents;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPatient(): ?User
    {
        return $this->patient;
    }

    public function setPatient(?User $patient): static
    {
        $this->patient = $patient;

        return $this;
    }

    public function getBloodType(): ?string
    {
        return $this->bloodType;
    }

    public function setBloodType(?string $bloodType): static
    {
        $this->bloodType = $bloodType;

        return $this;
    }

    public function getAllergies(): ?string
    {
        return $this->allergies;
    }

    public function setAllergies(?string $allergies): static
    {
        $this->allergies = $allergies;

        return $this;
    }

    public function getChronicDiseases(): ?string
    {
        return $this->chronicDiseases;
    }

    public function setChronicDiseases(?string $chronicDiseases): static
    {
        $this->chronicDiseases = $chronicDiseases;

        return $this;
    }

    public function getCurrentMedications(): ?string
    {
        return $this->currentMedications;
    }

    public function setCurrentMedications(?string $currentMedications): static
    {
        $this->currentMedications = $currentMedications;

        return $this;
    }

    public function getEmergencyContact(): ?string
    {
        return $this->emergencyContact;
    }

    public function setEmergencyContact(?string $emergencyContact): static
    {
        $this->emergencyContact = $emergencyContact;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, MedicalDocument>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(MedicalDocument $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setMedicalRecord($this);
        }

        return $this;
    }

    public function removeDocument(MedicalDocument $document): static
    {
        if ($this->documents->removeElement($document)) {
            // set the owning side to null (unless already changed)
            if ($document->getMedicalRecord() === $this) {
                $document->setMedicalRecord(null);
            }
        }

        return $this;
    }

    /**
     * Get documents by type
     */
    public function getDocumentsByType(string $type): Collection
    {
        return $this->documents->filter(function(MedicalDocument $document) use ($type) {
            return $document->getType() === $type;
        });
    }
}
