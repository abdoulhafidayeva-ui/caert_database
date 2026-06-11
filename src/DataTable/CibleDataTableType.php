<?php

namespace App\DataTable;

use App\Entity\Cible;
use Omines\DataTablesBundle\{DataTableTypeInterface, DataTable};
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\{TextColumn, TwigColumn};

class CibleDataTableType implements DataTableTypeInterface
{
    private $index;
    public function __construct()
    {
        $this->index = 0;
    }
    public function configure(DataTable $dataTable, array $options): void
    {
        $dataTable->setName($options['dataTableName'])
        ->add('id', TextColumn::class, [
            'label' => 'id',
            'orderable' => false,
            'searchable' => false,
            'globalSearchable' => false,
            'visible' => true,
            'data' =>  function(){
            return ++$this->index;
            },
        ])
        ->add('libelle', TextColumn::class, [
            'label' => 'Libelle',
            'orderable' => true,
            'searchable' => true,
            'globalSearchable' => true
        ])
        ->add('actions', TwigColumn::class, [
            'label' => 'Actions',
            'orderable' => false,
            'template' => 'cible/table_actions.html.twig'
        ])
        ->createAdapter(ORMAdapter::class, [
            'entity' => Cible::class,
        ]);
        
    }
}

