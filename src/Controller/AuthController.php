<?php

namespace App\Controller;

use LogicException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AuthController extends AbstractController
{
    #[Route('/login', name: 'login', methods: ["GET", "POST"])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        $renderParameters = [
            'last_username' => $lastUsername,
            'error' => $error,
            'path' => 'auth'
        ];

        return $this->render('auth/login.html.twig', $renderParameters);
    }
    
    #[Route('/logout', name: 'logout', methods: ["GET"])]
    public function logout()
    {
        throw new LogicException(); // Blank Method
    }
}
