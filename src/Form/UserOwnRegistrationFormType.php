<?php

namespace App\Form;

use App\Entity\Pays;
use App\Entity\Region;
use App\Entity\User;
use App\Repository\PaysRepository;
use App\Repository\RegionRepository;
use App\Service\Security\UserProfile;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserOwnRegistrationFormType extends AbstractType
{
    public function __construct(
        private readonly PaysRepository $paysRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $ph = fn (string $key): string => $this->translator->trans($key);

        $builder
            ->add('name', TextType::class, ['required' => true, 'label' => 'user.field.name'])
            ->add('prenoms', TextType::class, ['required' => true, 'label' => 'user.field.firstnames'])
            ->add('fonction', TextType::class, ['required' => true, 'label' => 'user.field.function'])
            ->add('email', EmailType::class, ['required' => true, 'label' => 'user.field.email'])
            ->add('plainPassword', RepeatedType::class, [
                'mapped' => false,
                'type' => PasswordType::class,
                'invalid_message' => 'auth.password_mismatch',
                'options' => ['attr' => ['class' => 'password-field']],
                'required' => true,
                'first_options' => [
                    'label' => 'user.field.password',
                    'attr' => ['placeholder' => $ph('user.field.password')],
                ],
                'second_options' => [
                    'label' => 'user.field.password_confirm',
                    'attr' => ['placeholder' => $ph('user.field.password_confirm')],
                ],
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 6, 'max' => 4096]),
                ],
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
                'required' => true,
            ])
            ->add('organisation', TextType::class, [
                'required' => true,
                'label' => 'user.field.organisation',
                'attr' => ['placeholder' => $ph('user.field.organisation')],
            ])
        ;

        $formModifier = function (FormInterface $form, ?Region $region = null) {
            $pays = $region === null
                ? []
                : $this->paysRepository->findUniqueByRegionLibelle($region->getLibelle());

            $form->add('pays', EntityType::class, [
                'class' => Pays::class,
                'choices' => $pays,
                'required' => true,
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
                if ($parent instanceof FormInterface) {
                    $formModifier($parent, $region);
                }
            }
        );

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $user = $event->getData();
            if (!$user instanceof User || !$event->getForm()->isValid()) {
                return;
            }

            if ($user->getRegion() === null && $user->getPays()?->getRegion() !== null) {
                $user->setRegion($user->getPays()->getRegion());
            }

            $user->setProfil(UserProfile::FOCAL);
            $user->setRoles(UserProfile::resolveRoles(UserProfile::FOCAL));
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'translation_domain' => 'messages',
        ]);
    }
}
