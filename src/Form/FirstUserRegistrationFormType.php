<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class FirstUserRegistrationFormType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $ph = fn (string $key): string => $this->translator->trans($key);

        $builder
            ->add('name', TextType::class, ['required' => true, 'label' => 'user.field.name'])
            ->add('prenoms', TextType::class, ['required' => true, 'label' => 'user.field.firstnames'])
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'translation_domain' => 'messages',
        ]);
    }
}
