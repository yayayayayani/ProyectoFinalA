<?php

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/** Registra resultados HTTP de escrituras sin contraseñas, tokens ni datos personales. */
final class AuditSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Security $security,
        #[Autowire(service: 'monolog.logger.audit')] private readonly LoggerInterface $logger,
    ) {}

    public static function getSubscribedEvents(): array { return [KernelEvents::RESPONSE => ['onResponse', -20]]; }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) return;
        $request = $event->getRequest();
        if (!in_array($request->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) return;
        $route = $request->attributes->get('_route');
        if (!is_string($route) || !str_starts_with($route, 'app_')) return;
        $user = $this->security->getUser();
        $this->logger->info('Solicitud de escritura procesada', [
            'route' => $route,
            'method' => $request->getMethod(),
            'status' => $event->getResponse()->getStatusCode(),
            'admin_id' => $user instanceof \App\Entity\Administrador ? $user->getId() : null,
            'record_id' => $request->attributes->getInt('id'),
        ]);
    }
}
