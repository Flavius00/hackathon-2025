<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

class AuthController extends BaseController
{
    public function __construct(
        Twig $view,
        private AuthService $authService,
        private LoggerInterface $logger,
    ) {
        parent::__construct($view);
    }

    public function showRegister(Request $request, Response $response): Response
    {
        // TODO: you also have a logger service that you can inject and use anywhere; file is var/app.log
        $this->logger->info('Register page requested');

        return $this->render($response, 'auth/register.twig');
    }

    public function register(Request $request, Response $response): Response
    {
        // TODO: call corresponding service to perform user registration
        $data = $request->getParsedBody();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        $passwordVerification = $data['confirm_password'] ?? '';

        try {

            $user = $this->authService->register($username, $password, $passwordVerification);
            $this->logger->info('User registered successfully', ['username' => $username]);

        } catch (\Exception $e) {

            $this->logger->error('User registration failed', ['error' => $e->getMessage()]);
            
            return $this->render($response, 'auth/register.twig', [
                'error' => $e->getMessage(),
            ]);
        }

        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    public function showLogin(Request $request, Response $response): Response
    {
        return $this->render($response, 'auth/login.twig');
    }

    public function login(Request $request, Response $response): Response
    {
        // TODO: call corresponding service to perform user login, handle login failures
        $data = $request->getParsedBody();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        try{
            $login = $this->authService->attempt($username, $password);

            if (!$login) {
                throw new \Exception('Invalid username or password.');
            }


            $this->logger->info('User logged in successfully', ['username' => $username]);


            return $response->withHeader('Location', '/')->withStatus(302);


        } catch (\Exception $e) {
            $this->logger->error('User login failed', ['error' => $e->getMessage()]);

            return $this->render($response, 'auth/login.twig', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function logout(Request $request, Response $response): Response
    {
        // TODO: handle logout by clearing session data and destroying session
        if (session_status() === PHP_SESSION_NONE) {
            $userId = $_SESSION['user_id'] ?? null;
            $this->logger->info('User logged out', ['user_id' => $userId]);

            $_SESSION = [];
            session_destroy();
        }

        return $response->withHeader('Location', '/login')->withStatus(302);
    }
}
