<?php

namespace App\Controller;

use App\Repository\AllDataRepository;
use App\Security\Voter\AllDataVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class WorkflowController extends AbstractController
{
    #[Route(path: '/workflow/inbox', name: 'workflow_inbox')]
    public function inbox(AllDataRepository $repository): Response
    {
        $pending = $repository->findPendingReview(100);

        return $this->render('workflow/inbox.html.twig', [
            'menu' => 'workflow',
            'pending' => $pending,
            'total' => $repository->countPendingReview(),
        ]);
    }
}
