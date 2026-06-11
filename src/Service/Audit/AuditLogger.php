<?php

namespace App\Service\Audit;

use App\Entity\AuditLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

final class AuditLogger
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function log(
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $payload = null,
        ?User $actor = null,
    ): void {
        $entry = new AuditLog();
        $entry->setAction($action);
        $entry->setEntityType($entityType);
        $entry->setEntityId($entityId);
        $entry->setPayload($payload);

        $user = $actor ?? $this->security->getUser();
        if ($user instanceof User) {
            $entry->setActor($user);
        }

        $request = $this->requestStack->getCurrentRequest();
        if ($request !== null) {
            $entry->setIpAddress($request->getClientIp());
        }

        $this->em->persist($entry);
        $this->em->flush();

        $this->logger->info('audit.{action}', [
            'action' => $action,
            'entity' => $entityType,
            'entity_id' => $entityId,
            'actor' => $user instanceof User ? $user->getEmail() : null,
        ]);
    }
}
