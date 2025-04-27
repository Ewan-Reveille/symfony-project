<?php
namespace App\Controller;

use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class AuthController extends AbstractController
{
    public function __construct(
        private UserRepository $users,
        private UserPasswordHasherInterface $hasher,
        private JWTTokenManagerInterface $jwtManager,
        private AuthenticationUtils $authenticationUtils // injection du service
    ){}

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        // 1. Données JSON entrantes
        $data = json_decode($request->getContent(), true);
        $email    = $data['email']    ?? null;
        $password = $data['password'] ?? null;

        // 2. Last username + erreur Symfony
        $lastUsername = $this->authenticationUtils->getLastUsername();
        $error        = $this->authenticationUtils->getLastAuthenticationError();

        if ($error) {
            // Si Symfony a déjà détecté une erreur d'auth (via un firewall json_login),
            // on la retourne
            return new JsonResponse([
                'last_email' => $lastUsername,
                'error'      => $error->getMessage(),
            ], 401);
        }

        if (!$email || !$password) {
            return new JsonResponse([
                'last_email' => $lastUsername,
                'error'      => 'Email and password are required'
            ], 400);
        }

        // 3. Recherche et validation du mot de passe
        $user = $this->users->findOneBy(['email' => $email]);
        if (!$user || !$this->hasher->isPasswordValid($user, $password)) {
            // On met à jour le LAST_USERNAME en session pour AuthenticationUtils
            $request->getSession()->set('_security.last_username', $email);

            return new JsonResponse([
                'last_email' => $email,
                'error'      => 'Invalid credentials',
            ], 401);
        }

        // 4. Génération du JWT
        $token = $this->jwtManager->create($user);

        // 5. Réponse JSON finale
        return new JsonResponse([
            'token'      => $token,
            'last_email' => $email,
            'error'      => null,
        ]);
    }
}
