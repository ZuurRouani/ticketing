<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Controller for managing user profiles.
 */
#[Route('/profile', name: 'profile_')]
class ProfileController extends AbstractController
{
    /**
     * Displays and updates the user's profile.
     *
     * @param Request $request The HTTP request.
     * @param EntityManagerInterface $entityManager The entity manager.
     * @param UserPasswordHasherInterface $passwordHasher The password hasher.
     * @return Response The HTTP response.
     */
    #[Route('', name: 'view', methods: ['GET', 'POST'])]
    public function view(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('You must be logged in to access this page.');
        }

        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('password')->getData();
            $currentPassword = $form->get('current_password')->getData();

            // If a new password is provided, validate the current password
            if ($newPassword) {
                if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                    $this->addFlash('error', 'The current password is incorrect.');
                    return $this->redirectToRoute('profile_view');
                }

                // Hash and set the new password
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Your profile has been updated successfully.');

            return $this->redirectToRoute('profile_view');
        }

        return $this->render('profile/view.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
