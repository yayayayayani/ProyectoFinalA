<?php

namespace App\Controller;

use App\Entity\Administrador;
use App\Form\UsuarioType;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/usuarios')]
#[IsGranted('ROLE_ADMIN')]
final class UsuarioController extends AbstractController
{
    #[Route('', name: 'app_usuario_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        return $this->render('usuario/index.html.twig', [
            'usuarios' => $em->getRepository(Administrador::class)->findBy([], ['usuario' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_usuario_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $form = $this->createForm(UsuarioType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if ($em->getRepository(Administrador::class)->findOneBy(['usuario' => $data['usuario']])) {
                $form->get('usuario')->addError(new FormError('Este nombre de usuario ya está registrado.'));
            } else {
                $usuario = (new Administrador())->setUsuario($data['usuario'])->setRol($data['rol']);
                $usuario->setPassword($hasher->hashPassword($usuario, $data['password']));
                try {
                    $em->persist($usuario);
                    $em->flush();
                    $this->addFlash('success', 'Usuario registrado. Ya puede iniciar sesión con el rol asignado.');
                    return $this->redirectToRoute('app_usuario_index', [], Response::HTTP_SEE_OTHER);
                } catch (UniqueConstraintViolationException) {
                    $form->get('usuario')->addError(new FormError('Este nombre de usuario ya está registrado.'));
                }
            }
        }
        return $this->render('usuario/new.html.twig', ['form' => $form]);
    }
}
