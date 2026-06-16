<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class DoctrineFlushSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -1024],
            KernelEvents::TERMINATE => ['onKernelTerminate', -1024],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if ($this->em->isOpen()) {
            $this->em->flush();
        }
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        if ($this->em->isOpen()) {
            $this->em->flush();
        }
    }
}
