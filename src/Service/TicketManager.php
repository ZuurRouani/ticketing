<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Ticket;

class TicketManager
{
    private TicketService $ticketService;

    public function __construct(TicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }

    public function assignTicket(Ticket $ticket, User $assignedUser, ?User $currentUser): void
    {
        $previousAssigned = $ticket->getAssigned();
        $ticket->setAssigned($assignedUser);
        $this->ticketService->saveTicket($ticket);

        // Log history
        $this->ticketService->logTicketHistory(
            $ticket,
            $currentUser,
            'Assignment',
            sprintf(
                'Ticket assigned from %s to %s',
                $previousAssigned ? $previousAssigned->getEmail() : 'Unassigned',
                $assignedUser->getEmail()
            )
        );
    }

    public function changeTicketStatus(Ticket $ticket, string $newStatus, ?User $currentUser): void
    {
        $previousStatus = $ticket->getStatus();
        $ticket->setStatus($newStatus);
        $this->ticketService->saveTicket($ticket);

        // Log history
        $this->ticketService->logTicketHistory(
            $ticket,
            $currentUser,
            'Status Change',
            sprintf('Status changed from %s to %s', $previousStatus, $newStatus)
        );
    }
}
