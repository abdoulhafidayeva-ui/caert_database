<?php

namespace App\Controller;

use App\DataTable\AllDataDataTableType;
use App\Entity\Attaque;
use App\Entity\Cible;
use App\Entity\Espace;
use App\Entity\AllData;
use App\Entity\Pays;
use App\Entity\Perpetrateurs;
use App\Entity\Region;
use App\Entity\User;
use App\Form\AllDataFormType;
use App\Form\AllDataUpdateFormType;
use App\Repository\AllDataRepository;
use App\Security\Voter\AllDataVoter;
use App\Service\Audit\AuditLogger;
use App\Service\Import\ExcelImportService;
use App\Service\Incident\AllDataTotalsCalculator;
use App\Service\Security\IncidentCountryGuard;
use Doctrine\ORM\EntityManagerInterface;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class HomeController extends AbstractController
{
    private string $menu = 'dashboard';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AllDataTotalsCalculator $totalsCalculator,
        private readonly ExcelImportService $excelImportService,
        private readonly AuditLogger $auditLogger,
        private readonly AllDataRepository $allDataRepository,
        private readonly IncidentCountryGuard $countryGuard,
    ) {
    }

    #[Route(path: '/', name: 'app_home')]
    public function index(Request $request, DataTableFactory $factory): Response
    {
        $table = $factory->createFromType(
            AllDataDataTableType::class,
            [
                'dataTableName' => 'alldatasTable',
                'fields' => ['all'],
                'visible_fields' => ['all'],
            ],
            ['pageLength' => 50, 'order' => []]
        )->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        $pendingCount = 0;
        $summary = null;
        if ($this->isGranted('ROLE_ADMIN')) {
            $pendingCount = $this->allDataRepository->countPendingReview();
            $summary = $this->allDataRepository->getExecutiveSummary();
        }

        $importErrors = $request->getSession()->get('import_errors');
        $request->getSession()->remove('import_errors');

        return $this->render('home/index.html.twig', [
            'table' => $table,
            'menu' => $this->menu,
            'pendingCount' => $pendingCount,
            'summary' => $summary,
            'importErrors' => $importErrors,
            'cibles' => $this->em->getRepository(Cible::class)->findAll(),
            'lespays' => $this->em->getRepository(Pays::class)->findAllUniqueByLibelle(),
            'attaques' => $this->em->getRepository(Attaque::class)->findAll(),
            'perpetrateurs' => $this->em->getRepository(Perpetrateurs::class)->findAll(),
            'espaces' => $this->em->getRepository(Espace::class)->findAll(),
            'regions' => $this->em->getRepository(Region::class)->findAllUniqueByLibelle(),
        ]);
    }

    /**
     * @deprecated Use route data_import — kept for bookmarks and external integrations.
     */
    #[Route(path: '/inport/data', name: 'data_inport', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function dataInportLegacy(Request $request): Response
    {
        return $this->dataImport($request);
    }

    #[Route(path: '/import/data', name: 'data_import', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function dataImport(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('import_data', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $file = $request->files->get('file');
        if ($file === null) {
            $this->addFlash('error', 'Aucun fichier sélectionné.');

            return $this->redirectToRoute('app_home');
        }

        $user = $this->getUser();
        if ($user === null) {
            throw $this->createAccessDeniedException();
        }

        try {
            $result = $this->excelImportService->import($file, $user);
            if ($result->errorCount > 0) {
                $this->addFlash('warning', sprintf(
                    'Import partiel : %d ligne(s) OK, %d erreur(s).',
                    $result->successCount,
                    $result->errorCount
                ));
                $request->getSession()->set('import_errors', $result->errors);
            } else {
                $this->addFlash('success', sprintf('%d ligne(s) importée(s) avec succès.', $result->successCount));
            }
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Import échoué : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_home');
    }

    #[Route(path: '/new/data', name: 'new_data', methods: ['GET', 'POST'])]
    public function newData(Request $request): Response
    {
        $form = $this->createForm(AllDataFormType::class)
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => ['class' => 'btn btn-square btn-primary'],
            ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $incident = $form->getData();
            $user = $this->getUser();
            if ($user instanceof User) {
                $this->countryGuard->assertCountryAllowed($user, $incident->getPays());
            }

            $now = new \DateTime();
            $incident->setCreatedAt($now);

            $dateAttaque = $incident->getDateAttaque();
            if ($dateAttaque instanceof \DateTimeInterface) {
                $merged = \DateTime::createFromInterface($dateAttaque);
                $merged->setTime(
                    (int) $now->format('H'),
                    (int) $now->format('i'),
                    (int) $now->format('s')
                );
                $incident->setDateAttaque($merged);
            }

            $this->totalsCalculator->applyTotals($incident);
            $incident->setUser($this->getUser());
            $incident->setIsPublished(null);
            $this->em->persist($incident);
            $this->em->flush();

            $this->auditLogger->log('INCIDENT_CREATE', 'all_data', $incident->getId());

            $this->addFlash('success', 'Enregistrement effectué.');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('home/new.html.twig', [
            'form' => $form->createView(),
            'menu' => $this->menu,
            'titreFormulaire' => 'Nouvel enregistrement',
        ]);
    }

    #[Route(path: '/data/{allData}', name: 'view_all_data', methods: ['GET'])]
    public function viewData(AllData $allData, Request $request): Response
    {
        $this->denyAccessUnlessGranted(AllDataVoter::VIEW, $allData);

        $backUrl = $request->query->get('from') === 'inbox'
            ? $this->generateUrl('workflow_inbox')
            : $this->generateUrl('app_home');

        return $this->render('home/show.html.twig', [
            'menu' => $this->menu,
            'incident' => $allData,
            'backUrl' => $backUrl,
        ]);
    }

    #[Route(path: '/update/data/{allData}', name: 'update_all_data', methods: ['GET', 'POST'])]
    public function updateData(Request $request, AllData $allData): Response
    {
        $this->denyAccessUnlessGranted(AllDataVoter::EDIT, $allData);

        if ($allData->getIsPublished() !== null) {
            $this->addFlash('warning', 'Cet enregistrement ne peut plus être modifié (déjà publié ou rejeté).');

            return $this->redirectToRoute('view_all_data', ['allData' => $allData->getId()]);
        }

        $form = $this->createForm(AllDataUpdateFormType::class, $allData, [
            'method' => 'POST',
            'action' => $this->generateUrl('update_all_data', ['allData' => $allData->getId()]),
        ])->add('save', SubmitType::class, [
            'label' => 'Modifier l\'enregistrement',
            'attr' => ['class' => 'btn btn-square btn-primary'],
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $incident = $form->getData();
            $user = $this->getUser();
            if ($user instanceof User) {
                $this->countryGuard->assertCountryAllowed($user, $incident->getPays());
            }

            $previousDate = $allData->getDateAttaque();
            $newDate = $incident->getDateAttaque();
            if ($previousDate instanceof \DateTimeInterface && $newDate instanceof \DateTimeInterface) {
                $merged = \DateTime::createFromInterface($newDate);
                $merged->setTime(
                    (int) $previousDate->format('H'),
                    (int) $previousDate->format('i'),
                    (int) $previousDate->format('s')
                );
                $incident->setDateAttaque($merged);
            }

            $this->totalsCalculator->applyTotals($incident);
            $incident->setUser($this->getUser());
            $incident->setObjetRejet(null);
            $incident->setIsPublished(null);
            $this->em->flush();

            $this->auditLogger->log('INCIDENT_UPDATE', 'all_data', $incident->getId());

            $this->addFlash('success', 'Modification enregistrée.');

            return $this->redirectToRoute('view_all_data', ['allData' => $incident->getId()]);
        }

        return $this->render('home/new.html.twig', [
            'form' => $form->createView(),
            'menu' => $this->menu,
            'titreFormulaire' => 'Modification de l\'enregistrement',
            'backUrl' => $this->generateUrl('view_all_data', ['allData' => $allData->getId()]),
        ]);
    }

    #[Route(path: '/alldata/delete/{allData}', name: 'delete_all_data', methods: ['POST'])]
    public function deleteData(Request $request, AllData $allData): Response
    {
        $this->denyAccessUnlessGranted(AllDataVoter::DELETE, $allData);

        if (!$this->isCsrfTokenValid('delete' . $allData->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $id = $allData->getId();
        $this->em->remove($allData);
        $this->em->flush();

        $this->auditLogger->log('INCIDENT_DELETE', 'all_data', $id);

        $this->addFlash('success', 'Suppression effectuée.');

        return $this->redirectToRoute('app_home');
    }

    #[Route(path: '/alldata/publish_or_not/{allData}', name: 'publish_or_not', methods: ['POST'], options: ['expose' => true])]
    public function publishOrNot(Request $request, AllData $allData): Response
    {
        $this->denyAccessUnlessGranted(AllDataVoter::PUBLISH, $allData);

        $objetRejet = trim((string) $request->request->get('objetRejet', ''));

        if ($objetRejet !== '') {
            $allData->setObjetRejet($objetRejet);
            $allData->setIsPublished(false);
            $msg = 'rejetées';
            $action = 'INCIDENT_REJECT';
        } else {
            $allData->setObjetRejet(null);
            $allData->setIsPublished(true);
            $msg = 'publiées';
            $action = 'INCIDENT_PUBLISH';
        }

        $this->em->flush();
        $this->auditLogger->log($action, 'all_data', $allData->getId(), [
            'objet_rejet' => $allData->getObjetRejet(),
        ]);

        $this->addFlash('success', 'Données ' . $msg);

        if ($request->request->get('_return') === 'view') {
            return $this->redirectToRoute('view_all_data', ['allData' => $allData->getId()]);
        }

        $referer = $request->headers->get('referer', '');
        if (str_contains($referer, '/workflow/inbox')) {
            return $this->redirectToRoute('workflow_inbox');
        }

        return $this->redirectToRoute('app_home');
    }

    #[Route(path: '/objet_rejet_msg/{id}', name: 'objet_rejet_msg', options: ['expose' => true], methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function objetRejetMsg(int $id): JsonResponse
    {
        $data = $this->allDataRepository->find($id);
        if ($data === null) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(AllDataVoter::VIEW, $data);

        return $this->json([
            'msg' => $data->getObjetRejet() ?? 'aucun commentaire',
        ]);
    }
}
