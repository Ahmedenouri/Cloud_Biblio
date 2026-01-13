<?php

namespace App\Controller;

use App\Entity\PendingUser;
use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home'); // Ou la route de votre choix
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        // IMPORTANT : On doit créer le formulaire d'inscription ici aussi
        // pour qu'il puisse s'afficher dans la partie "Register" du slider.
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user, [
            'action' => $this->generateUrl('app_register')
        ]);

        return $this->render('auth/auth.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'registrationForm' => $form->createView(), // On passe la vue du formulaire
            'show_register' => false, // Par défaut, on montre le Login
        ]);
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        EmailService $emailService,
        UrlGeneratorInterface $urlGenerator,
        AuthenticationUtils $authenticationUtils // Ajouté pour gérer les erreurs de login si on réaffiche la page
    ): Response {
        
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        // 1. On utilise le Formulaire Symfony standard
        $user = new User(); // On utilise User pour capter les données du formulaire
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // Récupération des données propres
            $email = $user->getEmail();
            $nom = $user->getNom();
            $plainPassword = $form->get('plainPassword')->getData();

            // 2. Vérification doublons (User ET PendingUser)
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            $existingPending = $entityManager->getRepository(PendingUser::class)->findOneBy(['email' => $email]);

            if ($existingUser || $existingPending) {
                $this->addFlash('error', 'Email already registered or pending verification.');
                // On laisse couler vers le "return render" final pour réafficher le formulaire
            } else {
                try {
                    // 3. Création du PendingUser (Votre logique spécifique)
                    $pendingUser = new PendingUser();
                    $pendingUser->setNom($nom);
                    $pendingUser->setEmail($email);
                    // On hash le mot de passe
                    $pendingUser->setPassword($passwordHasher->hashPassword($pendingUser, $plainPassword));
                    
                    // Token
                    $verificationToken = bin2hex(random_bytes(32));
                    $pendingUser->setVerificationToken($verificationToken);

                    $entityManager->persist($pendingUser);
                    $entityManager->flush();

                    // 4. Envoi Email
                    $verificationUrl = $urlGenerator->generate('app_verify_email', [
                        'token' => $verificationToken,
                    ], UrlGeneratorInterface::ABSOLUTE_URL);

                    $emailService->sendVerificationEmail(
                        $pendingUser->getEmail(),
                        $verificationUrl,
                        'Veuillez confirmer votre email'
                    );

                    // Succès !
                    return $this->redirectToRoute('app_check_email');

                } catch (\Exception $e) {
                    // Erreur technique (envoi mail, bdd...)
                    $this->addFlash('error', 'Registration error: ' . $e->getMessage());
                }
            }
        }

        // --- GESTION DES ERREURS D'AFFICHAGE ---
        // Si on arrive ici, c'est que le formulaire est invalide OU qu'il y a eu une erreur (doublon/exception)
        // On doit réafficher la page Auth complète (avec Login + Register ouvert)

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('auth/auth.html.twig', [
            'registrationForm' => $form->createView(), // Le formulaire contient maintenant les erreurs
            'last_username' => $lastUsername,
            'error' => $error,
            'show_register' => true, // IMPORTANT : On force l'affichage du panneau Register
        ]);
    }

    #[Route('/register/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        return $this->render('registration/check_email.html.twig');
    }

    #[Route('/verify/email/{token}', name: 'app_verify_email')]
    public function verifyUserEmail(string $token, EntityManagerInterface $entityManager): Response
    {
        // Votre logique de vérification était correcte
        $pendingUser = $entityManager->getRepository(PendingUser::class)->findOneBy([
            'verificationToken' => $token
        ]);

        if (!$pendingUser) {
            $this->addFlash('error', 'Invalid verification link or already verified.');
            return $this->redirectToRoute('app_login');
        }

        // Transfert de PendingUser vers User
        $user = new User();
        $user->setEmail($pendingUser->getEmail());
        $user->setNom($pendingUser->getNom());
        $user->setPassword($pendingUser->getPassword()); // Le mot de passe est déjà hashé
        $user->setIsVerified(true);
        // On initialise les autres champs requis par défaut si nécessaire
        $user->setRoles(['ROLE_USER']); 

        $entityManager->persist($user);
        $entityManager->remove($pendingUser);
        $entityManager->flush();

        $this->addFlash('success', 'Your email has been verified! You can now log in.');

        return $this->redirectToRoute('app_login');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Logout handled by firewall.');
    }
}