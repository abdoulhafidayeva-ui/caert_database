<?php

namespace App\DataTable;

use App\DataTable\Trait\RowNumberColumnTrait;
use App\Entity\AllData;
use App\Entity\User;
use App\Service\Security\UserDataScope;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigColumn;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableState;
use Omines\DataTablesBundle\DataTableTypeInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * File de validation — incidents en attente (isPublished IS NULL), sans fenêtre des 12 mois.
 */
class PendingValidationDataTableType implements DataTableTypeInterface
{
    use RowNumberColumnTrait;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly Security $security,
        private readonly UserDataScope $dataScope,
    ) {
    }

    public function configure(DataTable $dataTable, array $options): void
    {
        $l = fn (string $key): string => $this->translator->trans($key);
        $this->resetRowNumber();

        $dataTable->setName($options['dataTableName'] ?? 'pendingValidationTable')
            ->add('num', TextColumn::class, $this->rowNumberColumnOptions())
            ->add('actions', TwigColumn::class, [
                'label' => $l('user.actions'),
                'orderable' => false,
                'template' => 'workflow/_actions.html.twig',
            ])
            ->add('id', TextColumn::class, [
                'label' => 'id',
                'orderable' => true,
                'searchable' => false,
                'visible' => false,
                'globalSearchable' => false,
                'field' => 'a.id',
            ])
            ->add('dateAttaque', DateTimeColumn::class, [
                'label' => $l('incident.field.attack_date'),
                'orderable' => true,
                'searchable' => true,
                'format' => 'd/m/Y H:i',
                'globalSearchable' => false,
                'className' => 'dateAttaque',
            ])
            ->add('region', TextColumn::class, [
                'label' => $l('incident.field.region'),
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true,
                'className' => 'region',
                'field' => 'region.libelle',
            ])
            ->add('pays', TextColumn::class, [
                'label' => $l('incident.field.country'),
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true,
                'className' => 'pays',
                'field' => 'pays.libelle',
            ])
            ->add('localite', TextColumn::class, [
                'label' => $l('incident.field.locality'),
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true,
                'field' => 'a.localite',
            ])
            ->add('perpetrateurs', TextColumn::class, [
                'label' => $l('incident.field.terrorist_group'),
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true,
                'field' => 'perpetrateurs.libelle',
            ])
            ->add('totalDeces', TextColumn::class, [
                'label' => $l('incident.kpi.deaths'),
                'orderable' => true,
                'searchable' => false,
                'globalSearchable' => false,
                'field' => 'a.totalDeces',
                'render' => static function ($value): string {
                    return '<span class="badge badge-danger">'.(int) $value.'</span>';
                },
            ])
            ->add('user', TextColumn::class, [
                'label' => $l('workflow.table.entry_by'),
                'orderable' => true,
                'searchable' => true,
                'globalSearchable' => true,
                'field' => 'entryUser.email',
                'render' => function ($value, AllData $row) use ($l): string {
                    $entryUser = $row->getUser();
                    if ($entryUser === null) {
                        return '<span class="text-muted">'.$l('incident.field.nobody').'</span>';
                    }

                    $name = trim(($entryUser->getName() ?? '').' '.($entryUser->getPrenoms() ?? ''));

                    return $name !== ''
                        ? htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                        : htmlspecialchars((string) $entryUser->getEmail(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                },
            ])
            ->add('createdAt', DateTimeColumn::class, [
                'label' => $l('workflow.table.entry_at'),
                'orderable' => true,
                'searchable' => true,
                'format' => 'd/m/Y H:i',
                'globalSearchable' => false,
                'className' => 'createdAt',
            ])
            ->addOrderBy('createdAt', 'desc')
            ->addOrderBy('id', 'desc')
            ->createAdapter(ORMAdapter::class, [
                'entity' => AllData::class,
                'query' => static function (QueryBuilder $qb): void {
                    $qb->select('a')
                        ->from(AllData::class, 'a')
                        ->leftJoin('a.pays', 'pays')
                        ->leftJoin('pays.region', 'region')
                        ->leftJoin('a.perpetrateur', 'perpetrateurs')
                        ->leftJoin('a.user', 'entryUser')
                        ->andWhere('a.isPublished IS NULL')
                        ->andWhere('a.objetRejet IS NULL OR a.objetRejet = \'\'');
                },
                'criteria' => [
                    function (QueryBuilder $qb, DataTableState $state): void {
                        $user = $this->security->getUser();
                        if ($user instanceof User) {
                            $this->dataScope->applyRegionScopeToQueryBuilder($qb, $user);
                        }

                        foreach ($state->getSearchColumns() as $searchInfo) {
                            $column = $searchInfo['column'];
                            $value = $searchInfo['search'];
                            $columnName = $column->getName();

                            if ($columnName === 'dateAttaque' || $columnName === 'createdAt') {
                                $this->applyDateFilter($qb, $columnName === 'createdAt' ? 'a.createdAt' : 'a.dateAttaque', $value);
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

                            $qb->andWhere($qb->expr()->in('UPPER('.$field.')', ':'.$columnName))
                                ->setParameter($columnName, array_map(static fn (string $v) => mb_strtoupper($v), $filterValues));
                        }

                        $this->applyGlobalSearch($qb, $state);
                    },
                ],
            ]);
    }

    private function applyGlobalSearch(QueryBuilder $qb, DataTableState $state): void
    {
        $globalSearch = trim((string) $state->getGlobalSearch());
        if ($globalSearch === '') {
            return;
        }

        $term = '%'.mb_strtoupper($globalSearch).'%';
        $expr = $qb->expr();
        $orX = $expr->orX();
        $fields = [
            'pays.libelle',
            'region.libelle',
            'perpetrateurs.libelle',
            'a.localite',
            'entryUser.name',
            'entryUser.prenoms',
            'entryUser.email',
        ];

        foreach ($fields as $i => $field) {
            $param = 'pending_global_search_'.$i;
            $orX->add($expr->like('UPPER('.$field.')', ':'.$param));
            $qb->setParameter($param, $term);
        }

        if (ctype_digit($globalSearch)) {
            $orX->add($expr->eq('a.id', ':pending_global_search_id'));
            $qb->setParameter('pending_global_search_id', (int) $globalSearch);
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

    private function applyDateFilter(QueryBuilder $qb, string $field, mixed $value): void
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

        $paramBase = str_replace('.', '_', $field);

        if (!empty($values['start'])) {
            $startDate = $this->parseFilterDate((string) $values['start'], '00:00:00');
            if ($startDate instanceof \DateTime) {
                $qb->andWhere($qb->expr()->gte($field, ':'.$paramBase.'_start'))
                    ->setParameter($paramBase.'_start', $startDate);
            }
        }

        if (!empty($values['end'])) {
            $endDate = $this->parseFilterDate((string) $values['end'], '23:59:59');
            if ($endDate instanceof \DateTime) {
                $qb->andWhere($qb->expr()->lte($field, ':'.$paramBase.'_end'))
                    ->setParameter($paramBase.'_end', $endDate);
            }
        }
    }

    private function parseFilterDate(string $date, string $time): ?\DateTime
    {
        $date = trim($date);
        if ($date === '') {
            return null;
        }

        foreach (['d/m/Y', 'd/m/y', 'Y-m-d', 'd-m-Y'] as $format) {
            $parsed = \DateTime::createFromFormat($format.' H:i:s', $date.' '.$time);
            if ($parsed instanceof \DateTime) {
                return $parsed;
            }
        }

        return null;
    }
}
