<?php

namespace App\Service\Import;

use App\Entity\AllData;
use App\Entity\Attaque;
use App\Entity\Cible;
use App\Entity\Espace;
use App\Entity\Materiaux;
use App\Entity\MaterielAttaque;
use App\Entity\MoyenAttaque;
use App\Entity\Pays;
use App\Entity\Perpetrateurs;
use App\Entity\User;
use App\Service\Audit\AuditLogger;
use App\Service\Incident\AllDataTotalsCalculator;
use App\Service\Security\IncidentCountryGuard;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ExcelImportService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AllDataTotalsCalculator $totalsCalculator,
        private readonly AuditLogger $auditLogger,
        private readonly IncidentCountryGuard $countryGuard,
        private readonly string $uploadDirectory,
    ) {
    }

    public function import(UploadedFile $file, User $user): ImportResult
    {
        if (!is_dir($this->uploadDirectory) && !mkdir($this->uploadDirectory, 0775, true) && !is_dir($this->uploadDirectory)) {
            throw new \RuntimeException('Répertoire d\'import inaccessible.');
        }

        $storedName = bin2hex(random_bytes(16)) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $targetPath = rtrim($this->uploadDirectory, '/\\') . DIRECTORY_SEPARATOR . $storedName;
        $file->move(dirname($targetPath), basename($targetPath));

        $errors = [];
        $success = 0;
        $rowNum = 1;

        try {
            $spreadsheet = IOFactory::load($targetPath);
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->removeRow(1);
            $rows = $sheet->toArray(null, true, true, true);

            foreach ($rows as $row) {
                ++$rowNum;
                if ($this->isEmptyRow($row)) {
                    continue;
                }

                try {
                    $incident = $this->mapRow($row, $user);
                    $this->em->persist($incident);
                    ++$success;
                } catch (\Throwable $e) {
                    $errors[] = ['row' => $rowNum, 'message' => $e->getMessage()];
                }
            }

            $this->em->flush();
        } finally {
            if (is_file($targetPath)) {
                unlink($targetPath);
            }
        }

        $this->auditLogger->log('IMPORT_COMPLETE', 'all_data', null, [
            'success' => $success,
            'errors' => count($errors),
            'filename' => $file->getClientOriginalName(),
        ]);

        return new ImportResult($success, count($errors), $errors);
    }

  private function isEmptyRow(array $row): bool
    {
        return trim((string) ($row['A'] ?? '')) === '' && trim((string) ($row['H'] ?? '')) === '';
    }

    private function mapRow(array $row, User $user): AllData
    {
        $incident = new AllData();
        $incident->setDetails((string) ($row['H'] ?? ''));
        $incident->setMortSecuriteMilitaire($this->intVal($row['L'] ?? 0));
        $incident->setMortCivil($this->intVal($row['K'] ?? 0));
        $incident->setMortTerroriste($this->intVal($row['M'] ?? 0));
        $incident->setDisparuSecuriteMilitaire($this->intVal($row['O'] ?? 0));
        $incident->setDisparuCivil($this->intVal($row['N'] ?? 0));
        $incident->setDisparuTerroriste($this->intVal($row['P'] ?? 0));
        $incident->setBlesseSecuriteMilitaire($this->intVal($row['R'] ?? 0));
        $incident->setBlesseCivil($this->intVal($row['Q'] ?? 0));
        $incident->setBlesseTerroriste($this->intVal($row['S'] ?? 0));
        $incident->setOtages($this->intVal($row['Z'] ?? 0));
        $incident->setLiberes($this->intVal($row['W'] ?? 0));
        $incident->setTerroristeArretes($this->intVal($row['AA'] ?? 0));
        $incident->setAutres((string) ($row['AC'] ?? ''));
        $incident->setRemarque((string) ($row['AD'] ?? ''));
        $incident->setLocalite((string) ($row['F'] ?? ''));
        $incident->setUser($user);
        $incident->setCreatedAt(new \DateTime());
        $incident->setIsPublished(null);

        $incident->setDateAttaque($this->parseExcelDate($row['A'] ?? null));

        $incident->setAttaque($this->resolveRef(Attaque::class, $row['X'] ?? null, 'type d\'attaque'));
        $incident->setMaterielAttaque($this->resolveRef(MaterielAttaque::class, $row['Y'] ?? null, 'matériel d\'attaque'));
        $incident->setCible($this->resolveRef(Cible::class, $row['J'] ?? null, 'cible'));
        $incident->setMaterieaux($this->resolveRef(Materiaux::class, $row['AB'] ?? null, 'matériaux'));
        $incident->setMoyenAttaque($this->resolveRef(MoyenAttaque::class, $row['I'] ?? null, 'moyen d\'attaque'));
        $incident->setPerpetrateur($this->resolveRef(Perpetrateurs::class, $row['G'] ?? null, 'groupe'));
        $pays = $this->resolveRef(Pays::class, $row['D'] ?? null, 'pays');
        try {
            $this->countryGuard->assertWriteAllowed($user, $pays);
        } catch (\Symfony\Component\Security\Core\Exception\AccessDeniedException $e) {
            throw new \InvalidArgumentException(sprintf(
                'Import refusé : %s',
                $pays->getLibelle()
            ), 0, $e);
        }
        $incident->setPays($pays);
        $incident->setEspace($this->resolveRef(Espace::class, $row['C'] ?? null, 'espace'));

        $this->totalsCalculator->applyTotals($incident);

        return $incident;
    }

    private function intVal(mixed $value): int
    {
        return max(0, (int) $value);
    }

    private function parseExcelDate(mixed $value): \DateTime
    {
        if ($value === null || $value === '') {
            throw new \InvalidArgumentException('Date d\'attaque (colonne A) manquante.');
        }

        if (is_numeric($value)) {
            $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value);

            return \DateTime::createFromInterface($date);
        }

        $timestamp = strtotime((string) $value);
        if ($timestamp === false) {
            throw new \InvalidArgumentException(sprintf('Date d\'attaque invalide: %s', $value));
        }

        return (new \DateTime())->setTimestamp($timestamp);
    }

    private function resolveRef(string $entityClass, mixed $libelle, string $label): object
    {
        $normalized = trim((string) $libelle);
        if ($normalized === '') {
            throw new \InvalidArgumentException(sprintf('%s manquant.', $label));
        }

        $entity = $this->em->getRepository($entityClass)
            ->createQueryBuilder('e')
            ->where('UPPER(e.libelle) = :libelle')
            ->setParameter('libelle', mb_strtoupper($normalized))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($entity === null) {
            throw new \InvalidArgumentException(sprintf('%s inconnu : %s', $label, $normalized));
        }

        return $entity;
    }
}
