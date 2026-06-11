<?php

namespace App\Service\Import;

final class ImportResult
{
    public function __construct(
        public readonly int $successCount = 0,
        public readonly int $errorCount = 0,
        /** @var list<array{row: int, message: string}> */
        public readonly array $errors = [],
    ) {
    }
}
