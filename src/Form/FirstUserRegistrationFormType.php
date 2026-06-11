<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class FirstUserRegistrationFormType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('name', TextType::class,
                ['required' => true, 'label' => 'Nom', 'attr' => ['placeholder' => 'Nom de famille']])
            ->add('prenoms', TextType::class,
                ['required' => true, 'label' => 'Prenoms', 'attr' => ['placeholder' => 'Votre ou vos prenom(s)']])
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
