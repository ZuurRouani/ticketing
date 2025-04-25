<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\Comment;
use App\Entity\Attachment;
use App\Service\TicketService;
use App\Service\AttachmentService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

abstract class BaseTicketController extends AbstractController
{
    protected TicketService $ticketService;
    protected AttachmentService $attachmentService;

    public function __construct(TicketService $ticketService, AttachmentService $attachmentService)
    {
        $this->ticketService = $ticketService;
        $this->attachmentService = $attachmentService;
    }

    protected function handleAddAttachment(Ticket $ticket, Request $request, string $uploadsDirectory): RedirectResponse
    {
        $uploadedFile = $request->files->get('attachment');
        if ($uploadedFile) {
            $this->attachmentService->addAttachment($ticket, $uploadedFile, $uploadsDirectory);
            $this->addFlash('success', 'Attachment added successfully.');
        } else {
            $this->addFlash('error', 'Failed to upload the file.');
        }

        return $this->redirectToRoute($this->getViewTicketRoute(), ['id' => $ticket->getId()]);
    }

    protected function handleDeleteAttachment(Attachment $attachment, string $uploadsDirectory): RedirectResponse
    {
        $this->attachmentService->deleteAttachment($attachment, $uploadsDirectory);
        $this->addFlash('success', 'Attachment deleted successfully.');

        return $this->redirectToRoute($this->getViewTicketRoute(), ['id' => $attachment->getTicket()->getId()]);
    }

    protected function handleAddComment(Ticket $ticket, Comment $comment): void
    {
        $this->ticketService->addComment($ticket, $comment, $this->getUser());
        $this->addFlash('success', 'Comment added successfully.');
    }

    abstract protected function getViewTicketRoute(): string;
}
