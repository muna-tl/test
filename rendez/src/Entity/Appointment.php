<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\AppointmentRepository;

#[ORM\Entity(repositoryClass: AppointmentRepository::class)]
class Appointment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: \App\Entity\User::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $patient = null;

    #[ORM\ManyToOne(targetEntity: \App\Entity\DoctorProfile::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    private ?DoctorProfile $doctor = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $startAt = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $endAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(length: 50, options: ['default' => 'confirmed'])]
    private ?string $status = 'confirmed';

    #[ORM\Column(length: 20, unique: true)]
    private ?string $confirmationCode = null;

    // Constantes pour les statuts
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_DONE = 'done';
    public const STATUS_CANCELED = 'canceled';

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $appointmentDate = null;

    #[ORM\Column(type: 'time')]
    private ?\DateTimeInterface $appointmentTime = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPatient(): ?User
    {
        return $this->patient;
    }

    public function setPatient(User $patient): static
    {
        $this->patient = $patient;
        return $this;
    }

    public function getDoctor(): ?DoctorProfile
    {
        return $this->doctor;
    }

    public function setDoctor(DoctorProfile $doctor): static
    {
        $this->doctor = $doctor;
        return $this;
    }

    public function getStartAt(): ?\DateTimeInterface
    {
        return $this->startAt;
    }

    public function setStartAt(\DateTimeInterface $startAt): static
    {
        $this->startAt = $startAt;
        return $this;
    }

    public function getEndAt(): ?\DateTimeInterface
    {
        return $this->endAt;
    }

    public function setEndAt(\DateTimeInterface $endAt): static
    {
        $this->endAt = $endAt;
        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getAppointmentDate(): ?\DateTimeInterface
    {
        return $this->appointmentDate;
    }

    public function setAppointmentDate(\DateTimeInterface $appointmentDate): static
    {
        $this->appointmentDate = $appointmentDate;
        return $this;
    }

    public function getAppointmentTime(): ?\DateTimeInterface
    {
        return $this->appointmentTime;
    }

    public function setAppointmentTime(\DateTimeInterface $appointmentTime): static
    {
        $this->appointmentTime = $appointmentTime;
        return $this;
    }

    public function getConfirmationCode(): ?string
    {
        return $this->confirmationCode;
    }

    public function setConfirmationCode(string $confirmationCode): static
    {
        $this->confirmationCode = $confirmationCode;
        return $this;
    }
}
