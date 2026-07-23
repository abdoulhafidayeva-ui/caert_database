<?php
namespace App\DataTable;

use App\DataTable\Trait\RowNumberColumnTrait;
use App\Entity\AllData;
use App\Entity\User;
use App\Service\Security\UserDataScope;
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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AllDataDataTableType implements DataTableTypeInterface
{
    use RowNumberColumnTrait;

    private $em;
    private $router;

    public function __construct(
        EntityManagerInterface $em,
        UrlGeneratorInterface $router,
        private readonly TranslatorInterface $translator,
        private readonly Security $security,
        private readonly UserDataScope $dataScope,
    ) {
        $this->em = $em;
        $this->router = $router;
    }
    
    public function configure(DataTable $dataTable, array $options): void
    {
        $l = fn (string $key): string => $this->translator->trans($key);
        $this->resetRowNumber();

        $dataTable->setName($options['dataTableName'])
            ->add('num', TextColumn::class, $this->rowNumberColumnOptions())
            ->add('actions', TwigColumn::class, [
                'label' => $l('user.actions'),
                'orderable' => false,
                'template' => 'home/_actions.html.twig'
            ])
            ->add("id", TextColumn::class, [
                "label" => "id",
                "orderable" => true,
                "searchable" => false,
                "visible" => false,
                "globalSearchable" => false,
                "field" => "a.id",
            ])
            ->add("isPublished", TextColumn::class, [
                "label" => $l('incident.status.label'),
                "orderable" => true,
                "searchable" => true,
                "globalSearchable" => true,
                'className' => 'states',
                "render" => function($value, AllData $data) use ($l) {
                    if($data->getIsPublished() == 1) {
                        return "<span class='badge badge-success'>".$l('incident.status.published')."</span>";
                    }else if ($data->getObjetRejet()){
                        return "<span class='badge badge-danger'>".$l('incident.status.rejected')."</span>";
                    }else{
                        return "<span class='badge badge-warning'>".$l('incident.status.pending')."</span>";
                    }
                }
            ])
            ->add('dateAttaque', DateTimeColumn::class, [
                'label' => $l('incident.field.attack_date'),
                'orderable' => true,
                'searchable' => true,
                'format' => 'd/m/Y H:i',
                'globalSearchable' => false,
                'className' => 'dateAttaque',
            ])
            
            ->add("region", TextColumn::class, [
                "label" => $l('incident.field.region'),
                "orderable" => true,
                "visible" => true,
                "searchable" => true,
                "globalSearchable" => true,
                'className' => 'pays.region',
                "field" => "region.libelle"
            ])
            ->add("espace", TextColumn::class, [
                "label" => $l('incident.field.space'),
                "orderable" => true,
                "visible" => true,
                "searchable" => true,
                "globalSearchable" => true,
                'className' => 'espace',
                "field" =>"espace.libelle"
            ])
            ->add("pays", TextColumn::class, [
                "label" => $l('incident.field.country'),
                "orderable" => true,
                "visible" => true,
                "searchable" => true,
                "globalSearchable" => true,
                'className' => 'pays',
                "field" =>"pays.libelle"
            ])
            ->add("capitale", TextColumn::class, [
                "label" => $l('incident.field.capital'),
                "orderable" => true,
                "visible" => true,
                "searchable" => true,
                "globalSearchable" => true,
                'className' => 'pays',
                "field" =>"pays.capitale"
            ])
            ->add('localite', TextColumn::class, [
                'label' => $l('incident.field.locality'),
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            
            ->add("perpetrateurs", TextColumn::class, [
                "label" => $l('incident.field.terrorist_group'),
                "orderable" => true,
                "visible" => true,
                "searchable" => true,
                "globalSearchable" => true,
                'className' => 'perpetrateurs',
                "field" =>"perpetrateurs.libelle"
            ])
            ->add('details', TextColumn::class, [
                'label' => $l('incident.field.details'),
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add("moyenAttaque", TextColumn::class, [
                "label" => $l('incident.field.means'),
                "orderable" => true,
                "visible" => true,
                "searchable" => true,
                "globalSearchable" => true,
                'className' => 'moyenAttaque',
                "field" =>"moyenAttaque.libelle"
            ])
            ->add("cible", TextColumn::class, [
                "label" => $l('incident.field.target'),
                "orderable" => true,
                "visible" => true,
                "searchable" => true,
                "globalSearchable" => true,
                'className' => 'cible',
                "field" =>"cible.libelle"
            ])
            ->add('mortCivil', TextColumn::class, [
                'label' => $l('incident.field.deaths_civilian'),
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add('mortSecuriteMilitaire', TextColumn::class, [
                'label' => $l('incident.field.deaths_military'),
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add('mortTerroriste', TextColumn::class, [
                'label' => $l('incident.field.deaths_terrorist'),
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add('disparuCivil', TextColumn::class, [
                'label' => $l('incident.field.missing_civilian'),
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add('disparuSecuriteMilitaire', TextColumn::class, [
                'label' => $l('incident.field.missing_military'),
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add('disparuTerroriste', TextColumn::class, [
                'label' => $l('incident.field.missing_terrorist'),
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add('blesseCivil', TextColumn::class, [
                'label' => $l('incident.field.injured_civilian'),
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add('blesseSecuriteMilitaire', TextColumn::class, [
                'label' => $l('incident.field.injured_military'),
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add('blesseTerroriste', TextColumn::class, [
                'label' => $l('incident.field.injured_terrorist'),
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add('totalDeces', TextColumn::class, [
                'label' => $l('incident.field.total_deaths'),
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add('totalDisparus', TextColumn::class, [
                'label' => $l('incident.field.total_missing'),
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add('totalBlesses', TextColumn::class, [
                'label' => $l('incident.field.total_injured'),
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add('liberes', TextColumn::class, [
                'label' => $l('incident.field.released'),
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            
            ->add("attaque", TextColumn::class, [
                "label" => $l('incident.field.attack'),
                "orderable" => true,
                "visible" => true,
                "searchable" => true,
                "globalSearchable" => true,
                'className' => 'attaque',
                "field" =>"attaque.libelle"
            ])
            ->add("materielAttaque", TextColumn::class, [
                "label" => $l('incident.field.recovered_materials'),
                "orderable" => true,
                "visible" => true,
                "searchable" => true,
                "globalSearchable" => true,
                'className' => 'materielAttaque',
                "field" =>"materielAttaque.libelle"
            ])
            ->add('otages', TextColumn::class, [
                'label' => $l('incident.field.hostages'),
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add('terroristeArretes', TextColumn::class, [
                'label' => $l('incident.field.arrested'),
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add("materieaux", TextColumn::class, [
                "label" => $l('incident.field.attack_materials'),
                "orderable" => true,
                "visible" => true,
                "searchable" => true,
                "globalSearchable" => true,
                'className' => 'materieaux',
                "field" =>"materieaux.libelle"
            ])
            ->add('autres', TextColumn::class, [
                'label' => $l('incident.field.other'),
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add('remarque', TextColumn::class, [
                'label' => $l('incident.field.remarks'),
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true
            ])
            ->add("user", TextColumn::class, [
                "label" => $l('incident.field.entry'),
                "orderable" => true,
                "visible" => true,
                "searchable" => true,
                "globalSearchable" => true,
                'className' => 'agent',
                "field" =>"user.id",
                "render" => function($value, AllData $allData) use ($l){
                    if ($allData->getUser()) {
                        $to_return = $allData->getUser()->getName()." ".$allData->getUser()->getPrenoms();
                    }else{
                        $to_return = "<span style='color:silver'>".$l('incident.field.nobody')."</span>";
                    }
                    return $to_return;
                }
            ])
            ->add('createdAt', DateTimeColumn::class, [
                'label' => $l('incident.field.entry_date'),
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
                ->leftJoin('a.espace', 'espace')
                ->leftJoin('a.user', 'entryUser');
            },
            'criteria' => [
                function (QueryBuilder $qb, DataTableState $state) {
                    $user = $this->security->getUser();
                    if ($user instanceof User) {
                        $this->dataScope->applyDefaultListScope($qb, $state, $user);
                    }

                    foreach ($state->getSearchColumns() as $searchInfo) {
                        $column = $searchInfo['column'];
                        $value = $searchInfo['search'];
                        $columnName = $column->getName();

                        if ($columnName === 'dateAttaque' || $columnName === 'createdAt') {
                            $this->applyDateAttaqueFilter($qb, $value);
                            continue;
                        }

                        if ($columnName === 'isPublished') {
                            $this->applyStatusFilter($qb, $value);
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

                    $this->applyGlobalSearch($qb, $state);
                },
            ]
        ]);
    }

    private function applyGlobalSearch(QueryBuilder $qb, DataTableState $state): void
    {
        $globalSearch = trim((string) $state->getGlobalSearch());
        if ($globalSearch === '') {
            return;
        }

        $term = '%' . mb_strtoupper($globalSearch) . '%';
        $expr = $qb->expr();
        $orX = $expr->orX();

        $textFields = [
            'pays.libelle',
            'pays.capitale',
            'region.libelle',
            'espace.libelle',
            'perpetrateurs.libelle',
            'attaque.libelle',
            'cible.libelle',
            'moyenAttaque.libelle',
            'materielAttaque.libelle',
            'materieaux.libelle',
            'a.localite',
            'a.details',
            'a.autres',
            'a.remarque',
            'entryUser.name',
            'entryUser.prenoms',
            'entryUser.email',
        ];

        foreach ($textFields as $i => $field) {
            $param = 'global_search_' . $i;
            $orX->add($expr->like('UPPER(' . $field . ')', ':' . $param));
            $qb->setParameter($param, $term);
        }

        // Recherche par id (saisie numérique)
        if (ctype_digit($globalSearch)) {
            $orX->add($expr->eq('a.id', ':global_search_id'));
            $qb->setParameter('global_search_id', (int) $globalSearch);
        }

        $qb->andWhere($orX);
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

    private function applyStatusFilter(QueryBuilder $qb, mixed $value): void
    {
        $statuses = $this->normalizeColumnSearchValue($value);
        if ($statuses === null) {
            return;
        }

        $parts = [];
        foreach ($statuses as $status) {
            switch ($status) {
                case 'pending':
                    $parts[] = '(a.isPublished IS NULL AND (a.objetRejet IS NULL OR a.objetRejet = \'\'))';
                    break;
                case 'published':
                    $parts[] = 'a.isPublished = true';
                    break;
                case 'rejected':
                    $parts[] = '(a.isPublished = false OR (a.objetRejet IS NOT NULL AND a.objetRejet <> \'\'))';
                    break;
            }
        }

        if ($parts !== []) {
            $qb->andWhere('('.implode(' OR ', $parts).')');
        }
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
