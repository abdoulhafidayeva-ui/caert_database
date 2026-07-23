<?php

namespace App\Controller;

use App\DataTable\PendingValidationDataTableType;
use App\Entity\Pays;
use App\Entity\Region;
use App\Entity\User;
use App\Repository\AllDataRepository;
use App\Service\Security\UserDataScope;
use Doctrine\ORM\EntityManagerInterface;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_STAFF')]
class WorkflowController extends AbstractController
{
    public function __construct(
        private readonly UserDataScope $dataScope,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route(path: '/workflow/inbox', name: 'workflow_inbox')]
    public function inbox(
        Request $request,
        AllDataRepository $repository,
        DataTableFactory $factory,
    ): Response {
        $table = $factory->createFromType(
            PendingValidationDataTableType::class,
            [
                'dataTableName' => 'pendingValidationTable',
            ],
            ['pageLength' => 25, 'order' => []]
        )->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        $user = $this->getUser();
        $total = $user instanceof User
            ? $repository->countPendingReview($user, $this->dataScope)
            : $repository->countPendingReview();

        return $this->render('workflow/inbox.html.twig', [
            'menu' => 'workflow',
            'table' => $table,
            'total' => $total,
            'regions' => $this->em->getRepository(Region::class)->findAllUniqueByLibelle(),
            'lespays' => $this->em->getRepository(Pays::class)->findAllUniqueByLibelle(),
        ]);
    }
}
