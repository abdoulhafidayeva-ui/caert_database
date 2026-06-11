<?php
namespace App\DataTable;

use App\Entity\AllData;
use App\Entity\Attaque;
use App\Entity\Cible;
use App\Entity\Materiaux;
use App\Entity\MaterielAttaque;
use App\Entity\MoyenAttaque;
use App\Entity\Pays;
use App\Entity\Perpetrateurs;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableTypeInterface;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\{TextColumn, BoolColumn, TwigColumn};
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\DataTableState;
use Doctrine\ORM\EntityManagerInterface;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AllDataDataTableType implements DataTableTypeInterface
{
    private $index;
    private $em;
    private $router;
    private $security;
    private $authorizationChecker=null;

    
    public function __construct(EntityManagerInterface $em,
    UrlGeneratorInterface $router, Security $security, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->index = 0;
        $this->em = $em;
        $this->router = $router;
        $this->security = $security;
        $this->authorizationChecker=$authorizationChecker;
        
    }
    
    public function configure(DataTable $dataTable, array $options): void
    {
        $dataTable->setName($options['dataTableName'])
            ->add('num', TextColumn::class, [
                'data' =>  function(){
                 return ++$this->index;
                },
                'label' => '#',
                'orderable' => false,
                'searchable' => false,
                'globalSearchable' => false
            ])
            ->add('actions', TwigColumn::class, [
                'label' => 'Actions',
                'orderable' => false,
                'template' => 'home/_actions.html.twig'
            ])
            ->add("id", TextColumn::class, [
                "label" => "#",
                "orderable" => true,
                "searchable" => false,
                "visible" => false,
                "globalSearchable" => false
            ])
            ->add("isPublished", TextColumn::class, [
                "label" => "Statut",
                "orderable" => true,
                "searchable" => true,
                "globalSearchable" => true,
                'className' => 'states',
                "render" => function($value, AllData $data) {
                    if($data->getIsPublished() == 1) {
                        return "<span class='badge badge-success'>Publié</span>";
                    }else if ($data->getObjetRejet()){
                        return "<span class='badge badge-danger'>Rejeté</span>";
                    }else{
                        return "<span class='badge badge-warning'>En attente</span>";
                    }
                }
            ])
            ->add('dateAttaque', DateTimeColumn::class, [
                'label' => 'Date d\'attaque',
                'orderable' => true,
                'searchable' => true,
                'format' => 'd/m/Y H:i',
                'globalSearchable' => false,
                'className' => 'dateAttaque',
            ])
            
            ->add("region", TextColumn::class, [
                "label" => "Région",
                "orderable" => true,
                "visible" => true,
                "searchable" => true,
                "globalSearchable" => true,
                'className' => 'pays.region',
                "field" => "region.libelle"
            ])
            ->add("espace", TextColumn::class, [
                "label" => "Espace",
                "orderable" => true,
                "visible" => true,
                "searchable" => true,
                "globalSearchable" => true,
                'className' => 'espace',
                "field" =>"espace.libelle"
            ])
            ->add("pays", TextColumn::class, [
                "label" => "Pays",
                "orderable" => true,
                "visible" => true,
                "searchable" => true,
                "globalSearchable" => true,
                'className' => 'pays',
                "field" =>"pays.libelle"
            ])
            ->add("capitale", TextColumn::class, [
                "label" => "Capitale",
                "orderable" => true,
                "visible" => true,
                "searchable" => true,
                "globalSearchable" => true,
                'className' => 'pays',
                "field" =>"pays.capitale"
            ])
            ->add('localite', TextColumn::class, [
                'label' => 'localite',
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            
            ->add("perpetrateurs", TextColumn::class, [
                "label" => "Groupe terroriste",
                "orderable" => true,
                "visible" => true,
                "searchable" => true,
                "globalSearchable" => true,
                'className' => 'perpetrateurs',
                "field" =>"perpetrateurs.libelle"
            ])
            ->add('details', TextColumn::class, [
                'label' => 'details',
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add("moyenAttaque", TextColumn::class, [
                "label" => "Moyen",
                "orderable" => true,
                "visible" => true,
                "searchable" => true,
                "globalSearchable" => true,
                'className' => 'moyenAttaque',
                "field" =>"moyenAttaque.libelle"
            ])
            ->add("cible", TextColumn::class, [
                "label" => "Cible",
                "orderable" => true,
                "visible" => true,
                "searchable" => true,
                "globalSearchable" => true,
                'className' => 'cible',
                "field" =>"cible.libelle"
            ])
            ->add('mortCivil', TextColumn::class, [
                'label' => 'Mort civil',
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add('mortSecuriteMilitaire', TextColumn::class, [
                'label' => 'Mort securité militaire',
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add('mortTerroriste', TextColumn::class, [
                'label' => 'Mort terroriste',
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add('disparuCivil', TextColumn::class, [
                'label' => 'Disparu civil',
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add('disparuSecuriteMilitaire', TextColumn::class, [
                'label' => 'Disparu securité militaire',
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add('disparuTerroriste', TextColumn::class, [
                'label' => 'Disparu terroriste',
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add('blesseCivil', TextColumn::class, [
                'label' => 'Blessé civil',
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add('blesseSecuriteMilitaire', TextColumn::class, [
                'label' => 'Blessé securité militaire',
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add('blesseTerroriste', TextColumn::class, [
                'label' => 'Blessé terroriste',
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add('totalDeces', TextColumn::class, [
                'label' => 'Total dècès',
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add('totalDisparus', TextColumn::class, [
                'label' => 'Total disparus',
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add('totalBlesses', TextColumn::class, [
                'label' => 'Total blessés',
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add('liberes', TextColumn::class, [
                'label' => 'Libérés',
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            
            ->add("attaque", TextColumn::class, [
                "label" => "Attaque",
                "orderable" => true,
                "visible" => true,
                "searchable" => true,
                "globalSearchable" => true,
                'className' => 'attaque',
                "field" =>"attaque.libelle"
            ])
            ->add("materielAttaque", TextColumn::class, [
                "label" => "Materiel attaque",
                "orderable" => true,
                "visible" => true,
                "searchable" => true,
                "globalSearchable" => true,
                'className' => 'materielAttaque',
                "field" =>"materielAttaque.libelle"
            ])
            ->add('otages', TextColumn::class, [
                'label' => 'Otages',
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add('terroristeArretes', TextColumn::class, [
                'label' => 'Terroristes arrêtés',
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add("materieaux", TextColumn::class, [
                "label" => "Materieaux récupérés",
                "orderable" => true,
                "visible" => true,
                "searchable" => true,
                "globalSearchable" => true,
                'className' => 'materieaux',
                "field" =>"materieaux.libelle"
            ])
            ->add('autres', TextColumn::class, [
                'label' => 'Autres',
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add('remarque', TextColumn::class, [
                'label' => 'Remarques',
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add("user", TextColumn::class, [
                "label" => "Saisie",
                "orderable" => true,
                "visible" => true,
                "searchable" => true,
                "globalSearchable" => true,
                'className' => 'agent',
                "field" =>"user.id",
                "render" => function($value, AllData $allData){
                    if ($allData->getUser()) {
                        $to_return = $allData->getUser()->getName()." ".$allData->getUser()->getPrenoms();
                    }else{
                        $to_return = "<span style='color:silver'>Personne</span>";
                    }
                    return $to_return;
                }
            ])
            ->add('createdAt', DateTimeColumn::class, [
                'label' => 'Date',
                'orderable' => true,
                'format' => 'd/m/Y H:i:s',
                'globalSearchable' => false,
                'className' => 'createdAt',
            ])
            
        ->addOrderBy('dateAttaque', 'desc')
        ->addOrderBy('id', 'desc')
        ->createAdapter(ORMAdapter::class, [
            'entity' => AllData::class,
            'query' => function(QueryBuilder $queryBuilder) use ($options){
                $queryBuilder->select('a')
                ->from(AllData::class, 'a')
                ->leftJoin('a.cible', 'cible')
                ->leftJoin('a.pays', 'pays')
                ->leftJoin('pays.region', 'region')
                ->leftJoin('a.attaque', 'attaque')
                ->leftJoin('a.materielAttaque', 'materielAttaque')
                ->leftJoin('a.materieaux', 'materieaux')
                ->leftJoin('a.moyenAttaque', 'moyenAttaque')
                ->leftJoin('a.perpetrateur', 'perpetrateurs')
                ->leftJoin('a.espace', 'espace');
                if(!$this->authorizationChecker->isGranted('ROLE_ADMIN'))
                {
                    $queryBuilder->where('a.pays = :pays')->setParameter('pays', $this->security->getUser()->getPays());
                }
            },
            'criteria' => [
                function (QueryBuilder $qb, DataTableState $state) {
                    foreach ($state->getSearchColumns() as $searchInfo) {
                        $column = $searchInfo['column'];
                        $value = $searchInfo['search'];
                        $columnName = $column->getName();

                        if ($columnName === 'dateAttaque' || $columnName === 'createdAt') {
                            $this->applyDateAttaqueFilter($qb, $value);
                            continue;
                        }

                        $filterValues = $this->normalizeColumnSearchValue($value);
                        if ($filterValues === null) {
                            continue;
                        }

                        $field = $column->getField();
                        if ($field === null || $field === '') {
                            continue;
                        }

                        $qb->andWhere($qb->expr()->in('UPPER(' . $field . ')', ':' . $columnName))
                            ->setParameter($columnName, array_map(static fn (string $v) => mb_strtoupper($v), $filterValues));
                    }
                },
            ]
        ]);
    }

    /**
     * @return list<string>|null
     */
    private function normalizeColumnSearchValue(mixed $value): ?array
    {
        if (is_array($value)) {
            $filtered = array_values(array_filter($value, static fn ($el) => !in_array($el, ['', 'null', null], true)));

            return $filtered === [] ? null : array_map('strval', $filtered);
        }

        $trimmed = trim((string) $value);
        if ($trimmed === '' || $trimmed === 'null') {
            return null;
        }

        if (str_starts_with($trimmed, '[')) {
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded)) {
                $filtered = array_values(array_filter($decoded, static fn ($el) => $el !== '' && $el !== null));

                return $filtered === [] ? null : array_map('strval', $filtered);
            }
        }

        return [$trimmed];
    }

    private function applyDateAttaqueFilter(QueryBuilder $qb, mixed $value): void
    {
        if (is_array($value)) {
            $values = $value;
        } else {
            $trimmed = trim((string) $value);
            if ($trimmed === '') {
                return;
            }
            $values = json_decode($trimmed, true);
        }

        if (!is_array($values)) {
            return;
        }

        if (!empty($values['start'])) {
            $startDate = $this->parseDashboardFilterDate((string) $values['start'], '00:00:00');
            if ($startDate instanceof \DateTime) {
                $qb->andWhere($qb->expr()->gte('a.dateAttaque', ':dateAttaque_start'))
                    ->setParameter('dateAttaque_start', $startDate);
            }
        }

        if (!empty($values['end'])) {
            $endDate = $this->parseDashboardFilterDate((string) $values['end'], '23:59:59');
            if ($endDate instanceof \DateTime) {
                $qb->andWhere($qb->expr()->lte('a.dateAttaque', ':dateAttaque_end'))
                    ->setParameter('dateAttaque_end', $endDate);
            }
        }
    }

    private function parseDashboardFilterDate(string $date, string $time): ?\DateTime
    {
        $date = trim($date);
        if ($date === '') {
            return null;
        }

        foreach (['d/m/Y', 'd/m/y', 'Y-m-d', 'd-m-Y'] as $format) {
            $parsed = \DateTime::createFromFormat($format . ' H:i:s', $date . ' ' . $time);
            if ($parsed instanceof \DateTime) {
                return $parsed;
            }
        }

        return null;
    }
}
