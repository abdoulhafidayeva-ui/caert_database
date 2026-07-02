<?php

namespace App\DataTable\Trait;

use Omines\DataTablesBundle\Column\AbstractColumn;

trait RowNumberColumnTrait
{
    private int $rowNumber = 0;

    protected function resetRowNumber(): void
    {
        $this->rowNumber = 0;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rowNumberColumnOptions(): array
    {
        return [
            'label' => '#',
            'field' => null,
            'orderable' => false,
            'searchable' => false,
            'globalSearchable' => false,
            'visible' => true,
            'data' => function (mixed $context, mixed $value, AbstractColumn $column): int {
                $this->rowNumber++;

                return $column->getState()->getStart() + $this->rowNumber;
            },
        ];
    }
}
