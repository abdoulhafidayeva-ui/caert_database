<?php

namespace App\Form;

use App\Entity\Pays;
use App\Entity\Region;
use App\Entity\User;
use App\Repository\PaysRepository;
use App\Repository\RegionRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Service\UserManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserOwnRegistrationFormType extends AbstractType
{

    public function __construct(
        private readonly UserManager $userManager,
        private readonly PaysRepository $paysRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('name', TextType::class,
                ['required' => true, 'label' => 'Nom', 'attr' => ['placeholder' => 'Nom de famille']])
            ->add('prenoms', TextType::class,
                ['required' => true, 'label' => 'Prenoms', 'attr' => ['placeholder' => 'Votre ou vos prenom(s)']])
            ->add('fonction', TextType::class,
                    ['required' => true, 'label' => 'Fonction', 'attr' => ['placeholder' => 'Votre fonction']])
            ->add('email', EmailType::class,
                ['required' => true, 'label' => 'Email', 'attr' => ['placeholder' => 'adresse mail']])
            ->add('plainPassword', RepeatedType::class,
                [
                    'mapped' => false,
                    'type' => PasswordType::class,
                    'invalid_message' => 'Les mots de passe doivent correspondre',
                    'options' => ['attr' => ['class' => 'password-field']],
                    'required' => true,
                    'first_options'  => ['label' => 'Mot de passe','attr' => ['placeholder' => 'Mot de passe']],
                    'second_options' => ['label' => 'Confirmez le mot de passe', 'attr' => ['placeholder' => 'Repetez le mot de passe']],
                    'constraints' => [
                        new NotBlank(),
                        new Length(['min' => 6, 'max' => 4096])
                    ]
                ])
            ->add('regions', EntityType::class, [
                'label' => 'Région',
                'class' => Region::class,
                'mapped' => false,
                'query_builder' => function(RegionRepository $er){
                return $er->createQueryBuilder('r')
                ->orderBy('r.libelle', 'ASC');
                },
                'choice_value' => 'libelle',
                'choice_label' => 'libelle',
                'multiple' => false,
                'expanded' => false,
                'required'    => false,
                'placeholder' => 'Choisir',
                'by_reference' => false
            ])
            ->add('pays', ChoiceType::class, [
                'placeholder' => 'Pays (choisir une région)',
                'required'    => true
            ])
            ->add('profil', ChoiceType::class, [
                'label' => 'Profil',
                'placeholder' => 'Choisir',
                'required' => true,
                'choices'=>$this->getProfils()
            ])
            ->add('organisation', TextType::class,
                ['required' => true, 'label' => 'Organisation', 'attr' => ['placeholder' => 'Organisation']])
        ;

        $formModifier = function (FormInterface $form, ?Region $regions = null) {
            $pays = null === $regions || $regions->getLibelle() === null
                ? []
                : $this->paysRepository->findUniqueByRegionLibelle($regions->getLibelle());

            $form->add('pays', EntityType::class, [
                'class' => Pays::class,
                'choices' => $pays,
                'required' => false,
                'choice_label' => 'libelle',
                'placeholder' => 'Choisir',
                'attr' => ['class' => 'custom-select'],
                'label' => 'Pays'
            ]);
        };

        $builder->get('regions')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $region = $event->getForm()->getData();
                $formModifier($event->getForm()->getParent(), $region);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }


    private function normalizeRoles($roles)
    {
        $rolesNormalized = [];
        foreach ($roles as $key => $role) {
            $rolesNormalized[$role] = $role;
        }
        return $rolesNormalized;
    }

    private function getProfils()
    {
        $choices = User::PROFILS;
        $output = [];
        foreach ($choices as $key => $value) {
            $output[$value] = $key;
        }
        return $output;
    }
}
