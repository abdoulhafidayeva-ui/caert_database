<?php

namespace App\Form;

use App\Entity\AllData;
use App\Entity\Pays;
use App\Entity\Region;
use App\Entity\User;
use App\Service\Security\IncidentCountryGuard;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class IncidentFormCountryConfigurator
{
    public static function apply(
        FormBuilderInterface $builder,
        Security $security,
        IncidentCountryGuard $countryGuard,
    ): void {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($security, $countryGuard) {
            $user = $security->getUser();
            if (!$user instanceof User || !$countryGuard->isCountryRestricted($user)) {
                return;
            }

            $userPays = $countryGuard->getAssignedCountry($user);
            if ($userPays === null) {
                return;
            }

            $form = $event->getForm();
            $region = $userPays->getRegion();

            if ($form->has('regions')) {
                $form->add('regions', EntityType::class, [
                    'label' => 'Région',
                    'class' => Region::class,
                    'mapped' => false,
                    'choices' => $region !== null ? [$region] : [],
                    'data' => $region,
                    'choice_label' => 'libelle',
                    'disabled' => true,
                    'required' => false,
                ]);
            }

            $form->add('pays', EntityType::class, [
                'class' => Pays::class,
                'choices' => [$userPays],
                'data' => $userPays,
                'choice_label' => 'libelle',
                'label' => 'Pays',
                'required' => true,
                'attr' => ['class' => 'custom-select', 'readonly' => true],
            ]);
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($security, $countryGuard) {
            $user = $security->getUser();
            if (!$user instanceof User) {
                return;
            }

            $data = $event->getData();
            if (!$data instanceof AllData) {
                return;
            }

            if ($countryGuard->isCountryRestricted($user)) {
                $userPays = $countryGuard->getAssignedCountry($user);
                if ($userPays !== null) {
                    $data->setPays($userPays);
                }
            }

            $countryGuard->assertCountryAllowed($user, $data->getPays());
        });
    }
}
