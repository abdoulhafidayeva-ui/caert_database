<?php

namespace App\Service\Config;

use Symfony\Component\Filesystem\Filesystem;

final class EnvFileManager
{
    private const TARGET_FILE = '.env.local';

    public function __construct(
        private readonly string $projectDir,
        private readonly EditableEnvRegistry $registry,
        private readonly Filesystem $filesystem,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function getCurrentValues(): array
    {
        $fileValues = $this->readFromFiles();
        $values = [];

        foreach (array_keys($this->registry->all()) as $key) {
            if (array_key_exists($key, $fileValues)) {
                $values[$key] = $fileValues[$key];
                continue;
            }

            $value = $_ENV[$key] ?? getenv($key);
            $values[$key] = is_string($value) ? $value : '';
        }

        return $values;
    }

    /**
     * @param array<string, string|null> $submitted
     *
     * @return array<string, string>
     */
    public function applyChanges(array $submitted): array
    {
        $current = $this->getCurrentValues();
        $changes = [];

        foreach ($this->registry->all() as $key => $definition) {
            if (!array_key_exists($key, $submitted)) {
                continue;
            }

            $value = $submitted[$key];
            if ($definition->type === 'password' && ($value === null || $value === '')) {
                continue;
            }

            if ($value === null) {
                continue;
            }

            $value = trim($value);
            if ($value === '' && !$definition->required) {
                $changes[$key] = '';
                continue;
            }

            if ($value !== ($current[$key] ?? '')) {
                $changes[$key] = $value;
            }
        }

        if ($changes === []) {
            return [];
        }

        $this->writeToLocalFile($changes);

        return $changes;
    }

    public function getTargetFilePath(): string
    {
        return $this->projectDir.\DIRECTORY_SEPARATOR.self::TARGET_FILE;
    }

    /**
     * @param array<string, string> $changes
     */
    private function writeToLocalFile(array $changes): void
    {
        $path = $this->getTargetFilePath();
        $lines = $this->filesystem->exists($path)
            ? file($path, FILE_IGNORE_NEW_LINES)
            : $this->defaultHeaderLines();

        if ($lines === false) {
            $lines = $this->defaultHeaderLines();
        }

        $updatedKeys = [];
        $result = [];

        foreach ($lines as $line) {
            $parsed = $this->parseLine($line);
            if ($parsed === null) {
                $result[] = $line;
                continue;
            }

            [$key, $value] = $parsed;
            if (array_key_exists($key, $changes)) {
                $result[] = $this->formatAssignment($key, $changes[$key]);
                $updatedKeys[$key] = true;
                continue;
            }

            $result[] = $line;
        }

        foreach ($changes as $key => $value) {
            if (!isset($updatedKeys[$key])) {
                $result[] = $this->formatAssignment($key, $value);
            }
        }

        $content = implode("\n", $result);
        if ($content !== '' && !str_ends_with($content, "\n")) {
            $content .= "\n";
        }

        $this->filesystem->dumpFile($path, $content);
    }

    /**
     * @return list<string>
     */
    private function defaultHeaderLines(): array
    {
        return [
            '# Local overrides managed via CAERT admin (/admin/env-config)',
            '# https://symfony.com/doc/current/configuration.html#overriding-environment-values',
        ];
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private function parseLine(string $line): ?array
    {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            return null;
        }

        if (!preg_match('/^([A-Z][A-Z0-9_]*)=(.*)$/', $trimmed, $matches)) {
            return null;
        }

        return [$matches[1], $this->unquoteValue($matches[2])];
    }

    private function unquoteValue(string $value): string
    {
        $value = trim($value);
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            return substr($value, 1, -1);
        }

        return $value;
    }

    private function formatAssignment(string $key, string $value): string
    {
        if ($value === '') {
            return $key.'=';
        }

        if (preg_match('/[\s#="\'\\\\]/', $value) === 1) {
            return $key.'="'.addcslashes($value, "\\\"\n\r\t").'"';
        }

        return $key.'='.$value;
    }

    /**
     * @return array<string, string>
     */
    private function readFromFiles(): array
    {
        $values = [];
        $files = [
            $this->projectDir.\DIRECTORY_SEPARATOR.'.env',
            $this->getTargetFilePath(),
        ];

        foreach ($files as $file) {
            if (!$this->filesystem->exists($file)) {
                continue;
            }

            $lines = file($file, FILE_IGNORE_NEW_LINES);
            if ($lines === false) {
                continue;
            }

            foreach ($lines as $line) {
                $parsed = $this->parseLine($line);
                if ($parsed === null) {
                    continue;
                }

                [$key, $value] = $parsed;
                $values[$key] = $value;
            }
        }

        return $values;
    }
}
