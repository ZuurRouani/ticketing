<?php

namespace App\Controller;

use DateTime;
use App\Entity\Ticket;
use DateTimeImmutable;
use App\Entity\Attachment;
use App\Form\TicketFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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
            'tickets'=>$tickets
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
            $ticket->setStatus('New');
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


}
