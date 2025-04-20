<?php

namespace App\Entity;

use App\Repository\TicketHistoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TicketHistoryRepository::class)]
class TicketHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $ticket_id = null;

    #[ORM\Column]
    private ?int $changed_by_id = null;

    #[ORM\Column(length: 50)]
    private ?string $previous_status = null;

    #[ORM\Column(length: 50)]
    private ?string $new_status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $changed_at = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $comment = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTicketId(): ?int
    {
        return $this->ticket_id;
    }

    public function setTicketId(int $ticket_id): static
    {
        $this->ticket_id = $ticket_id;

        return $this;
    }

    public function getChangedById(): ?int
    {
        return $this->changed_by_id;
    }

    public function setChangedById(int $changed_by_id): static
    {
        $this->changed_by_id = $changed_by_id;

        return $this;
    }

    public function getPreviousStatus(): ?string
    {
        return $this->previous_status;
    }

    public function setPreviousStatus(string $previous_status): static
    {
        $this->previous_status = $previous_status;

        return $this;
    }

    public function getNewStatus(): ?string
    {
        return $this->new_status;
    }

    public function setNewStatus(string $new_status): static
    {
        $this->new_status = $new_status;

        return $this;
    }

    public function getChangedAt(): ?\DateTimeImmutable
    {
        return $this->changed_at;
    }

    public function setChangedAt(\DateTimeImmutable $changed_at): static
    {
        $this->changed_at = $changed_at;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }
}
