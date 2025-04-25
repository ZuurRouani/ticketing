<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\Comment;
use App\Entity\Attachment;
use App\Form\CommentFormType;
use App\Service\TicketManager;
use App\Service\TicketService;
use App\Service\AttachmentService;
use App\Traits\ControllerHelperTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for managing support-related actions.
 */
#[Route('/support', name: 'support_')]
class SupportController extends BaseTicketController
{
    use ControllerHelperTrait;

    private TicketManager $ticketManager;

    /**
     * Constructor.
     *
     * @param TicketService $ticketService The service for managing tickets.
     * @param AttachmentService $attachmentService The service for managing attachments.
     * @param TicketManager $ticketManager The manager for handling ticket operations.
     */
    public function __construct(
        TicketService $ticketService,
        AttachmentService $attachmentService,
        TicketManager $ticketManager
    ) {
        parent::__construct($ticketService, $attachmentService);
        $this->ticketManager = $ticketManager;
    }

    /**
     * Displays the list of tickets assigned to the current support user.
     *
     * @return Response The HTTP response containing the list of tickets.
     */
    #[Route('/tickets', name: 'assigned_tickets')]
    public function manageTickets(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPPORT');

        $tickets = $this->ticketService->findTicketsByAssignedUser($this->getUser());

        return $this->render('support/tickets.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    /**
     * Adds an attachment to a ticket.
     *
     * @param int $id The ID of the ticket.
     * @param Request $request The HTTP request.
     * @return RedirectResponse A redirect response to the ticket view.
     */
    #[Route('/ticket/{id}/add-attachment', name: 'add_attachment', methods: ['POST'])]
    public function addAttachment(int $id, Request $request): RedirectResponse
    {
        $ticket = $this->ticketService->findTicketById($id);
        if (!$ticket) {
            return $this->redirectWithError('support_tickets', 'Ticket not found.');
        }

        return $this->handleAddAttachment($ticket, $request, $this->getParameter('uploads_directory'));
    }

    /**
     * Deletes an attachment from a ticket.
     *
     * @param int $id The ID of the attachment.
     * @return RedirectResponse A redirect response to the ticket view.
     */
    #[Route('/attachment/delete/{id}', name: 'delete_attachment', methods: ['POST', 'DELETE'])]
    public function deleteAttachment(int $id): RedirectResponse
    {
        $attachment = $this->attachmentService->findAttachmentById($id);
        if (!$attachment) {
            return $this->redirectWithError('support_tickets', 'Attachment not found.');
        }

        return $this->handleDeleteAttachment($attachment, $this->getParameter('uploads_directory'));
    }

    /**
     * Deletes a ticket.
     *
     * @param int $id The ID of the ticket.
     * @return RedirectResponse A redirect response after deleting the ticket.
     */
    #[Route('/ticket/delete/{id}', name: 'delete_ticket', methods: ['POST'])]
    public function deleteTicket(int $id): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPPORT');

        $ticket = $this->ticketService->findTicketById($id);
        if (!$ticket) {
            return $this->redirectWithError('support_tickets', 'Ticket not found.');
        }

        $this->ticketService->deleteTicket($ticket);

        return $this->redirectWithSuccess('support_tickets', 'Ticket deleted successfully.');
    }

    /**
     * Deletes a comment from a ticket.
     *
     * @param int $id The ID of the comment.
     * @return RedirectResponse A redirect response after deleting the comment.
     */
    #[Route('/comment/delete/{id}', name: 'delete_comment', methods: ['POST'])]
    public function deleteComment(int $id): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPPORT');

        $comment = $this->ticketService->findCommentById($id);
        if (!$comment) {
            return $this->redirectWithError('support_tickets', 'Comment not found.');
        }

        $ticketId = $comment->getTicket()->getId();
        $this->ticketService->deleteComment($comment);

        return $this->redirectWithSuccess('support_view_ticket', 'Comment deleted successfully.', ['id' => $ticketId]);
    }

    /**
     * Displays the details of a ticket and its comments.
     *
     * @param int $id The ID of the ticket.
     * @param Request $request The HTTP request.
     * @return Response The HTTP response containing the ticket details.
     */
    #[Route('/ticket/{id}', name: 'view_ticket')]
    public function viewTicket(int $id, Request $request): Response
    {
        $ticket = $this->ticketService->findTicketById($id);
        if (!$ticket) {
            return $this->redirectWithError('support_tickets', 'Ticket not found.');
        }

        $comments = $this->ticketService->findCommentsByTicket($ticket);
        $tickethistories = $this->ticketService->findTicketHistoriesByTicket($ticket);

        $comment = new Comment();
        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleAddComment($ticket, $comment);
            return $this->redirectWithSuccess('support_view_ticket', 'Comment added successfully.', ['id' => $id]);
        }

        return $this->render('support/view_ticket.html.twig', [
            'ticket' => $ticket,
            'comments' => $comments,
            'tickethistories' => $tickethistories,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Changes the status of a ticket and logs the history.
     *
     * @param int $id The ID of the ticket.
     * @param Request $request The HTTP request.
     * @return RedirectResponse The HTTP redirect response after changing the status.
     */
    #[Route('/ticket/{id}/change-status', name: 'change_status', methods: ['POST'])]
    public function changeStatus(int $id, Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPPORT');

        $ticket = $this->ticketService->findTicketById($id);
        if (!$ticket) {
            return $this->redirectWithError('support_tickets', 'Ticket not found.');
        }

        $newStatus = $request->request->get('status');
        if (!$newStatus || $newStatus === $ticket->getStatus()) {
            return $this->redirectWithError('support_view_ticket', 'Invalid or unchanged status.', ['id' => $id]);
        }

        $this->ticketManager->changeTicketStatus($ticket, $newStatus, $this->getUser());

        return $this->redirectWithSuccess('support_view_ticket', 'Ticket status updated successfully.', ['id' => $id]);
    }

    /**
     * Returns the route name for viewing a ticket.
     *
     * @return string The route name.
     */
    protected function getViewTicketRoute(): string
    {
        return 'support_view_ticket';
    }
}
