<?php

namespace App\Controller;

use App\DataTable\AttaqueDataTableType;
use App\DataTable\CibleDataTableType;
use App\DataTable\EspaceDataTableType;
use App\DataTable\MaterielAttaqueDataTableType;
use App\DataTable\MaterielDataTableType;
use App\DataTable\MoyenAttaqueDataTableType;
use App\DataTable\PerpetrateurDataTableType;
use App\Entity\Attaque;
use App\Entity\Cible;
use App\Entity\Espace;
use App\Entity\Materiaux;
use App\Entity\MaterielAttaque;
use App\Entity\MoyenAttaque;
use App\Entity\Perpetrateurs;
use App\Form\AttaqueFormType;
use App\Form\CibleFormType;
use App\Form\EspaceFormType;
use App\Form\MaterielAttaqueFormType;
use App\Form\MaterielFormType;
use App\Form\MoyenAttaqueFormType;
use App\Form\PerpetrateurFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class AllParamController extends AbstractController
{
    private $menu = "dashboard";

    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    #[IsGranted('ROLE_SUPER_ADMIN', message: "Vous n'avez pas accès à cette partie de l'application!")]
    #[Route(path: '/attaque/list/{attaque}', name: 'attaque', methods: ['GET', 'POST'])]
    public function list(Request $request, DataTableFactory $dataTableFactory, Attaque $attaque = null)
    {
        $parameterRep = $this->em->getRepository(Attaque::class);
        $form = $this->createForm(AttaqueFormType::class, $attaque, [
            'action' => $this->generateUrl('attaque', ['attaque' => $attaque == null ? $attaque : $attaque->getId()]),
            'method' => 'POST'
        ])->add('save', SubmitType::class, [
            'label' => $attaque == null ? 'Ajouter' : 'Modifier',
            'attr' => ['class' => 'btn btn-square btn-success']
        ]);

        if($request->isMethod(Request::METHOD_POST))
        {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $attaque = $form->getData();
                $attaque->setUser($this->getUser());
                $this->em->persist($attaque);
                $this->em->flush();
                $this->addFlash('success', 'Enrégistrement bien éffectué');
                return $this->redirectToRoute('attaque');
            }
        }

        $dataTableTypeOptions = [
            'dataTableName' => 'attaqueTable',
            'fields' => ['all'],
            'visible_fields' => ['all']
        ];
        $table = $dataTableFactory->createFromType(
            AttaqueDataTableType::class,
            $dataTableTypeOptions,
            ['pageLength' => 10, 'order' => [[1, 'asc']]]
        )->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }
        return $this->render('attaque/list.html.twig', [
            'form' => $form->createView(),
            'datatable' => $table,
            'menu' => "attaque"
        ]);
    }

    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route(
        path: 'attaque/delete/{attaque}',
        name: 'deleteAttaque',
        methods: ['POST', 'GET']
    )]
    public function deleteAttaque(Request $request, Attaque $attaque)
    {
            try {
                $this->em->remove($attaque);
                $this->em->flush();
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
                throw $e;
            }
            $this->addFlash('success', 'Suppression éffectuée');

            return $this->redirectToRoute('attaque');
    }

    #[IsGranted('ROLE_SUPER_ADMIN', message: "Vous n'avez pas accès à cette partie de l'application!")]
    #[Route(path: '/cible/list/{cible}', name: 'cible', methods: ['GET', 'POST'])]
    public function cibleList(Request $request, DataTableFactory $dataTableFactory, Cible $cible = null)
    {
        $parameterRep = $this->em->getRepository(Cible::class);
        $form = $this->createForm(CibleFormType::class, $cible, [
            'action' => $this->generateUrl('cible', ['cible' => $cible == null ? $cible : $cible->getId()]),
            'method' => 'POST'
        ])->add('save', SubmitType::class, [
            'label' => $cible == null ? 'Ajouter' : 'Modifier',
            'attr' => ['class' => 'btn btn-square btn-success']
        ]);

        if($request->isMethod(Request::METHOD_POST))
        {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $cible = $form->getData();
                $cible->setUser($this->getUser());
                $this->em->persist($cible);
                $this->em->flush();
                $this->addFlash('success', 'Enrégistrement bien éffectué');

                return $this->redirectToRoute('cible');
            }
        }

        $dataTableTypeOptions = [
            'dataTableName' => 'cibleTable',
            'fields' => ['all'],
            'visible_fields' => ['all']
        ];
        $table = $dataTableFactory->createFromType(
            CibleDataTableType::class,
            $dataTableTypeOptions,
            ['pageLength' => 10, 'order' => [[1, 'asc']]]
        )->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }
        return $this->render('cible/list.html.twig', [
            'form' => $form->createView(),
            'datatable' => $table,
            'menu' => "cible"
        ]);
    }

    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route(
        path: 'attaque/delete/{attaque}',
        name: 'deleteCible',
        methods: ['POST', 'GET']
    )]
    public function deleteCible(Request $request, Cible $cible)
    {
            try {
                $this->em->remove($cible);
                $this->em->flush();
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
                throw $e;
            }
            $this->addFlash('success', 'Suppression éffectuée');

            return $this->redirectToRoute('cible');
    }

    #[IsGranted('ROLE_SUPER_ADMIN', message: "Vous n'avez pas accès à cette partie de l'application!")]
    #[Route(path: '/materiel/list/{materiel}', name: 'materiel', methods: ['GET', 'POST'])]
    public function materielList(Request $request, DataTableFactory $dataTableFactory, Materiaux $materiel = null)
    {
        $parameterRep = $this->em->getRepository(Materiaux::class);
        $form = $this->createForm(MaterielFormType::class, $materiel, [
            'action' => $this->generateUrl('materiel', ['materiel' => $materiel == null ? $materiel : $materiel->getId()]),
            'method' => 'POST'
        ])->add('save', SubmitType::class, [
            'label' => $materiel == null ? 'Ajouter' : 'Modifier',
            'attr' => ['class' => 'btn btn-square btn-success']
        ]);

        if($request->isMethod(Request::METHOD_POST))
        {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $materiel = $form->getData();
                $materiel->setUser($this->getUser());
                $this->em->persist($materiel);
                $this->em->flush();
                $this->addFlash('success', 'Enrégistrement bien éffectué');
                return $this->redirectToRoute('materiel');
            }
        }

        $dataTableTypeOptions = [
            'dataTableName' => 'materielTable',
            'fields' => ['all'],
            'visible_fields' => ['all']
        ];
        $table = $dataTableFactory->createFromType(
            MaterielDataTableType::class,
            $dataTableTypeOptions,
            ['pageLength' => 10, 'order' => [[1, 'asc']]]
        )->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }
        return $this->render('materiel/list.html.twig', [
            'form' => $form->createView(),
            'datatable' => $table,
            'menu' => "materiel"
        ]);
    }

    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route(
        path: 'materiel/delete/{materiel}',
        name: 'deleteMateriel',
        methods: ['POST', 'GET']
    )]
    public function deleteMateriel(Request $request, Materiaux $materiel)
    {
            try {
                $this->em->remove($materiel);
                $this->em->flush();
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
                throw $e;
            }
            $this->addFlash('success', 'Suppression éffectuée');

            return $this->redirectToRoute('materiel');
    }

    #[IsGranted('ROLE_SUPER_ADMIN', message: "Vous n'avez pas accès à cette partie de l'application!")]
    #[Route(path: '/materiel/attaque/list/{materielAttaque}', name: 'materielAttaque', methods: ['GET', 'POST'])]
    public function materielAttaqueList(Request $request, DataTableFactory $dataTableFactory, MaterielAttaque $materielAttaque = null)
    {
        $parameterRep = $this->em->getRepository(MaterielAttaque::class);
        $form = $this->createForm(MaterielAttaqueFormType::class, $materielAttaque, [
            'action' => $this->generateUrl('materielAttaque', ['materielAttaque' => $materielAttaque == null ? $materielAttaque : $materielAttaque->getId()]),
            'method' => 'POST'
        ])->add('save', SubmitType::class, [
            'label' => $materielAttaque == null ? 'Ajouter' : 'Modifier',
            'attr' => ['class' => 'btn btn-square btn-success']
        ]);

        if($request->isMethod(Request::METHOD_POST))
        {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $materielAttaque = $form->getData();
                $materielAttaque->setUser($this->getUser());
                $this->em->persist($materielAttaque);
                $this->em->flush();
                $this->addFlash('success', 'Enrégistrement bien éffectué');
                return $this->redirectToRoute('materielAttaque');
            }
        }

        $dataTableTypeOptions = [
            'dataTableName' => 'materielAttaqueTable',
            'fields' => ['all'],
            'visible_fields' => ['all']
        ];
        $table = $dataTableFactory->createFromType(
            MaterielAttaqueDataTableType::class,
            $dataTableTypeOptions,
            ['pageLength' => 10, 'order' => [[1, 'asc']]]
        )->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }
        return $this->render('materielAttaque/list.html.twig', [
            'form' => $form->createView(),
            'datatable' => $table,
            'menu' => "materielAttaque"
        ]);
    }

    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route(
        path: 'materiel/attaque/delete/{materielAttaque}',
        name: 'deleteMaterielAttaque',
        methods: ['POST', 'GET']
    )]
    public function deleteMaterielAttaque(Request $request, MaterielAttaque $materielAttaque)
    {
            try {
                $this->em->remove($materielAttaque);
                $this->em->flush();
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
                throw $e;
            }
            $this->addFlash('success', 'Suppression éffectuée');

            return $this->redirectToRoute('materielAttaque');
    }

    #[IsGranted('ROLE_SUPER_ADMIN', message: "Vous n'avez pas accès à cette partie de l'application!")]
    #[Route(path: '/moyen/attaque/list/{moyenAttaque}', name: 'moyenAttaque', methods: ['GET', 'POST'])]
    public function moyenAttaqueList(Request $request, DataTableFactory $dataTableFactory, MoyenAttaque $moyenAttaque = null)
    {
        $parameterRep = $this->em->getRepository(MaterielAttaque::class);
        $form = $this->createForm(MoyenAttaqueFormType::class, $moyenAttaque, [
            'action' => $this->generateUrl('moyenAttaque', ['moyenAttaque' => $moyenAttaque == null ? $moyenAttaque : $moyenAttaque->getId()]),
            'method' => 'POST'
        ])->add('save', SubmitType::class, [
            'label' => $moyenAttaque == null ? 'Ajouter' : 'Modifier',
            'attr' => ['class' => 'btn btn-square btn-success']
        ]);

        if($request->isMethod(Request::METHOD_POST))
        {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $moyenAttaque = $form->getData();
                $moyenAttaque->setUser($this->getUser());
                $this->em->persist($moyenAttaque);
                $this->em->flush();
                $this->addFlash('success', 'Enrégistrement bien éffectué');

                return $this->redirectToRoute('moyenAttaque');
            }
        }

        $dataTableTypeOptions = [
            'dataTableName' => 'moyenAttaqueTable',
            'fields' => ['all'],
            'visible_fields' => ['all']
        ];
        $table = $dataTableFactory->createFromType(
            MoyenAttaqueDataTableType::class,
            $dataTableTypeOptions,
            ['pageLength' => 10, 'order' => [[1, 'asc']]]
        )->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }
        return $this->render('moyenAttaque/list.html.twig', [
            'form' => $form->createView(),
            'datatable' => $table,
            'menu' => "moyenAttaque"
        ]);
    }

    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route(
        path: 'moyen/attaque/delete/{moyenAttaque}',
        name: 'deleteMoyenAttaque',
        methods: ['POST', 'GET']
    )]
    public function deleteMoyenAttaque(Request $request, MoyenAttaque $moyenAttaque)
    {
            try {
                $this->em->remove($moyenAttaque);
                $this->em->flush();
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
                throw $e;
            }
            $this->addFlash('success', 'Suppression éffectuée');

            return $this->redirectToRoute('moyenAttaque');
    }

    #[IsGranted('ROLE_SUPER_ADMIN', message: "Vous n'avez pas accès à cette partie de l'application!")]
    #[Route(path: '/perpetrateur/list/{perpetrateur}', name: 'perpetrateur', methods: ['GET', 'POST'])]
    public function moyenPerpetrateurList(Request $request, DataTableFactory $dataTableFactory, Perpetrateurs $perpetrateur = null)
    {
        $parameterRep = $this->em->getRepository(Perpetrateurs::class);
        $form = $this->createForm(PerpetrateurFormType::class, $perpetrateur, [
            'action' => $this->generateUrl('perpetrateur', ['perpetrateur' => $perpetrateur == null ? $perpetrateur : $perpetrateur->getId()]),
            'method' => 'POST'
        ])->add('save', SubmitType::class, [
            'label' => $perpetrateur == null ? 'Ajouter' : 'Modifier',
            'attr' => ['class' => 'btn btn-square btn-success']
        ]);

        if($request->isMethod(Request::METHOD_POST))
        {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $perpetrateur = $form->getData();
                $perpetrateur->setUser($this->getUser());
                $this->em->persist($perpetrateur);
                $this->em->flush();
                $this->addFlash('success', 'Enrégistrement bien éffectué');
                return $this->redirectToRoute('perpetrateur');
            }
        }

        $dataTableTypeOptions = [
            'dataTableName' => 'perpetrateurTable',
            'fields' => ['all'],
            'visible_fields' => ['all']
        ];
        $table = $dataTableFactory->createFromType(
            PerpetrateurDataTableType::class,
            $dataTableTypeOptions,
            ['pageLength' => 10, 'order' => [[1, 'asc']]]
        )->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }
        return $this->render('perpetrateur/list.html.twig', [
            'form' => $form->createView(),
            'datatable' => $table,
            'menu' => "perpetrateur"
        ]);
    }

    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route(
        path: 'perpetrateur/delete/{perpetrateur}',
        name: 'deletePerpetrateur',
        methods: ['POST', 'GET']
    )]
    public function deletePerpetrateur(Request $request, Perpetrateurs $perpetrateur)
    {
            try {
                $this->em->remove($perpetrateur);
                $this->em->flush();
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
                throw $e;
            }
            $this->addFlash('success', 'Suppression éffectuée');

            return $this->redirectToRoute('perpetrateur');
    }

    #[IsGranted('ROLE_SUPER_ADMIN', message: "Vous n'avez pas accès à cette partie de l'application!")]
    #[Route(path: '/espace/list/{espace}', name: 'espace', methods: ['GET', 'POST'])]
    public function espaceList(Request $request, DataTableFactory $dataTableFactory, Espace $espace = null)
    {
        $parameterRep = $this->em->getRepository(Espace::class);
        $form = $this->createForm(EspaceFormType::class, $espace, [
            'action' => $this->generateUrl('espace', ['espace' => $espace == null ? $espace : $espace->getId()]),
            'method' => 'POST'
        ])->add('save', SubmitType::class, [
            'label' => $espace == null ? 'Ajouter' : 'Modifier',
            'attr' => ['class' => 'btn btn-square btn-success']
        ]);

        if($request->isMethod(Request::METHOD_POST))
        {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $cible = $form->getData();
                $cible->setUser($this->getUser());
                $this->em->persist($cible);
                $this->em->flush();
                $this->addFlash('success', 'Enrégistrement bien éffectué');

                return $this->redirectToRoute('espace');
            }
        }

        $dataTableTypeOptions = [
            'dataTableName' => 'espaceTable',
            'fields' => ['all'],
            'visible_fields' => ['all']
        ];
        $table = $dataTableFactory->createFromType(
            EspaceDataTableType::class,
            $dataTableTypeOptions,
            ['pageLength' => 10, 'order' => [[1, 'asc']]]
        )->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }
        return $this->render('espace/list.html.twig', [
            'form' => $form->createView(),
            'datatable' => $table,
            'menu' => "espace"
        ]);
    }

    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route(
        path: 'attaque/delete/{espace}',
        name: 'deleteEspace',
        methods: ['POST', 'GET']
    )]
    public function deleteEspace(Request $request, Espace $espace)
    {
            try {
                $this->em->remove($espace);
                $this->em->flush();
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
                throw $e;
            }
            $this->addFlash('success', 'Suppression éffectuée');

            return $this->redirectToRoute('espace');
    }
}
