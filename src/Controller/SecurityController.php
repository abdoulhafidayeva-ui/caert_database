<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserOwnRegistrationFormType;
use App\Form\UserRegistrationFormType;
use App\Form\UserPasswordFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;
use App\Notification\EmailNotification;
use App\Repository\AppParamRepository;
use App\Service\Locale\LocaleResolver;
use App\Service\TokenGenerator;
use App\Service\UserManager;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class SecurityController extends AbstractAppController
{
    public const  LAST_EMAIL = 'app_login_form_old_email';

    private $em;
    private $menu = "user";
    private $tokenGenerator;
    private $userRepository;
    private $userManager;
    private $notification;
    private $localeResolver;

    public function __construct(EntityManagerInterface $em, TokenGenerator $tokenGenerator, UserRepository $userRepository, UserManager $userManager , EmailNotification $notification, LocaleResolver $localeResolver)
    {
        $this->em = $em;
        $this->tokenGenerator = $tokenGenerator;
        $this->userRepository = $userRepository;
        $this->userManager = $userManager;
        $this->notification = $notification;
        $this->localeResolver = $localeResolver;
    }

    #[IsGranted('ROLE_SUPER_ADMIN', message: "security.access_users")]
    #[Route(path: '/users', name: 'app_user_list', methods: ['GET'])]
    public function list(PaginatorInterface $paginator, Request $request): Response
    {
        $menu = 'userList';
        $query = $this->userRepository->findBy(array(), array('name' => 'ASC'));

        $users = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            15
        );
        return $this->render('security/user_list.html.twig',['users'=> $users, 'menu' => $menu]);
    }



    #[IsGranted('ROLE_SUPER_ADMIN', message: "security.access_app")]
    #[Route(path: '/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request): Response
    {
        $menu = 'userRegister';
        $form = $this->createForm(UserRegistrationFormType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            $token = $this->tokenGenerator->generateToken();
            $user->setTokenCreatedAt(new \DateTime());
            $user->setToken($token);
            $user->setNotifyBy(0);
            if (!$user->getLocale()) {
                $user->setLocale($this->localeResolver->resolveFromCountry($user->getPays()));
            }
            $this->em->persist($user);
            $this->em->flush();

            try {
                $this->notification->notify($user);
                $this->addFlash('success', 'flash.user_created');
            } catch (TransportExceptionInterface) {
                $this->addFlash('warning', 'flash.user_created_no_mail');
            }
        }
        return $this->render('security/register.html.twig',['form' => $form->createView(), 'menu' => $menu]);
    }

    #[IsGranted('ROLE_SUPER_ADMIN', message: "security.access_app")]
    #[Route(path: '/register/{id}', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user): Response
    {

        $form = $this->createForm(UserRegistrationFormType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            $this->em->persist($user);
            $this->em->flush();

            $this->addFlash('success', 'flash.user_updated');
        }
        return $this->render('security/user_edit.html.twig',['user' => $user, 'form' => $form->createView(), 'menu' => $this->menu]);
    }

    #[Route(path: '/set-password/{token}', name: 'app_add_password', methods: ['GET', 'POST'])]
    public function set_password_form(Request $request, UserPasswordHasherInterface $passwordHasher, $token): Response
    {
        $user = $this->userRepository->findOneBy(array('token' => $token));

        if (!$user) {
            return $this->render('security/invalid_link.html.twig');
        }else{
            $actuel = new \DateTime();
            $tokenCreated = $user->getTokenCreatedAt();
            $laDiff = $tokenCreated->diff($actuel)->days;
            if ($laDiff > 24) {
                return $this->render('security/expired_link.html.twig');
            }
        }


        // dd($tokenCreated->diff($actuel)->h);

        $form = $this->createForm(UserPasswordFormType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $plainPassword = $form['plainPassword']->getData();

            $password = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($password);
            $user->setToken(null);
            $user->setIsVerified(true);
            $user->setEnable(true);
            $this->em->persist($user);
            $this->em->flush();

            $this->addFlash('success', 'flash.password_saved');
            return $this->redirectToRoute('app_login');
        }
        return $this->render('security/set_password.html.twig',['user' => $user,'form' => $form->createView()]);
    }

    #[Route(path: '/user/register', name: 'app_user_register', methods: ['GET', 'POST'])]
    public function user_register(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createForm(UserOwnRegistrationFormType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            $token = $this->tokenGenerator->generateToken();
            $user->setTokenCreatedAt(new \DateTime());
            $user->setToken($token);

            $defaultRoles = ["ROLE_USER"];
            $plainPassword = $form['plainPassword']->getData();
            $password = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($password);
            $user->setRoles($defaultRoles);
            $user->setNotifyBy(0);
            $this->em->persist($user);
            $this->em->flush();

            $this->notification->verify($user);
            $this->addFlash('success', 'flash.account_created_verify');
            return $this->render('security/attente_validation.html.twig',['user' => $user]);
        }
        return $this->render('security/user-register.html.twig',['form' => $form->createView()]);
    }

    #[Route(path: '/user/mail/verif/{id}', name: 'app_user_verif_resend', methods: ['GET', 'POST'])]
    public function user_mail_verif(Request $request, User $user): Response
    {
            $token = $this->tokenGenerator->generateToken();
            $user->setTokenCreatedAt(new \DateTime());
            $user->setToken($token);
            $this->em->persist($user);
            $this->em->flush();

            $this->notification->verify($user);
            $this->addFlash('success', 'flash.verification_resent');
            return $this->render('security/attente_validation.html.twig',['user' => $user]);
    }

    #[Route(path: '/verif-account/{token}', name: 'app_account_verif', methods: ['GET', 'POST'])]
    public function verif_account(Request $request, $token): Response
    {
        $user = $this->userRepository->findOneBy(array('token' => $token));

        if (!$user) {
            return $this->render('security/invalid_link.html.twig');
        }
        // else{
        //     $actuel = new \DateTime();
        //     $tokenCreated = $user->getTokenCreatedAt();
        //     $laDiff = $tokenCreated->diff($actuel)->days;
        //     if ($laDiff > 24) {
        //         return $this->render('security/expired_link.html.twig');
        //     }
        // }

        $user->setToken(null);
        $user->setIsVerified(true);
        $this->em->persist($user);
        $this->em->flush();

        $this->addFlash('success', 'flash.email_verified');
        return $this->redirectToRoute('app_login');
    }

    #[Route(path: '/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AppParamRepository $param): Response
    {
        if ($param->findAll() == []) {
            return $this->redirectToRoute('app_install');
        }else{
            if ($this->userRepository->findAll() == []) {
                return $this->redirectToRoute('app_first_user');
            }
        }
        return $this->render('security/login.html.twig');
    }

    #[Route(path: '/logout', name: 'app_logout', methods: ['GET'])]
    public function logout()
    {
        throw new \LogicException("This method can't be blank - it will be intercepted by the logout key on your firewall.");
    }

    #[IsGranted('ROLE_SUPER_ADMIN', message: "security.access_users")]
    #[Route(path: '/user/{id}/toggle-active', name: 'app_user_toggle_active', methods: ['POST'])]
    public function toggleActive(User $user, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('toggle'.$user->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'flash.invalid_csrf');

            return $this->redirectToRoute('app_user_list');
        }

        $currentUser = $this->getUser();
        if ($currentUser instanceof User && $currentUser->getId() === $user->getId()) {
            $this->addFlash('warning', 'flash.cannot_toggle_self');

            return $this->redirectToRoute('app_user_list');
        }

        if ($user->getIsVerified() !== true) {
            $this->addFlash('warning', 'flash.user_not_verified');

            return $this->redirectToRoute('app_user_list');
        }

        $activating = $user->getEnable() !== true;
        $user->setEnable($activating);
        $this->em->flush();

        $fullName = $user->getPrenoms().' '.$user->getName();
        $this->addFlash(
            'success',
            $activating
                ? $this->trans('flash.user_activated', ['%name%' => $fullName])
                : $this->trans('flash.user_suspended', ['%name%' => $fullName])
        );

        return $this->redirectToRoute('app_user_list');
    }

    #[IsGranted('ROLE_SUPER_ADMIN', message: "security.access_users")]
    #[Route(path: '/user/{id}/reset-password', name: 'app_user_reset_password', methods: ['POST'])]
    public function resetUserPassword(User $user, Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        if (!$this->isCsrfTokenValid('reset_password'.$user->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'flash.invalid_csrf');

            return $this->redirectToRoute('app_user_list');
        }

        $plainPassword = (string) $request->request->get('plainPassword', '');

        if ($plainPassword === '' || strlen($plainPassword) < 6) {
            $this->addFlash('danger', 'flash.password_too_short');

            return $this->redirectToRoute('app_user_list');
        }

        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
        $user->setToken(null);
        $user->setTokenCreatedAt(null);
        $user->setIsVerified(true);
        $user->setEnable(true);
        $this->em->flush();

        $fullName = $user->getPrenoms().' '.$user->getName();
        $message = $this->trans('flash.user_password_reset', ['%name%' => $fullName]);

        if ($request->request->getBoolean('send_email')) {
            try {
                $this->notification->sendNewPassword($user, $plainPassword);
                $message .= $this->trans('flash.user_password_reset_mail', ['%email%' => $user->getEmail()]);
            } catch (TransportExceptionInterface) {
                $this->addFlash(
                    'warning',
                    $message.$this->trans('flash.user_password_reset_mail_failed')
                );

                return $this->redirectToRoute('app_user_list');
            }
        }

        $this->addFlash('success', $message);

        return $this->redirectToRoute('app_user_list');
    }

    #[IsGranted('ROLE_SUPER_ADMIN', message: "security.access_users")]
    #[Route(path: '/user/delete/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(User $user, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$user->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'flash.invalid_csrf');

            return $this->redirectToRoute('app_user_list');
        }

        $currentUser = $this->getUser();
        if ($currentUser instanceof User && $currentUser->getId() === $user->getId()) {
            $this->addFlash('warning', 'flash.cannot_delete_self');

            return $this->redirectToRoute('app_user_list');
        }

        $this->em->remove($user);
        $this->em->flush();
        $this->addFlash('success', 'flash.user_deleted');

        return $this->redirectToRoute('app_user_list');
    }

    #[Route(path: '/forgotten-pass', name: 'app_forgotten_password', methods: ['GET', 'POST'])]
    public function forgottenPassword(Request $request): Response
    {
        return $this->render('security/forgotten_password.html.twig');
    }

    #[Route(path: '/forgotten-pass-{moyen}-{variable}', name: 'app_forgotten_password_code', options: ['expose' => true], methods: ['GET'])]
    public function forgottenPasswordCode($moyen, $variable): Response
    {
        $data = false;
        if ($moyen == 1 && $variable != "") {
            $user = $this->userRepository->findOneByEmail($variable);
            if($user != null){
                $token = $this->tokenGenerator->getCode();
                $user->setToken($token);
                $this->em->persist($user);
                $this->em->flush();

                $this->notification->sendCode($user);
                $data = true;
            }
        }else {
            // $phone = $request->get('phone');
            // $user = $this->userRepository->findOneByPhone($phone);
        }
        $response = new JsonResponse();
        $response->setData($data);
        return $response;
    }

    #[Route(path: '/forgotten-password-code-{moyen}-{variable}-{token}', name: 'app_forgotten_password_verif_code', options: ['expose' => true], methods: ['GET'])]
    public function forgottenPasswordVerifCode($moyen, $variable, $token): Response
    {
        $data = false;
        if ($moyen == 1 && $variable != "") {
            $user = $this->userRepository->findOneBy(array('email'=>$variable, 'token'=>$token));
            if($user != null){
                $token = $this->tokenGenerator->generateToken();
                $user->setTokenCreatedAt(new \DateTime());
                $user->setToken($token);
                $this->em->persist($user);
                $this->em->flush();
                $data = true;
            }
        }else {
            // $phone = $request->get('phone');
            // $user = $this->userRepository->findOneByPhone($phone);
        }
        $response = new JsonResponse();
        $response->setData($data);
        return $response;
    }


}
