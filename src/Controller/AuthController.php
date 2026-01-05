<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
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
        EntityManagerInterface $entityManager
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
                ]);
            }

            $username = trim($request->request->get('username', ''));
            $email = strtolower(trim($request->request->get('email', '')));
            $password = $request->request->get('password', '');
            $confirmPassword = $request->request->get('confirm_password', '');

            $errors = [];

            if (empty($username)) $errors[] = 'Username is required.';
            elseif (strlen($username) < 3) $errors[] = 'Username must be at least 3 characters.';
            elseif (strlen($username) > 50) $errors[] = 'Username too long.';
            elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) $errors[] = 'Invalid username format.';

            if (empty($email)) $errors[] = 'Email is required.';
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
            elseif (strlen($email) > 180) $errors[] = 'Email too long.';

            if (empty($password)) $errors[] = 'Password is required.';
            elseif (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';

            if ($password !== $confirmPassword) $errors[] = 'Passwords do not match.';

            if (!empty($errors)) {
                foreach ($errors as $error) $this->addFlash('error', $error);
                return $this->render('auth/auth.html.twig', [
                    'last_username' => $username,
                    'error' => null,
                    'show_register' => true,
                ]);
            }

            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                $this->addFlash('error', 'Email already registered.');
                return $this->render('auth/auth.html.twig', [
                    'last_username' => $username,
                    'error' => null,
                    'show_register' => true,
                ]);
            }

            // 🔍 DEBUG COMPLET
            try {
                $user = new User();
                $user->setNom($username);
                $user->setEmail($email);
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                $user->setIsVerified(true);

                dump("AVANT PERSIST", $user); // ✅

                $entityManager->persist($user);

                dump("AVANT FLUSH"); // ✅

                $entityManager->flush();

                dump("APRÈS FLUSH - USER ID : ", $user->getId()); // ✅

                $this->addFlash('success', 'Account created! Please log in.');
                return $this->redirectToRoute('app_login');

            } catch (\Exception $e) {
                dd("❌ ERREUR LORS DU FLUSH : " . $e->getMessage()); // 🔥 Montre l’erreur exacte
            }
        }

        return $this->render('auth/auth.html.twig', [
            'last_username' => '',
            'error' => null,
            'show_register' => true,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Logout handled by firewall.');
    }
}