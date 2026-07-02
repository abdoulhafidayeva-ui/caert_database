<?php

namespace App\DataTable;

use App\DataTable\Trait\RowNumberColumnTrait;
use App\Entity\Materiaux;
use Omines\DataTablesBundle\{DataTableTypeInterface, DataTable};
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\{TextColumn, TwigColumn};
use Symfony\Contracts\Translation\TranslatorInterface;

class MaterielDataTableType implements DataTableTypeInterface
{
    use RowNumberColumnTrait;

    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function configure(DataTable $dataTable, array $options): void
    {
        $l = fn (string $key): string => $this->translator->trans($key);
        $this->resetRowNumber();

        $dataTable->setName($options['dataTableName'])
        ->add('num', TextColumn::class, $this->rowNumberColumnOptions())
        ->add('libelle', TextColumn::class, [
            'label' => $l('referential.field.label'),
            'orderable' => true,
            'searchable' => true,
            'globalSearchable' => true
        ])
        ->add('actions', TwigColumn::class, [
            'label' => $l('user.actions'),
            'orderable' => false,
            'template' => 'materiel/table_actions.html.twig'
        ])
        ->createAdapter(ORMAdapter::class, [
            'entity' => Materiaux::class,
        ]);
    }
}
