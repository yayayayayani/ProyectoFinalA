<?php
namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
final class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authentication): Response
    {
        if ($this->isGranted('ROLE_ARBITRO')) return $this->redirectToRoute('app_inicio');
        $error = $authentication->getLastAuthenticationError();
        $mensajeError = match (true) {
            $error instanceof InvalidCsrfTokenException => 'No se pudo validar el formulario de acceso. Recarga la página e inténtalo de nuevo.',
            $error instanceof TooManyLoginAttemptsAuthenticationException => 'Hubo demasiados intentos de acceso. Espera un minuto antes de volver a intentarlo.',
            default => 'Usuario o contraseña incorrectos. Revisa los datos e inténtalo de nuevo.',
        };
        return $this->render('security/login.html.twig', [
            'ultimo_usuario' => $authentication->getLastUsername(),
            'error' => $error,
            'mensajeError' => $mensajeError,
        ]);
    }
    #[Route('/logout', name: 'app_logout', methods: ['POST'])]
    public function logout(): never { throw new \LogicException('Symfony intercepta el cierre de sesión.'); }
}
