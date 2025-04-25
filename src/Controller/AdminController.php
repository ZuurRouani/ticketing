<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Ticket;
use App\Entity\Comment;
use App\Entity\Category;
use App\Entity\Attachment;
use App\Service\UserService;
use App\Form\CommentFormType;
use App\Form\CategoryFormType;
use App\Service\TicketManager;
use App\Service\TicketService;
use App\Service\CategoryService;
use App\Service\AttachmentService;
use App\Repository\UserRepository;
use App\Traits\ControllerHelperTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for managing admin-related actions.
 */
#[Route('/admin', name: 'admin_')]
class AdminController extends BaseTicketController
{
    use ControllerHelperTrait;

    private CategoryService $categoryService;
    private UserService $userService;
    private TicketManager $ticketManager;
    protected AttachmentService $attachmentService;

    public function __construct(
        TicketService $ticketService,
        CategoryService $categoryService,
        UserService $userService,
        TicketManager $ticketManager,
        AttachmentService $attachmentService
    ) {
        parent::__construct($ticketService, $attachmentService);
        $this->categoryService = $categoryService;
        $this->userService = $userService;
        $this->ticketManager = $ticketManager;
        $this->attachmentService = $attachmentService;
    }

    #[Route('/categories', name: 'categories')]
    public function manageCategories(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $categories = $this->categoryService->getAllCategories();
        $form = $this->createForm(CategoryFormType::class, new Category());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->categoryService->createCategory($form->getData());
            return $this->redirectWithSuccess('admin_categories', 'Category created successfully.');
        }

