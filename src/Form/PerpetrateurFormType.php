<?php

namespace App\Form;

use App\Entity\Perpetrateurs;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class PerpetrateurFormType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('libelle', TextType::class, [
            'required' => true,
            'label' => 'referential.field.label',
            'attr' => ['placeholder' => $this->translator->trans('referential.field.label_placeholder')],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Perpetrateurs::class,
            'translation_domain' => 'messages',
        ]);
    }
}
