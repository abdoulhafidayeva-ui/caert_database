<?php

namespace App\Controller;

use App\Form\EnvConfigType;
use App\Service\Audit\AuditLogger;
use App\Service\Config\EditableEnvRegistry;
use App\Service\Config\EnvFileManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN', message: 'security.access_app')]
class EnvConfigController extends AbstractAppController
{
    #[Route(path: '/admin/env-config', name: 'app_env_config', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        EnvFileManager $envFileManager,
        EditableEnvRegistry $registry,
        AuditLogger $auditLogger,
    ): Response {
        $currentValues = $envFileManager->getCurrentValues();
        $form = $this->createForm(EnvConfigType::class, $currentValues);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            foreach ($registry->all() as $key => $definition) {
                if (!$definition->required) {
                    continue;
                }

                $submittedValue = trim((string) $form->get($key)->getData());
                $currentValue = trim($currentValues[$key] ?? '');

                if ($submittedValue === '' && $currentValue === '') {
                    $form->get($key)->addError(new \Symfony\Component\Form\FormError(
                        $this->trans('env_config.error.required')
                    ));
                }
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array<string, string|null> $submitted */
            $submitted = $form->getData();
            $changes = $envFileManager->applyChanges($submitted);

            if ($changes === []) {
                $this->addFlash('info', 'env_config.flash.no_changes');
            } else {
                $auditLogger->log(
                    action: 'env_config.update',
                    entityType: 'env_config',
                    payload: [
                        'keys' => array_keys($changes),
                        'file' => basename($envFileManager->getTargetFilePath()),
                    ],
                );

                $this->addFlash('success', 'env_config.flash.saved');
                $this->addFlash('warning', 'env_config.flash.restart_hint');
            }

            return $this->redirectToRoute('app_env_config');
        }

        return $this->render('parametrage/env_config.html.twig', [
            'form' => $form->createView(),
            'menu' => 'envConfig',
            'groupedDefinitions' => $registry->grouped(),
            'targetFile' => basename($envFileManager->getTargetFilePath()),
        ]);
    }
}
