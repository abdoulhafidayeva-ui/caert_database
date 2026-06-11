<?php

namespace App\DataTable\Column;

use Symfony\Component\OptionsResolver\OptionsResolver;

trait ColumnTrait
{
    protected function configureOptions(OptionsResolver $resolver): static
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefaults([
                'processSearch' => null,
                'processGlobalSearch' => null,
            ])
            ->setAllowedTypes('processSearch', ['null', 'callable'])
            ->setAllowedTypes('processGlobalSearch', ['null', 'callable']);

        return $this;
    }

    public function getProcessSearch()
    {
        return $this->options['processSearch'];
    }

    public function getProcessGlobalSearch()
    {
        return $this->options['processGlobalSearch'];
    }
}
