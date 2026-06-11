<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Health probe for load balancers, Docker, and ops monitoring.
 */
final class HealthController extends AbstractController
{
    #[Route(path: '/health', name: 'app_health', methods: ['GET'])]
    public function health(Connection $connection): JsonResponse
    {
        $checks = [
            'app' => 'ok',
            'version' => '2.0.0-prod',
        ];

        try {
            $connection->executeQuery('SELECT 1');
            $checks['database'] = 'ok';
        } catch (\Throwable) {
            $checks['database'] = 'error';
        }

        $uploadDir = $this->getParameter('app.upload_directory');
        $checks['uploads_writable'] = is_dir($uploadDir) && is_writable($uploadDir) ? 'ok' : 'error';

        $logDir = $this->getParameter('kernel.logs_dir');
        $checks['logs_writable'] = is_dir($logDir) && is_writable($logDir) ? 'ok' : 'error';

        $healthy = !in_array('error', $checks, true);

        return $this->json([
            'status' => $healthy ? 'healthy' : 'degraded',
            'service' => 'caert',
            'checks' => $checks,
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ], $healthy ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
