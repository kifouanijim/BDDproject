<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class InscriptionController extends AbstractController
{
    #[Route('/inscription', name: 'app_inscription')]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérification si l'email est déjà utilisé
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);

            if ($existingUser) {
                $this->addFlash('error', 'Cet email est déjà utilisé.');
                return $this->redirectToRoute('app_inscription');
            }

            // Détermination du rôle en fonction de l'email
            $email = $user->getEmail();
            $role = str_contains($email, '@certideal.com') ? 'ROLE_ADMIN' : 'ROLE_USER';

            // Hachage du mot de passe
            $hashedPassword = $passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);
            $user->setRoles([$role]); // Attribution du rôle

            // Persistance de l'utilisateur en base de données
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Inscription réussie. Vous pouvez maintenant vous connecter.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('inscription/index.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
