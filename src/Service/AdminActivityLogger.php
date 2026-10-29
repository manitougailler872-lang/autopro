<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AdminActivityLog;
use App\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class AdminActivityLogger
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security,
        private readonly RequestStack $requestStack,
    ) {}

    public function log(string $action, ?string $entityType = null, ?int $entityId = null, ?array $metadata = null): void
    {
        $log = new AdminActivityLog();
        $log->setAction($action)
            ->setEntityType($entityType)
            ->setEntityId($entityId)
            ->setMetadata($metadata);

        $user = $this->security->getUser();
        if ($user instanceof AdminUser) {
            $log->setAdminUser($user);
        }

        $request = $this->requestStack->getCurrentRequest();
        if ($request !== null) {
            $log->setIpAddress($request->getClientIp());
        }

        $this->em->persist($log);
        $this->em->flush();
    }
}
