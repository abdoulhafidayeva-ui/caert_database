<?php

namespace App\Form;

use App\Entity\AppParam;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class AppParamType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => true,
                'label' => 'install.field.app_name',
                'attr' => ['placeholder' => $this->translator->trans('install.field.app_name_placeholder')],
            ])
            ->add('email', EmailType::class, [
                'required' => true,
                'label' => 'install.field.app_email',
                'attr' => ['placeholder' => $this->translator->trans('install.field.app_email_placeholder')],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AppParam::class,
            'translation_domain' => 'messages',
        ]);
    }
}
