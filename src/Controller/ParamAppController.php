<?php

namespace App\Controller;

use App\Form\AppParamType;
use App\Repository\AppParamRepository;
use App\Form\FirstUserRegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ParamAppController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route(path: '/install', name: 'app_install')]
    public function installController(AppParamRepository $param, UserRepository $userRepository, Request $request): Response
    {
        if ($param->findAll() != []) {
            if ($userRepository->findAll() == []) {
                return $this->redirectToRoute('app_first_user');
            }
            return $this->redirectToRoute('app_login');
        }
        $form = $this->createForm(AppParamType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $app = $form->getData();
            $this->em->persist($app);
            $this->em->flush();
            return $this->redirectToRoute('app_first_user');
        }
        return $this->render('parametrage/app_name.html.twig',['form' => $form->createView()]);
    }

    #[Route(path: '/first-user', name: 'app_first_user')]
    public function first(AppParamRepository $param, UserRepository $userRepository, Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        if ($userRepository->findAll() != []) {
            return $this->redirectToRoute('app_login');
        }
        if ($param->findAll() == []) {
            return $this->redirectToRoute('app_install');
        }

        $form = $this->createForm(FirstUserRegistrationFormType::class);

        $form->handleRequest($request);
        // dd($form->getErrors(true));
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            $user->setTokenCreatedAt(new \DateTime());

            $defaultRoles = ["ROLE_SUPER_ADMIN"];
            $plainPassword = $form['plainPassword']->getData();
            $password = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($password);
            $user->setToken(null);
            $user->setIsVerified(true);
            $user->setEnable(true);
            $user->setRoles($defaultRoles);

            $this->em->persist($user);
            $this->em->flush();
            $this->addFlash('success', 'flash.install_complete');
        }
        return $this->render('parametrage/app_first_user.html.twig',['form' => $form->createView()]);
    }
}
