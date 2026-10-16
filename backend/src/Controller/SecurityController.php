<?php
namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
final class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authentication): Response
    {
        if ($this->isGranted('ROLE_ARBITRO')) return $this->redirectToRoute('app_inicio');
        return $this->render('security/login.html.twig', [
            'ultimo_usuario' => $authentication->getLastUsername(),
            'error' => $authentication->getLastAuthenticationError(),
        ]);
    }
    #[Route('/logout', name: 'app_logout', methods: ['POST'])]
    public function logout(): never { throw new \LogicException('Symfony intercepta el cierre de sesión.'); }
}
