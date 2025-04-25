<?php

namespace App\Controller;

use DateTime;
use App\Entity\Ticket;
use DateTimeImmutable;
use App\Entity\Comment;
use App\Entity\Attachment;
use App\Form\TicketFormType;
use App\Form\CommentFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


#[Route('/user', name: 'user_')]
final class UserController extends AbstractController
{

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/tickets', name: 'tickets')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $tickets = $this->entityManager->getRepository(Ticket::class)->findBy(['owner' => $this->getUser()]);

        return $this->render('user/index.html.twig', [
            'tickets' => $tickets
        ]);
    }

    #[Route('/create-ticket', name: 'create_ticket')]
    public function createTicket(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $ticket = new Ticket();
        $form = $this->createForm(TicketFormType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ticket->setOwner($this->getUser());
            $ticket->setStatus('new');
            $ticket->setCreatedAt(new \DateTimeImmutable());

            // Gestion des fichiers téléchargés
            $uploadedFiles = $form->get('attachments')->getData();
            if ($uploadedFiles) { // Vérifie que des fichiers ont été téléchargés
                foreach ($uploadedFiles as $uploadedFile) {
                    if ($uploadedFile instanceof UploadedFile) {
                        $filename = uniqid() . '.' . $uploadedFile->guessExtension();
                        $filePath = $this->getParameter('uploads_directory') . '/' . $filename;

                        $uploadedFile->move($this->getParameter('uploads_directory'), $filename);

                        $attachment = new Attachment();
                        $attachment->setFileName($filename);
                        $attachment->setFilePath($filePath); // Définit le chemin complet du fichier
                        $attachment->setTicket($ticket);
                        $attachment->setCreatedAt(new DateTimeImmutable());

                        $this->entityManager->persist($attachment);
                    }
                }
            }

            $this->entityManager->persist($ticket);
            $this->entityManager->flush();

            $this->addFlash('success', 'Ticket created successfully.');

            return $this->redirectToRoute('user_tickets');
        }

        return $this->render('user/create_ticket.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/ticket/{id}', name: 'view_ticket')]
    public function viewTicket(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $ticket = $this->entityManager->getRepository(Ticket::class)->find($id);

        if (!$ticket || $ticket->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You do not have access to this ticket.');
        }
        // Créer un nouvel objet Comment
        $comment = new Comment();
        // CréFormulaireCommentaire
        $form = $this->createForm(CommentFormType::class, $comment);
        // Traiter la requête du formulaire
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Lier le ticket et l'utilisateur au commentaire
            $comment->setTicket($ticket);
            $comment->setOwner($this->getUser());  // L'utilisateur qui a laissé le commentaire

            // Ajoute Date
            $comment->setCreatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            // Ajouter un message flash pour l'utilisateur
            $this->addFlash('success', 'Votre commentaire a été ajouté avec succès.');

            return $this->redirectToRoute('user_view_ticket', ['id' => $ticket->getId()]);
        }
        // dd($ticket);
        $comments = $ticket->getComments();


        return $this->render('user/view_ticket.html.twig', [
            'ticket' => $ticket,
            'comments' => $comments,
            'form' => $form->createView(),
        ]);
    }
    #[Route('/attachment/{id}/delete', name: 'delete_attachment')]
    public function deleteAttachment(int $id): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        // cette ligne veut dire pour récuperer l attachment par son ID (id attachment)
        // exemp: select * from attachment where id= 2;
        $attachment = $this->entityManager->getRepository(Attachment::class)->find($id);
        if (!$attachment || $attachment->getTicket()->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You do not have access to this attchment.');
        }

        $filePath = $this->getParameter('uploads_directory') . '/' . $attachment->getFileName();

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $this->entityManager->remove($attachment);

        $this->entityManager->flush();

        $ticketId = $attachment->getTicket()->getId();

        $this->addFlash('succes', 'attachment delete succcefully');

        return $this->redirectToRoute('user_view_ticket', ['id' => $ticketId]);
    }


    #[Route('/attachment/{id}/add', name: 'add_attachment')]
    public function addAttachment(int $id, Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $ticket = $this->entityManager->getRepository(Ticket::class)->find($id);

        if (!$ticket || $ticket->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You do not have access to this ticket.');
        }

        $uploadedFile = $request->files->get('attachment');
        if ($uploadedFile instanceof UploadedFile) {
            $filename = uniqid() . '.' . $uploadedFile->guessExtension();
            $filePath = $this->getParameter('uploads_directory') . '/' . $filename;

            $uploadedFile->move($this->getParameter('uploads_directory'), $filename);

            $attachment = new Attachment();
            $attachment->setFileName($filename);
            $attachment->setFilePath($filePath);
            $attachment->setTicket($ticket);

            $this->entityManager->persist($attachment);
            $this->entityManager->flush();

            $this->addFlash('success', 'Attachment added successfully.');
        } else {
            $this->addFlash('error', 'Failed to upload the file.');
        }

        return $this->redirectToRoute('user_view_ticket', ['id' => $id]);
    }



    //Suppresion Ticket

    #[Route('/ticket/{id}/delete', name: 'delete_ticket')]
    public function deleteTicket(int $id): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $ticket = $this->entityManager->getRepository(Ticket::class)->find($id);

        if (!$ticket || $ticket->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à ce ticket.");
        }

        if ($ticket->getStatus() !== "new") {
            $this->addFlash('error', "Vous ne pouvez pas supprimer ce ticket.");

            return $this->redirectToRoute('user_view_ticket', ['id' => $ticket->getId()]);
        }

        //  Suppression des pièces jointes (fichiers et base)
        $attachments = $ticket->getAttachments();
        if ($attachments) {
            foreach ($attachments as $attachment) {
                $filePath = $this->getParameter('uploads_directory') . '/' . $attachment->getFileName();

                if (file_exists($filePath)) {
                    unlink($filePath);
                }

                $this->entityManager->remove($attachment);
            }
        }

        // Suppression des commentaires liés au ticket
        $comments = $ticket->getComments();
        if ($comments) {
            foreach ($comments as $comment) {
                $this->entityManager->remove($comment);
            }
        }

        //  Suppression du ticket lui-même
        $this->entityManager->remove($ticket);

        //  Exécuter toutes les suppressions en base de données
        $this->entityManager->flush();

        //  Message flash
        $this->addFlash('success', 'Le ticket a été supprimé avec succès');

        //  Redirection vers la liste des tickets
        return $this->redirectToRoute('user_tickets');
    }


    //Ajoute commentaire 


    #[Route('/comment/delete/{id}', name: 'delete_comment', methods: ['POST'])]
    public function deleteComment(int $id): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $comment = $this->entityManager->getRepository(Comment::class)->find($id);

        if (!$comment || $comment->getOwner() !== $this->getUser()) {
            $this->addFlash('error', 'You do not have permission to delete this comment.');
            return $this->redirectToRoute('user_view_ticket', ['id' => $comment->getTicket()->getId()]);
        }

        $ticketId = $comment->getTicket()->getId();
        $this->entityManager->remove($comment);
        $this->entityManager->flush();

        $this->addFlash('success', 'Comment deleted successfully.');
        return $this->redirectToRoute('user_view_ticket', ['id' => $ticketId]);
    }
}
