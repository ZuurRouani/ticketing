<?php

namespace App\Service;

use App\Entity\Ticket;
use App\Entity\Comment;
use App\Entity\Attachment;
use App\Entity\User;
use App\Entity\TicketHistory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class TicketService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function addComment(Ticket $ticket, Comment $comment, $user): void
    {
        $comment->setTicket($ticket);
        $comment->setOwner($user);

        $this->entityManager->persist($comment);
        $this->entityManager->flush();
    }

    public function findTicketById(int $id): ?Ticket
    {
        return $this->entityManager->getRepository(Ticket::class)->find($id);
    }

    public function deleteTicket(Ticket $ticket): void
    {
        $this->entityManager->remove($ticket);
        $this->entityManager->flush();
    }

    public function findTicketsByAssignedUser(User $user): array
    {
        return $this->entityManager->getRepository(Ticket::class)->findBy(['assigned' => $user]);
    }

    public function findCommentsByTicket(Ticket $ticket): array
    {
        return $this->entityManager->getRepository(Comment::class)->findBy(['ticket' => $ticket]);
    }

    public function findAllTickets(): array
    {
        return $this->entityManager->getRepository(Ticket::class)->findAll();
    }

    public function findCommentById(int $id): ?Comment
    {
        return $this->entityManager->getRepository(Comment::class)->find($id);
    }

    public function deleteComment(Comment $comment): void
    {
        $this->entityManager->remove($comment);
        $this->entityManager->flush();
    }

    public function saveTicket(Ticket $ticket): void
    {
        $this->entityManager->persist($ticket);
        $this->entityManager->flush();
    }

    public function logTicketHistory(Ticket $ticket, User $changedBy, string $action, string $comment): void
    {
        $history = new TicketHistory();
        $history->setTicket($ticket)
            ->setChangedBy($changedBy)
            ->setPreviousStatus($ticket->getStatus())
            ->setNewStatus($ticket->getStatus())
            ->setChangedAt(new \DateTimeImmutable())
            ->setComment($comment);

        $this->entityManager->persist($history);
        $this->entityManager->flush();
    }

    public function findTicketHistoriesByTicket(Ticket $ticket): array
    {
        return $this->entityManager->getRepository(TicketHistory::class)->findBy(['ticket' => $ticket], ['changedAt' => 'DESC']);
    }
}
