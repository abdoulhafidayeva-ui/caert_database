<?php

namespace App\Form;

use App\Entity\AllData;
use App\Entity\Attaque;
use App\Entity\Cible;
use App\Entity\Espace;
use App\Entity\Materiaux;
use App\Entity\MaterielAttaque;
use App\Entity\MoyenAttaque;
use App\Entity\Pays;
use App\Entity\Perpetrateurs;
use App\Entity\Region;
use App\Entity\User;
use App\Repository\PaysRepository;
use App\Repository\RegionRepository;
use App\Service\Security\IncidentCountryGuard;
use App\Service\UserManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class AllDataFormType extends AbstractType
{
    use IncidentRegionPaysFormTrait;

    public function __construct(
        private readonly UserManager $userManager,
        private readonly Security $security,
        private readonly IncidentCountryGuard $countryGuard,
        private readonly RegionRepository $regionRepository,
        private readonly PaysRepository $paysRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $ph = fn (string $key): string => $this->translator->trans($key);

        $builder
            ->add('espace', EntityType::class, [
                'label' => 'incident.field.space',
                'class' => Espace::class,
                'required' => true,
                'placeholder' => 'common.choose',
            ])
            ->add('regions', EntityType::class, [
                'label' => 'incident.field.region',
                'class' => Region::class,
                'mapped' => false,
                'choices' => $this->regionRepository->findAllUniqueByLibelle(),
                'choice_label' => 'libelle',
                'multiple' => false,
                'expanded' => false,
                'required' => false,
                'placeholder' => 'common.choose',
                'by_reference' => false,
            ])
            ->add('pays', ChoiceType::class, [
                'placeholder' => 'incident.field.country_region_first',
                'required' => true,
            ])
            ->add('localite', TextType::class, [
                'required' => true,
                'label' => 'incident.field.locality',
                'attr' => ['placeholder' => $ph('incident.field.locality')],
            ])
            ->add('perpetrateur', EntityType::class, [
                'label' => 'incident.field.terrorist_group',
                'class' => Perpetrateurs::class,
                'multiple' => false,
                'required' => true,
                'placeholder' => 'common.choose',
            ])
            ->add('details', TextareaType::class, [
                'required' => true,
                'label' => 'incident.field.details',
                'attr' => ['placeholder' => $ph('incident.field.details_placeholder')],
            ])
            ->add('moyenAttaque', EntityType::class, [
                'label' => 'incident.field.attack_means',
                'class' => MoyenAttaque::class,
                'multiple' => false,
                'required' => true,
                'placeholder' => 'common.choose',
            ])
            ->add('attaque', EntityType::class, [
                'label' => 'incident.field.attack_type',
                'class' => Attaque::class,
                'multiple' => false,
                'required' => true,
                'placeholder' => 'common.choose',
            ])
            ->add('dateAttaque', DateType::class, [
                'label' => 'incident.field.attack_date',
                'required' => true,
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'dd/MM/yyyy',
                'attr' => [
                    'class' => 'js-datepicker',
                    'autocomplete' => 'off',
                    'placeholder' => $ph('incident.field.date_placeholder'),
                ],
            ])
            ->add('materieaux', EntityType::class, [
                'label' => 'incident.field.attack_materials',
                'class' => Materiaux::class,
                'multiple' => false,
                'required' => true,
                'placeholder' => 'common.choose',
            ])
            ->add('cible', EntityType::class, [
                'label' => 'incident.field.main_target',
                'class' => Cible::class,
                'multiple' => false,
                'required' => true,
                'placeholder' => 'common.choose',
            ])
            ->add('mortSecuriteMilitaire', IntegerType::class, [
                'required' => true,
                'label' => 'incident.field.deaths_military',
                'attr' => ['placeholder' => $ph('incident.field.deaths_military')],
            ])
            ->add('mortCivil', IntegerType::class, [
                'required' => true,
                'label' => 'incident.field.deaths_civilian',
                'attr' => ['placeholder' => $ph('incident.field.deaths_civilian')],
            ])
            ->add('mortTerroriste', IntegerType::class, [
                'required' => true,
                'label' => 'incident.field.deaths_terrorist',
                'attr' => ['placeholder' => $ph('incident.field.deaths_terrorist')],
            ])
            ->add('disparuSecuriteMilitaire', IntegerType::class, [
                'required' => true,
                'label' => 'incident.field.missing_military',
                'attr' => ['placeholder' => $ph('incident.field.missing_military')],
            ])
            ->add('disparuCivil', IntegerType::class, [
                'required' => true,
                'label' => 'incident.field.missing_civilian',
                'attr' => ['placeholder' => $ph('incident.field.missing_civilian')],
            ])
            ->add('disparuTerroriste', IntegerType::class, [
                'required' => true,
                'label' => 'incident.field.missing_terrorist',
                'attr' => ['placeholder' => $ph('incident.field.missing_terrorist')],
            ])
            ->add('blesseSecuriteMilitaire', IntegerType::class, [
                'required' => true,
                'label' => 'incident.field.injured_military',
                'attr' => ['placeholder' => $ph('incident.field.injured_military')],
            ])
            ->add('blesseCivil', IntegerType::class, [
                'required' => true,
                'label' => 'incident.field.injured_civilian',
                'attr' => ['placeholder' => $ph('incident.field.injured_civilian')],
            ])
            ->add('blesseTerroriste', IntegerType::class, [
                'required' => true,
                'label' => 'incident.field.injured_terrorist',
                'attr' => ['placeholder' => $ph('incident.field.injured_terrorist')],
            ])
            ->add('otages', IntegerType::class, [
                'required' => true,
                'label' => 'incident.field.hostages',
                'attr' => ['placeholder' => $ph('incident.field.hostages')],
            ])
            ->add('liberes', IntegerType::class, [
                'required' => true,
                'label' => 'incident.field.released',
                'attr' => ['placeholder' => $ph('incident.field.released')],
            ])
            ->add('terroristeArretes', IntegerType::class, [
                'required' => true,
                'label' => 'incident.field.arrested',
                'attr' => ['placeholder' => $ph('incident.field.arrested')],
            ])
            ->add('materielAttaque', EntityType::class, [
                'label' => 'incident.field.recovered_materials',
                'class' => MaterielAttaque::class,
                'multiple' => false,
                'required' => true,
                'placeholder' => 'common.choose',
            ])
            ->add('autres', TextareaType::class, [
                'required' => true,
                'label' => 'incident.field.other_victims',
                'attr' => ['placeholder' => $ph('incident.field.other_victims')],
            ])
            ->add('remarque', TextType::class, [
                'required' => true,
                'label' => 'incident.field.remark',
                'attr' => ['placeholder' => $ph('incident.field.remark')],
            ])
        ;

        $formModifier = function (FormInterface $form, ?Region $regions = null, ?Pays $selectedPays = null) {
            $pays = null === $regions || $regions->getLibelle() === null
                ? []
                : $this->paysRepository->findUniqueByRegionLibelle($regions->getLibelle());

            $form->add('pays', EntityType::class, [
                'class' => Pays::class,
                'choices' => $pays,
                'data' => $selectedPays,
                'required' => true,
                'choice_label' => 'libelle',
                'placeholder' => 'common.choose',
                'attr' => ['class' => 'custom-select'],
                'label' => 'incident.field.country',
            ]);
        };

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($formModifier) {
            $user = $this->security->getUser();
            if ($user instanceof User && $this->countryGuard->isCountryRestricted($user)) {
                return;
            }

            $data = $event->getData();
            if (!\is_array($data)) {
                return;
            }

            $region = $this->resolveRegionFromSubmit($data['regions'] ?? null);
            $canonicalPays = $this->resolveCanonicalPaysFromSubmit($data['pays'] ?? null, $region);

            $formModifier($event->getForm(), $region, $canonicalPays);

            if ($region !== null) {
                $data['regions'] = (string) $region->getId();
            }
            if ($canonicalPays !== null) {
                $data['pays'] = (string) $canonicalPays->getId();
            }
            $event->setData($data);
        });

        IncidentFormCountryConfigurator::apply($builder, $this->security, $this->countryGuard);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AllData::class,
            'translation_domain' => 'messages',
        ]);
    }
}
