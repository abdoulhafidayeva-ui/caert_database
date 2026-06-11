<?php

namespace App\Form;

use App\Entity\AppParam;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AppParamType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class,
            ['required' => true, 'label' => 'Nom', 'attr' => ['placeholder' => 'Nom de l\'application']])
            ->add('email', EmailType::class,
            ['required' => true, 'label' => 'Email', 'attr' => ['placeholder' => 'Email de l\'application']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AppParam::class,
        ]);
    }
}
