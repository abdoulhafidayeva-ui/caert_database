<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\AllDataRepository;
use App\Service\Security\UserDataScope;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_STAFF')]
class WorkflowController extends AbstractController
{
    private const INBOX_PAGE_SIZE = 20;

    public function __construct(private readonly UserDataScope $dataScope)
    {
    }

    #[Route(path: '/workflow/inbox', name: 'workflow_inbox')]
    public function inbox(Request $request, AllDataRepository $repository, PaginatorInterface $paginator): Response
    {
        $qb = $repository->createPendingReviewQueryBuilder();
        $user = $this->getUser();
        if ($user instanceof User) {
            $this->dataScope->applyRegionScopeToQueryBuilder($qb, $user);
        }

        $pending = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            self::INBOX_PAGE_SIZE
        );

        return $this->render('workflow/inbox.html.twig', [
            'menu' => 'workflow',
            'pending' => $pending,
            'total' => $pending->getTotalItemCount(),
        ]);
    }
}
