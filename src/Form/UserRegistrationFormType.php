<?php

namespace App\Form;

use App\Entity\Pays;
use App\Entity\Region;
use App\Entity\User;
use App\Repository\PaysRepository;
use App\Repository\RegionRepository;
use App\Service\Locale\SupportedLocales;
use App\Service\Security\UserProfile;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserRegistrationFormType extends AbstractType
{
    public function __construct(
        private readonly PaysRepository $paysRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['required' => true, 'label' => 'user.field.name'])
            ->add('prenoms', TextType::class, ['required' => true, 'label' => 'user.field.firstnames'])
            ->add('fonction', TextType::class, ['required' => true, 'label' => 'user.field.function'])
            ->add('email', EmailType::class, ['required' => true, 'label' => 'user.field.email'])
            ->add('locale', ChoiceType::class, [
                'label' => 'user.field.locale',
                'choices' => SupportedLocales::activeChoices(),
                'required' => false,
                'placeholder' => 'common.choose',
                'choice_translation_domain' => 'messages',
            ])
            ->add('region', EntityType::class, [
                'label' => 'user.field.region',
                'class' => Region::class,
                'query_builder' => fn (RegionRepository $er) => $er->createQueryBuilder('r')->orderBy('r.libelle', 'ASC'),
                'choice_label' => 'libelle',
                'required' => true,
                'placeholder' => 'common.choose',
            ])
            ->add('pays', ChoiceType::class, [
                'placeholder' => 'user.field.country_region_first',
                'required' => false,
            ])
            ->add('profil', ChoiceType::class, [
                'label' => 'user.field.profile',
                'placeholder' => 'common.choose',
                'required' => true,
                'choices' => UserProfile::choices(),
                'choice_translation_domain' => 'messages',
            ])
            ->add('organisation', TextType::class, [
                'required' => true,
                'label' => 'user.field.organisation',
            ])
            ->add('enable', CheckboxType::class, [
                'required' => false,
                'label' => 'user.field.active',
                'help' => 'user.field.active_help',
            ])
        ;

        if ($options['show_super_admin_option']) {
            $builder->add('isSuperAdmin', CheckboxType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'user.field.super_admin',
                'help' => 'user.field.super_admin_help',
            ]);
        }

        $formModifier = function (FormInterface $form, ?Region $region = null, ?User $user = null) {
            $pays = $region === null
                ? []
                : $this->paysRepository->findUniqueByRegionLibelle($region->getLibelle());

            $form->add('pays', EntityType::class, [
                'class' => Pays::class,
                'choices' => $pays,
                'required' => UserProfile::normalize((string) ($user?->getProfil() ?? UserProfile::FOCAL)) === UserProfile::FOCAL,
                'choice_label' => 'libelle',
                'placeholder' => 'common.choose',
                'attr' => ['class' => 'custom-select'],
                'label' => 'user.field.country',
            ]);
        };

        $builder->get('region')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $region = $event->getForm()->getData();
                $parent = $event->getForm()->getParent();
                $user = $parent?->getData();
                if ($parent instanceof FormInterface) {
                    $formModifier($parent, $region, $user instanceof User ? $user : null);
                }
            }
        );

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) use ($formModifier, $options) {
            $user = $event->getData();
            $form = $event->getForm();

            if ($form->has('enable')) {
                // Création : actif par défaut ; édition : valeur existante (null → inactif)
                if (!$user instanceof User || $user->getId() === null) {
                    $form->get('enable')->setData(true);
                } else {
                    $form->get('enable')->setData($user->getEnable() === true);
                }
            }

            if (!$user instanceof User) {
                return;
            }

            if ($options['show_super_admin_option'] && $form->has('isSuperAdmin')) {
                $form->get('isSuperAdmin')->setData(UserProfile::isSuperAdmin($user));
            }

            if ($user->getProfil() !== null) {
                $user->setProfil(UserProfile::normalize($user->getProfil()));
            }

            $region = $user->getRegion() ?? $user->getPays()?->getRegion();
            if ($region !== null) {
                $formModifier($form, $region, $user);
            }
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($options) {
            $user = $event->getData();
            if (!$user instanceof User || !$event->getForm()->isValid()) {
                return;
            }

            $profil = UserProfile::normalize($user->getProfil());
            $user->setProfil($profil);

            if ($user->getRegion() === null && $user->getPays()?->getRegion() !== null) {
                $user->setRegion($user->getPays()->getRegion());
            }

            $isSuperAdmin = $options['show_super_admin_option']
                && $event->getForm()->has('isSuperAdmin')
                && $event->getForm()->get('isSuperAdmin')->getData() === true;

            $user->setRoles(UserProfile::resolveRoles($profil, $isSuperAdmin));

            // Checkbox non cochée → false (pas null)
            if ($event->getForm()->has('enable')) {
                $user->setEnable($event->getForm()->get('enable')->getData() === true);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'translation_domain' => 'messages',
            'show_super_admin_option' => false,
        ]);
        $resolver->setAllowedTypes('show_super_admin_option', 'bool');
    }
}
