<?php

namespace App\Service\Config;

final readonly class EditableEnvDefinition
{
    /**
     * @param array<string, string> $choices
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $group,
        public string $type = 'text',
        public bool $required = false,
        public ?string $help = null,
        public array $choices = [],
    ) {
    }
}
