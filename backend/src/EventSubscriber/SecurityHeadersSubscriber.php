<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array { return [KernelEvents::RESPONSE => ['onResponse', -10]]; }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) return;
        $response = $event->getResponse();
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        // Compatible con importmap, Turbo, vista previa e impresión.
        $response->headers->set('Content-Security-Policy', "base-uri 'self'; object-src 'none'; frame-ancestors 'none'; form-action 'self'");
        // No almacenar páginas con datos de jugadores, sesiones o tokens en cachés compartidas.
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');
    }
}
