<?php

namespace App\Controller;

use App\Entity\PendingUser; // Import PendingUser
use App\Entity\User;
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
            return $this->redirectToRoute('app_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('auth/auth.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'show_register' => false,
        ]);
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        EmailService $emailService,
        UrlGeneratorInterface $urlGenerator
    ): Response {
        if ($this->getUser()) {
             return $this->redirectToRoute('app_home');
        }

        if ($request->isMethod('POST')) {
            $submittedToken = $request->request->get('_csrf_token');
            if (!$this->isCsrfTokenValid('register', $submittedToken)) {
                $this->addFlash('error', 'Security error. Please try again.');
                return $this->render('auth/auth.html.twig', [
                    'last_username' => '',
                    'error' => null,
                    'show_register' => true,
                ], new Response(null, 422));
            }

            $username = trim($request->request->get('username', ''));
            $email = strtolower(trim($request->request->get('email', '')));
            $password = $request->request->get('password', '');
            $confirmPassword = $request->request->get('confirm_password', '');

            $errors = [];

            if (empty($username)) {
                $errors[] = 'Username is required.';
            } elseif (strlen($username) < 3) {
                $errors[] = 'Username must be at least 3 characters.';
            } elseif (strlen($username) > 50) {
                $errors[] = 'Username too long.';
            } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
                $errors[] = 'Invalid username format.';
            }

            if (empty($email)) {
                $errors[] = 'Email is required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email format.';
            } elseif (strlen($email) > 180) {
                $errors[] = 'Email too long.';
            }

            if (empty($password)) {
                $errors[] = 'Password is required.';
            } elseif (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters.';
            }

            if ($password !== $confirmPassword) {
                $errors[] = 'Passwords do not match.';
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->render('auth/auth.html.twig', [
                    'last_username' => $username,
                    'error' => null,
                    'show_register' => true,
                ], new Response(null, 422));
            }

            // Check if email exists in User OR PendingUser
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            $existingPending = $entityManager->getRepository(PendingUser::class)->findOneBy(['email' => $email]);
            
            if ($existingUser || $existingPending) {
                $this->addFlash('error', 'Email already registered or pending verification.');
                return $this->render('auth/auth.html.twig', [
                    'last_username' => $username,
                    'error' => null,
                    'show_register' => true,
                ], new Response(null, 422));
            }

            try {
                // Create PENDING USER instead of real User
                $pendingUser = new PendingUser();
                $pendingUser->setNom($username);
                $pendingUser->setEmail($email);
                $pendingUser->setPassword($passwordHasher->hashPassword($pendingUser, $password)); // Using same hasher logic
                
                // Générer le token de vérification
                $verificationToken = bin2hex(random_bytes(32));
                $pendingUser->setVerificationToken($verificationToken);
                // No isVerified field needed for PendingUser

                $entityManager->persist($pendingUser);
                $entityManager->flush();

                // Créer l'URL de vérification absolue
                $verificationUrl = $urlGenerator->generate('app_verify_email', [
                    'token' => $verificationToken,
                ], UrlGeneratorInterface::ABSOLUTE_URL);
                
                // Envoyer l'email de vérification
                try {
                    $emailService->sendVerificationEmail(
                        $pendingUser->getEmail(),
                        $verificationUrl,
                        'Veuillez confirmer votre email'
                    );

                    // Redirect to success page
                    return $this->redirectToRoute('app_check_email', [], 303);

                } catch (\Exception $e) {
                    // TEMP: Dump error to screen
                    dd("EMAIL SEND ERROR: " . $e->getMessage());
                    
                    $this->addFlash('warning', 'Account created but email could not be sent: ' . $e->getMessage());
                    error_log("REGISTER WARNING: Email send failed: " . $e->getMessage());
                    // In a real app we might want to delete the pending user here so they can try again,
                    // but for now let's leave it.
                    return $this->redirectToRoute('app_login'); 
                }

            } catch (\Exception $e) {
                $this->addFlash('error', 'Registration error: ' . $e->getMessage());
                return $this->render('auth/auth.html.twig', [
                    'last_username' => $username,
                    'error' => null,
                    'show_register' => true,
                ], new Response(null, 422));
            }
        }

        return $this->render('auth/auth.html.twig', [
            'last_username' => '',
            'error' => null,
            'show_register' => true,
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
        // Search in PendingUser first
        $pendingUser = $entityManager->getRepository(PendingUser::class)->findOneBy([
            'verificationToken' => $token
        ]);

        if (!$pendingUser) {
            $this->addFlash('error', 'Invalid verification link or already verified.');
            return $this->redirectToRoute('app_login');
        }

        // Move data from PendingUser to User
        $user = new User();
        $user->setEmail($pendingUser->getEmail());
        $user->setNom($pendingUser->getNom());
        $user->setPassword($pendingUser->getPassword());
        $user->setIsVerified(true); // Verified because they clicked the link
        $user->setVerificationToken(null); // Clear token

        // Save new User
        $entityManager->persist($user);
        
        // Remove PendingUser
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