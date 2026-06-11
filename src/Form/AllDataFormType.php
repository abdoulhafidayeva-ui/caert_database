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
use App\Repository\CibleRepository;
use App\Repository\MateriauxRepository;
use App\Repository\MaterielAttaqueRepository;
use App\Repository\MoyenAttaqueRepository;
use App\Repository\PaysRepository;
use App\Repository\PerpetrateursRepository;
use App\Repository\RegionRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Service\UserManager;
use App\Service\Security\IncidentCountryGuard;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Bundle\SecurityBundle\Security;

class AllDataFormType extends AbstractType
{
    use IncidentRegionPaysFormTrait;

    public function __construct(
        private readonly UserManager $userManager,
        private readonly Security $security,
        private readonly IncidentCountryGuard $countryGuard,
        private readonly RegionRepository $regionRepository,
        private readonly PaysRepository $paysRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $roles = $this->userManager->getRoles();
        $rolesChoices = [];
        if(count($roles)){
            asort($roles);
            $rolesChoices = $this->normalizeRoles($roles);
        }

        $builder
        ->add('espace', EntityType::class, [
            'label' => 'Espace',
            'class' => Espace::class,
            'required'    => true,
            'placeholder' => 'Choisir'
        ])
            ->add('regions', EntityType::class, [
                'label' => 'Région',
                'class' => Region::class,
                'mapped' => false,
                'choices' => $this->regionRepository->findAllUniqueByLibelle(),
                'choice_label' => 'libelle',
                'multiple' => false,
                'expanded' => false,
                'required'    => false,
                'placeholder' => 'Choisir',
                'by_reference' => false,
            ])
            ->add('pays', ChoiceType::class, [
                'placeholder' => 'Pays (choisir une région)',
                'required'    => true
            ])
            ->add('localite', TextType::class,
                    ['required' => true, 'label' => 'Localité', 'attr' => ['placeholder' => 'Localité']])
            ->add('perpetrateur', EntityType::class, [
                'label' => 'Groupe térroriste',
                'class' => Perpetrateurs::class,
                'multiple' => false,
                'required'    => true,
                'placeholder' => 'Choisir'
            ])
            ->add('details', TextareaType::class,
                ['required' => true, 'label' => 'Détail', 'attr' => ['placeholder' => 'Détails']])
                
            ->add('moyenAttaque', EntityType::class, [
                'label' => 'Moyens d\'attaque',
                'class' => MoyenAttaque::class,
                'multiple' => false,
                'required'    => true,
                'placeholder' => 'Choisir'
            ])
            ->add('attaque', EntityType::class, [
                'label' => 'Type d\'Attaque',
                'class' => Attaque::class,
                'multiple' => false,
                'required'    => true,
                'placeholder' => 'Choisir'
            ])
            ->add('dateAttaque', DateType::class, [
                'label' => 'Date d\'attaque',
                'required' => true,
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'dd/MM/yyyy',
                'attr' => ['class' => 'js-datepicker', 'autocomplete' => 'off', 'placeholder' => 'jj/mm/aaaa'],
            ])
            ->add('materieaux', EntityType::class, [
                'label' => 'Materiaux d\'attaque',
                'class' => Materiaux::class,
                'multiple' => false,
                'required'    => true,
                'placeholder' => 'Choisir'
            ])
            ->add('cible', EntityType::class, [
                'label' => 'Cible principale',
                'class' => Cible::class,
                'multiple' => false,
                'required'    => true,
                'placeholder' => 'Choisir'
            ])
            ->add('mortSecuriteMilitaire', IntegerType::class,
                ['required' => true, 'label' => 'Mort sécurité militaire', 'attr' => ['placeholder' => 'Mort Sécurité militaire']])
            ->add('mortCivil', IntegerType::class,
                    ['required' => true, 'label' => 'Mort civils', 'attr' => ['placeholder' => 'mort civil']])
            ->add('mortTerroriste', IntegerType::class,
                    ['required' => true, 'label' => 'Mort terroriste', 'attr' => ['placeholder' => 'mort terroriste']])
            ->add('disparuSecuriteMilitaire', IntegerType::class,
                    ['required' => true, 'label' => 'disparu securité militaire', 'attr' => ['placeholder' => 'disparu militaire']])
            ->add('disparuCivil', IntegerType::class,
                    ['required' => true, 'label' => 'disparu civil', 'attr' => ['placeholder' => 'disparu civil']])
            ->add('disparuTerroriste', IntegerType::class,
                    ['required' => true, 'label' => 'disparu terroriste', 'attr' => ['placeholder' => 'disparu térroriste']])
            ->add('blesseSecuriteMilitaire', IntegerType::class,
                    ['required' => true, 'label' => 'blessés securité militaire', 'attr' => ['placeholder' => 'blessé militaire']])
            ->add('blesseCivil', IntegerType::class,
                    ['required' => true, 'label' => 'blessés civil', 'attr' => ['placeholder' => 'blessés civil']])
            ->add('blesseTerroriste', IntegerType::class,
                    ['required' => true, 'label' => 'blessés terroriste', 'attr' => ['placeholder' => 'blessés térroriste']])
            ->add('otages', IntegerType::class,
                    ['required' => true, 'label' => 'Otages', 'attr' => ['placeholder' => 'otages']])
            ->add('liberes', IntegerType::class,
                    ['required' => true, 'label' => 'Libérés', 'attr' => ['placeholder' => 'libérés']])
            ->add('terroristeArretes', IntegerType::class,
                    ['required' => true, 'label' => 'Terroristes arrêtés', 'attr' => ['placeholder' => 'Terroristes arrêtés']])
            ->add('materielAttaque', EntityType::class, [
                        'label' => 'Matériaux récupérés',
                        'class' => MaterielAttaque::class,
                        'multiple' => false,
                        'required'    => true,
                        'placeholder' => 'Choisir'
                    ])
            ->add('autres', TextareaType::class,
                    ['required' => true, 'label' => 'Autres victime', 'attr' => ['placeholder' => 'Autres victime']])
            ->add('remarque', TextType::class,
                    ['required' => true, 'label' => 'Remarque', 'attr' => ['placeholder' => 'Remarque']])
                    
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
                'placeholder' => 'Choisir',
                'attr' => ['class' => 'custom-select'],
                'label' => 'Pays',
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

    private function getChoices()
    {
        $choices = User::NOTIFYBY;
        $output = [];
        foreach ($choices as $key => $value) {
            $output[$value] = $key;
        }
        return $output;
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
