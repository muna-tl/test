<?php

namespace App\Entity;

use App\Repository\MedicalDocumentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MedicalDocumentRepository::class)]
class MedicalDocument
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MedicalRecord::class, inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MedicalRecord $medicalRecord = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $filePath = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $uploadedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $uploadedBy = null;

    public function __construct()
    {
        $this->uploadedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMedicalRecord(): ?MedicalRecord
    {
        return $this->medicalRecord;
    }

    public function setMedicalRecord(?MedicalRecord $medicalRecord): static
    {
        $this->medicalRecord = $medicalRecord;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        // Validate type: analyse, radio, observation
        if (!in_array($type, ['analyse', 'radio', 'observation'])) {
            throw new \InvalidArgumentException('Type must be: analyse, radio, or observation');
        }
        
        $this->type = $type;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

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

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): static
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getUploadedAt(): ?\DateTimeInterface
    {
        return $this->uploadedAt;
    }

    public function setUploadedAt(\DateTimeInterface $uploadedAt): static
    {
        $this->uploadedAt = $uploadedAt;

        return $this;
    }

    public function getUploadedBy(): ?User
    {
        return $this->uploadedBy;
    }

    public function setUploadedBy(?User $uploadedBy): static
    {
        $this->uploadedBy = $uploadedBy;

        return $this;
    }

    /**
     * Get the file extension
     */
    public function getFileExtension(): ?string
    {
        return pathinfo($this->filePath, PATHINFO_EXTENSION);
    }

    /**
     * Get icon class based on file extension
     */
    public function getIconClass(): string
    {
        $extension = strtolower($this->getFileExtension() ?? '');
        
        return match ($extension) {
            'pdf' => 'fa-file-pdf text-danger',
            'jpg', 'jpeg', 'png', 'gif' => 'fa-file-image text-primary',
            'doc', 'docx' => 'fa-file-word text-primary',
            'xls', 'xlsx' => 'fa-file-excel text-success',
            default => 'fa-file text-secondary',
        };
    }
}
