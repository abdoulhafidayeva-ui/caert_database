<?php

namespace App\Service\Config;

final class EditableEnvRegistry
{
    /** @var array<string, EditableEnvDefinition>|null */
    private ?array $definitions = null;

    /**
     * @param array<string, array<string, mixed>> $editableEnvVars
     */
    public function __construct(
        private readonly array $editableEnvVars,
    ) {
    }

    /**
     * @return array<string, EditableEnvDefinition>
     */
    public function all(): array
    {
        if ($this->definitions !== null) {
            return $this->definitions;
        }

        $definitions = [];
        foreach ($this->editableEnvVars as $key => $config) {
            $definitions[$key] = new EditableEnvDefinition(
                key: $key,
                label: (string) ($config['label'] ?? $key),
                group: (string) ($config['group'] ?? 'env_config.group.other'),
                type: (string) ($config['type'] ?? 'text'),
                required: (bool) ($config['required'] ?? false),
                help: isset($config['help']) ? (string) $config['help'] : null,
                choices: array_map('strval', $config['choices'] ?? []),
            );
        }

        return $this->definitions = $definitions;
    }

    public function get(string $key): ?EditableEnvDefinition
    {
        return $this->all()[$key] ?? null;
    }

    /**
     * @return array<string, array<int, EditableEnvDefinition>>
     */
    public function grouped(): array
    {
        $groups = [];
        foreach ($this->all() as $definition) {
            $groups[$definition->group][] = $definition;
        }

        return $groups;
    }
}