        return $this->render('admin/categories.html.twig', [
            'categories' => $categories,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/category/update/{id}', name: 'update_category', methods: ['POST'])]
    public function updateCategory(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $category = $this->categoryService->findCategoryById($id);
        if (!$category) {
            return $this->redirectWithError('admin_categories', 'Category not found.');
        }

        $name = $request->request->get('name');
        if ($name) {
            $this->categoryService->updateCategory($category, $name);
            return $this->redirectWithSuccess('admin_categories', 'Category updated successfully.');
        }

        return $this->redirectWithError('admin_categories', 'Category name cannot be empty.');
    }

    #[Route('/category/delete/{id}', name: 'delete_category', methods: ['POST'])]
    public function deleteCategory(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $category = $this->categoryService->findCategoryById($id);
        if (!$category) {
            return $this->redirectWithError('admin_categories', 'Category not found.');
        }

        if ($category->getTickets()->count() > 0) {
            return $this->redirectWithError('admin_categories', 'Cannot delete a category linked to tickets.');
        }

        $this->categoryService->deleteCategory($category);
        return $this->redirectWithSuccess('admin_categories', 'Category deleted successfully.');
    }

    #[Route('/members', name: 'members')]
    public function manageMembers(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $members = $userRepository->findMembersWithAdminOrSupport();

        return $this->render('admin/members.html.twig', [
            'members' => $members,
        ]);
    }

    #[Route('/users', name: 'users')]
    public function manageUsers(UserRepository $userRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SUPPORT')) {
            throw $this->createAccessDeniedException();
        }

        $users = $userRepository->findUsersWithRoleUser();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/tickets/delete/{id}', name: 'delete_ticket')]
    public function deleteTicket(int $id): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $ticket = $this->ticketService->findTicketById($id);
        if (!$ticket) {
            return $this->redirectWithError('admin_tickets', 'Ticket not found.');
        }

        $this->ticketService->deleteTicket($ticket);

        return $this->redirectWithSuccess('admin_tickets', 'Ticket deleted successfully.');
    }

    #[Route('/member/add', name: 'add_member', methods: ['GET', 'POST'])]
    public function addMember(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($request->isMethod('POST')) {
            return $this->handleMemberCreation($request);
        }

        return $this->render('admin/add_member.html.twig', [
            'email' => '',
            'password' => '',
            'role' => '',
        ]);
    }

    #[Route('/member/{id<\d+>}', name: 'view_member', methods: ['GET', 'POST'])]
    public function viewMember(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $member = $this->userService->findUserById($id);
        if (!$member) {
            return $this->redirectWithError('admin_members', 'Member not found.');
        }

        if ($request->isMethod('POST')) {
            $role = $request->request->get('role');
            if (in_array($role, ['ROLE_ADMIN', 'ROLE_SUPPORT'])) {
                $this->userService->updateUserRole($member, $role);
                return $this->redirectWithSuccess('admin_view_member', 'Member updated successfully.');
            } else {
                return $this->redirectWithError('admin_view_member', 'Invalid role selected.');
            }
        }

        $tickets = $this->ticketService->findTicketsByAssignedUser($member);

        return $this->render('admin/view_member.html.twig', [
            'member' => $member,
            'tickets' => $tickets,
        ]);
    }

    #[Route('/member/delete/{id}', name: 'delete_member', methods: ['POST'])]
    public function deleteMember(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $member = $this->userService->findUserById($id);
        if (!$member) {
            return $this->redirectWithError('admin_members', 'Member not found.');
        }

        if ($this->isAdminDeletionRestricted($member)) {
            return $this->redirectWithError('admin_members', 'Only super admins can delete admins.');
        }

        $this->userService->deleteUser($member);
        return $this->redirectWithSuccess('admin_members', 'Member deleted successfully.');
    }

    #[Route('/tickets', name: 'tickets')]
    public function manageTickets(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $tickets = $this->ticketService->findAllTickets();

        return $this->render('admin/tickets.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    #[Route('/ticket/{id}/add-attachment', name: 'add_attachment', methods: ['POST'])]
    public function addAttachment(int $id, Request $request): RedirectResponse
    {
        $ticket = $this->ticketService->findTicketById($id);
        return $this->handleAddAttachment($ticket, $request, $this->getParameter('uploads_directory'));
    }

    #[Route('/attachment/delete/{id}', name: 'delete_attachment', methods: ['POST', 'DELETE'])]
    public function deleteAttachment(int $id): RedirectResponse
    {
        $attachment = $this->attachmentService->findAttachmentById($id);
        if (!$attachment) {
            return $this->redirectWithError('admin_tickets', 'Attachment not found.');
        }

        $this->attachmentService->deleteAttachment($attachment, $this->getParameter('uploads_directory'));
        return $this->redirectWithSuccess('admin_tickets', 'Attachment deleted successfully.');
    }

    #[Route('/ticket/{id}', name: 'view_ticket')]
    public function viewTicket(
        int $id,
        Request $request,
        UserRepository $userRepository
    ): Response {
        $ticket = $this->ticketService->findTicketById($id);
        if (!$ticket) {
            return $this->redirectWithError('admin_tickets', 'Ticket not found.');
        }

        $comments = $this->ticketService->findCommentsByTicket($ticket);
        $members = $userRepository->findMembersWithAdminOrSupport();
        $tickethistories = $this->ticketService->findTicketHistoriesByTicket($ticket);

        $comment = new Comment();
        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleAddComment($ticket, $comment);
            return $this->redirectToRoute('admin_view_ticket', ['id' => $id]);
        }

        return $this->render('admin/view_ticket.html.twig', [
            'ticket' => $ticket,
            'comments' => $comments,
            'form' => $form->createView(),
            'users' => $members,
            'tickethistories' => $tickethistories,
        ]);
    }

    #[Route('/ticket/{id}/assign', name: 'assign_ticket', methods: ['POST'])]
    public function assignTicket(int $id, Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $ticket = $this->ticketService->findTicketById($id);
        if (!$ticket) {
            return $this->redirectWithError('admin_tickets', 'Ticket not found.');
        }

        $assignedUserId = $request->request->get('assigned_user');
        $assignedUser = $this->userService->findUserById((int)$assignedUserId);

        if (!$assignedUser || !in_array('ROLE_ADMIN', $assignedUser->getRoles()) && !in_array('ROLE_SUPPORT', $assignedUser->getRoles())) {
            return $this->redirectWithError('admin_view_ticket', 'Invalid user selected.', ['id' => $id]);
        }

        $this->ticketManager->assignTicket($ticket, $assignedUser, $this->getUser());

        return $this->redirectWithSuccess('admin_view_ticket', 'Ticket assigned successfully.', ['id' => $id]);
    }

    #[Route('/ticket/{id}/change-status', name: 'change_status', methods: ['POST'])]
    public function changeStatus(int $id, Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $ticket = $this->ticketService->findTicketById($id);
        if (!$ticket) {
            return $this->redirectWithError('admin_tickets', 'Ticket not found.');
        }

        $newStatus = $request->request->get('status');
        if (!$newStatus || $newStatus === $ticket->getStatus()) {
            return $this->redirectWithError('admin_view_ticket', 'Invalid or unchanged status.', ['id' => $id]);
        }

        $this->ticketManager->changeTicketStatus($ticket, $newStatus, $this->getUser());

        return $this->redirectWithSuccess('admin_view_ticket', 'Ticket status updated successfully.', ['id' => $id]);
    }

    protected function getViewTicketRoute(): string
    {
        return 'admin_view_ticket';
    }

    #[Route('/comment/delete/{id}', name: 'delete_comment', methods: ['POST'])]
    public function deleteComment(int $id): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $comment = $this->ticketService->findCommentById($id);
        if (!$comment || $comment->getOwner() !== $this->getUser()) {
            return $this->redirectWithError('admin_view_ticket', 'You do not have permission to delete this comment.');
        }

        $ticketId = $comment->getTicket()->getId();
        $this->ticketService->deleteComment($comment);

        return $this->redirectWithSuccess('admin_view_ticket', 'Comment deleted successfully.', ['id' => $ticketId]);
    }

    private function handleMemberCreation(Request $request): Response
    {
        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');
        $role = $request->request->get('role', '');

        if (!$email || !$password || !$role) {
            return $this->renderWithError('admin/add_member.html.twig', 'All fields are required.', compact('email', 'password', 'role'));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->renderWithError('admin/add_member.html.twig', 'Invalid email format.', compact('email', 'password', 'role'));
        }

        try {
            $this->userService->createUser($email, $password, [$role]);
            return $this->redirectWithSuccess('admin_members', 'Member added successfully.');
        } catch (\Exception $e) {
            return $this->renderWithError('admin/add_member.html.twig', 'An error occurred while adding the member: ' . $e->getMessage(), compact('email', 'password', 'role'));
        }
    }

    private function isAdminDeletionRestricted(User $member): bool
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        return in_array('ROLE_ADMIN', $member->getRoles()) && !$currentUser->isSuperAdmin();
    }
}
