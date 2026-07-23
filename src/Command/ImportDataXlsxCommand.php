<?php

namespace App\Command;

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
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Import historique AUCTC (fichier "Data.xlsx", feuille "Data") vers all_data.
 *
 * Colonnes attendues (ligne 1) : ID, Year, Month, Date, Region, Country, City,
 * Perpetors, Means of attack, Primary target, Attacks, Total Deaths, Total Injured.
 *
 * Les libellés anglais sont convertis vers les référentiels FR existants via une
 * table d'alias ; les champs absents du fichier reçoivent des valeurs par défaut
 * explicites (« Non renseigné », etc.).
 */
#[AsCommand(
    name: 'app:import-data-xlsx',
    description: 'Importe le fichier historique Data.xlsx (feuille Data) dans all_data.',
)]
final class ImportDataXlsxCommand extends Command
{
    private const DEFAULT_SHEET = 'Data';
    private const DEFAULT_BATCH = 200;

    /** Alias pays EN (majuscules) → libellé pays en base (fixtures prod). */
    private const COUNTRY_ALIASES = [
        'ALGERIA' => 'ALGERIE',
        'BENIN' => 'BENIN',
        'BURKINA FASO' => 'BURKINA FASO',
        'BURUNDI' => 'BURUNDI',
        'CAMEROON' => 'CAMEROUN',
        'CENTRAL AFRICAN REPUBLIC (CAR)' => 'CENTRAFRIQUE',
        'CENTRAL AFRICAN REPUBLIC' => 'CENTRAFRIQUE',
        'CHAD' => 'TCHAD',
        "COTE D'IVOIRE" => "COTE D'IVOIRE",
        'DEMOCRATIC REPUBLIC OF THE CONGO' => 'RDC',
        'DRC' => 'RDC',
        'DJIBOUTI' => 'DJIBOUTI',
        'EGYPT' => 'EGYPTE',
        'ETHIOPIA' => 'ETHIOPIE',
        'KENYA' => 'KENYA',
        'LIBYA' => 'LIBYE',
        'MALI' => 'MALI',
        'MAURITANIA' => 'MAURITANIE',
        'MOROCCO' => 'MAROC',
        'MORROCO' => 'MAROC',
        'MOZAMBIQUE' => 'MOZAMBIQUE',
        'NIGER' => 'NIGER',
        'NIGERIA' => 'NIGERIA',
        'RWANDA' => 'RWANDA',
        'SENEGAL' => 'SENEGAL',
        'SOMALIA' => 'SOMALIE',
        'SOUTH SUDAN' => 'SOUDAN DU SUD',
        'SUDAN' => 'SOUDAN',
        'TANZANIA' => 'TANZANIE',
        'TOGO' => 'TOGO',
        'TUNISIA' => 'TUNISIE',
        'UGANDA' => 'OUGANDA',
    ];

    /** Alias région EN → libellé région en base (contrôle de cohérence uniquement). */
    private const REGION_ALIASES = [
        'CENTRAL AFRICA' => 'AFRIQUE CENTRALE',
        'EAST AFRICA' => "AFRIQUE DE L'EST",
        'NORTH AFRICA' => 'AFRIQUE DU NORD',
        'SOUTHERN AFRICA' => 'AFRIQUE DU SUD',
        'WEST AFRICA' => "AFRIQUE DE L'OUEST",
    ];

    /** Cibles EN → libellé Cible FR (créées si absentes). */
    private const TARGET_ALIASES = [
        'CIVILIANS' => 'Civils',
        'MILITARY/SECURITY' => 'Forces de sécurité',
        'GOVERNEMENT' => 'Gouvernement',
        'GOVERNMENT' => 'Gouvernement',
        'INTERN ORGAN' => 'Organisation internationale',
        'PROPERTY' => 'Infrastructure',
        'CLASH BETWEEN TERRORIST GROUPS' => 'Affrontement entre groupes armés',
    ];

    /** Moyens d'attaque EN → libellé MoyenAttaque FR (créés si absents). */
    private const MEANS_ALIASES = [
        'SALW' => 'Armes légères',
        'IED' => 'IED',
        'SALW&IED' => 'Armes légères et IED',
        'KIDNAPPING' => 'Enlèvement',
    ];

    /** Groupes : normalisation des variantes de casse / libellés flous. */
    private const PERPETRATOR_ALIASES = [
        'ND' => 'Non identifié',
        'OTHERS GROUP' => 'Autres groupes',
        'VIOLENT EXTREMIST GROUP' => 'Violent Extremist Group',
        'VIOLENT EXTREMISTS' => 'Violent Extremist Group',
        'ISWAP' => 'ISWAP',
    ];

    private const DEFAULT_LABEL = 'Non renseigné';

    /** @var array<string, object> cache libellé (classe|UPPER) → entité référentiel */
    private array $refCache = [];

    /** @var array<string, int> référentiels créés pendant l'import (libellé → occurrences) */
    private array $createdRefs = [];

    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Chemin du fichier .xlsx (ex. C:\\Users\\...\\Data.xlsx)')
            ->addOption('sheet', null, InputOption::VALUE_REQUIRED, 'Nom de la feuille à lire', self::DEFAULT_SHEET)
            ->addOption('user', null, InputOption::VALUE_REQUIRED, 'E-mail du compte auteur des incidents (défaut : premier super admin)')
            ->addOption('pending', null, InputOption::VALUE_NONE, 'Importer en attente de validation au lieu de publié')
            ->addOption('deaths-mode', null, InputOption::VALUE_REQUIRED, "'total' (défaut : Total Deaths → total décès, détail à 0) ou 'civil' (tout en morts/blessés civils)", 'total')
            ->addOption('batch', null, InputOption::VALUE_REQUIRED, 'Taille des lots avant flush', (string) self::DEFAULT_BATCH)
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limiter le nombre de lignes importées (test)')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Valider sans enregistrer (transaction annulée à la fin)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Le classeur contient des feuilles de calcul lourdes (TCD, formules) :
        // PhpSpreadsheet dépasse les 128 Mo par défaut lors du chargement.
        if ((int) ini_get('memory_limit') !== -1) {
            ini_set('memory_limit', '1024M');
        }

        $io = new SymfonyStyle($input, $output);

        $file = (string) $input->getArgument('file');
        if (!is_file($file)) {
            $io->error('Fichier introuvable : '.$file);

            return Command::FAILURE;
        }

        $deathsMode = strtolower((string) $input->getOption('deaths-mode'));
        if (!in_array($deathsMode, ['total', 'civil'], true)) {
            $io->error("--deaths-mode doit être 'total' ou 'civil'.");

            return Command::FAILURE;
        }

        $user = $this->resolveUser($input->getOption('user') !== null ? (string) $input->getOption('user') : null);
        if ($user === null) {
            $io->error('Aucun utilisateur valide trouvé. Précisez --user=email@exemple.org (compte existant).');

            return Command::FAILURE;
        }

        $sheetName = (string) $input->getOption('sheet');
        $batchSize = max(1, (int) $input->getOption('batch'));
        $limit = $input->getOption('limit') !== null ? max(1, (int) $input->getOption('limit')) : null;
        $isPublished = $input->getOption('pending') ? null : true;
        $dryRun = (bool) $input->getOption('dry-run');

        $io->title('Import historique AUCTC — '.basename($file));
        $io->listing([
            'Feuille : '.$sheetName,
            'Auteur : '.$user->getEmail(),
            'Statut : '.($isPublished === true ? 'publié' : 'en attente de validation'),
            'Décès/blessés : mode '.$deathsMode,
            $dryRun ? 'DRY-RUN : rien ne sera enregistré' : 'Écriture réelle en base',
        ]);

        $reader = IOFactory::createReaderForFile($file);
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly([$sheetName]);
        $spreadsheet = $reader->load($file);
        $sheet = $spreadsheet->getSheetByName($sheetName);
        if ($sheet === null) {
            $io->error(sprintf('Feuille "%s" absente du fichier.', $sheetName));

            return Command::FAILURE;
        }

        $rows = $sheet->toArray(null, true, false, false);
        if (count($rows) < 2) {
            $io->error('La feuille ne contient pas de données.');

            return Command::FAILURE;
        }

        $columns = $this->mapHeaders(array_shift($rows));
        foreach (['date', 'country', 'perpetors', 'means', 'target'] as $required) {
            if (!isset($columns[$required])) {
                $io->error('Colonne introuvable dans les en-têtes : '.$required.' (attendu : Date, Country, Perpetors, Means of attack, Primary target)');

                return Command::FAILURE;
            }
        }

        $connection = $this->em->getConnection();
        $connection->beginTransaction();

        $success = 0;
        $errors = [];
        $regionMismatches = [];
        $rowNum = 1;

        try {
            foreach ($rows as $row) {
                ++$rowNum;

                if ($this->isEmptyRow($row, $columns)) {
                    continue;
                }

                if ($limit !== null && $success >= $limit) {
                    break;
                }

                try {
                    $incident = $this->mapRow($row, $columns, $user, $isPublished, $deathsMode, $regionMismatches, $rowNum);
                    $this->em->persist($incident);
                    ++$success;
                } catch (\Throwable $e) {
                    $errors[] = ['row' => $rowNum, 'message' => $e->getMessage()];
                }

                if ($success > 0 && $success % $batchSize === 0) {
                    $this->em->flush();
                }
                if ($rowNum % 1000 === 0) {
                    $io->text(sprintf('… %d lignes lues (%d importées, %d erreurs)', $rowNum - 1, $success, count($errors)));
                }
            }

            $this->em->flush();

            if ($dryRun) {
                $connection->rollBack();
            } else {
                $connection->commit();
            }
        } catch (\Throwable $e) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            $io->error('Import interrompu : '.$e->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf(
            '%d incident(s) importé(s), %d erreur(s).%s',
            $success,
            count($errors),
            $dryRun ? ' (DRY-RUN : transaction annulée)' : ''
        ));

        if ($this->createdRefs !== []) {
            $io->section('Référentiels créés pendant l\'import');
            $lines = [];
            foreach ($this->createdRefs as $label => $count) {
                $lines[] = sprintf('%s (utilisé %d fois)', $label, $count);
            }
            $io->listing($lines);
        }

        if ($regionMismatches !== []) {
            $io->section(sprintf('Incohérences région fichier vs pays (%d) — le pays fait foi', array_sum($regionMismatches)));
            $lines = [];
            foreach ($regionMismatches as $label => $count) {
                $lines[] = sprintf('%s : %d ligne(s)', $label, $count);
            }
            $io->listing($lines);
        }

        if ($errors !== []) {
            $io->section('Premières erreurs');
            foreach (array_slice($errors, 0, 20) as $error) {
                $io->text(sprintf('Ligne %d : %s', $error['row'], $error['message']));
            }
            if (count($errors) > 20) {
                $io->text(sprintf('… et %d autres.', count($errors) - 20));
            }

            $logPath = $this->writeErrorLog($errors);
            if ($logPath !== null) {
                $io->text('Détail complet : '.$logPath);
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @param list<mixed> $headerRow
     *
     * @return array<string, int>
     */
    private function mapHeaders(array $headerRow): array
    {
        $map = [];
        foreach ($headerRow as $index => $header) {
            $normalized = strtolower(trim((string) $header));
            $key = match ($normalized) {
                'date' => 'date',
                'year' => 'year',
                'month' => 'month',
                'region' => 'region',
                'country' => 'country',
                'city' => 'city',
                'perpetors', 'perpetrators' => 'perpetors',
                'means of attack' => 'means',
                'primary target' => 'target',
                'total deaths' => 'deaths',
                'total injured' => 'injured',
                default => null,
            };
            if ($key !== null && !isset($map[$key])) {
                $map[$key] = $index;
            }
        }

        return $map;
    }

    /**
     * @param list<mixed>        $row
     * @param array<string, int> $columns
     */
    private function isEmptyRow(array $row, array $columns): bool
    {
        $date = trim((string) ($row[$columns['date']] ?? ''));
        $country = trim((string) ($row[$columns['country']] ?? ''));

        return $date === '' && $country === '';
    }

    /**
     * @param list<mixed>          $row
     * @param array<string, int>   $columns
     * @param array<string, int>   $regionMismatches
     */
    private function mapRow(
        array $row,
        array $columns,
        User $user,
        ?bool $isPublished,
        string $deathsMode,
        array &$regionMismatches,
        int $rowNum,
    ): AllData {
        $get = static fn (string $key): string => trim((string) ($row[$columns[$key] ?? -1] ?? ''));

        $incident = new AllData();
        $incident->setUser($user);
        $incident->setIsPublished($isPublished);
        $incident->setDateAttaque($this->parseDate($row[$columns['date']] ?? null, $get('year'), $get('month')));
        $incident->setLocalite($get('city'));

        // Pays (obligatoire) via alias EN → FR
        $countryRaw = $get('country');
        if ($countryRaw === '') {
            throw new \InvalidArgumentException('Pays (colonne Country) manquant.');
        }
        $countryKey = mb_strtoupper($countryRaw);
        $countryLabel = self::COUNTRY_ALIASES[$countryKey] ?? $countryKey;
        $pays = $this->findRef(Pays::class, $countryLabel);
        if (!$pays instanceof Pays) {
            throw new \InvalidArgumentException(sprintf('Pays inconnu en base : "%s" (alias résolu : %s)', $countryRaw, $countryLabel));
        }
        $incident->setPays($pays);

        // Contrôle de cohérence région fichier vs région du pays (le pays fait foi)
        $regionRaw = mb_strtoupper($get('region'));
        if ($regionRaw !== '' && isset(self::REGION_ALIASES[$regionRaw])) {
            $expected = mb_strtoupper(self::REGION_ALIASES[$regionRaw]);
            $actual = mb_strtoupper((string) $pays->getRegion()?->getLibelle());
            if ($actual !== '' && $actual !== $expected) {
                $key = sprintf('%s : fichier "%s" ≠ base "%s"', $pays->getLibelle(), $get('region'), $pays->getRegion()?->getLibelle());
                $regionMismatches[$key] = ($regionMismatches[$key] ?? 0) + 1;
            }
        }

        // Groupe perpétrateur : alias + création si nouveau
        $perpRaw = $get('perpetors');
        $perpLabel = $perpRaw === ''
            ? 'Non identifié'
            : (self::PERPETRATOR_ALIASES[mb_strtoupper($perpRaw)] ?? $perpRaw);
        $incident->setPerpetrateur($this->ensureRef(Perpetrateurs::class, $perpLabel));

        // Moyen d'attaque : alias + création si nouveau
        $meansRaw = $get('means');
        $meansLabel = $meansRaw === ''
            ? self::DEFAULT_LABEL
            : (self::MEANS_ALIASES[mb_strtoupper($meansRaw)] ?? $meansRaw);
        $incident->setMoyenAttaque($this->ensureRef(MoyenAttaque::class, $meansLabel));

        // Cible : alias + création si nouveau
        $targetRaw = $get('target');
        $targetLabel = $targetRaw === ''
            ? self::DEFAULT_LABEL
            : (self::TARGET_ALIASES[mb_strtoupper($targetRaw)] ?? $targetRaw);
        $incident->setCible($this->ensureRef(Cible::class, $targetLabel));

        // Champs absents du fichier → défauts explicites
        $incident->setAttaque($this->ensureRef(Attaque::class, self::DEFAULT_LABEL));
        $incident->setEspace($this->ensureRef(Espace::class, self::DEFAULT_LABEL));
        $incident->setMaterielAttaque($this->ensureRef(MaterielAttaque::class, self::DEFAULT_LABEL));
        $incident->setMaterieaux($this->ensureRef(Materiaux::class, 'Aucun'));
        $incident->setDetails('Import historique AUCTC');
        $incident->setAutres('—');
        $incident->setRemarque(sprintf('Import Data.xlsx (ligne %d)', $rowNum));

        // Victimes : le fichier ne fournit que des totaux
        $deaths = $this->intVal($row[$columns['deaths'] ?? -1] ?? 0);
        $injured = $this->intVal($row[$columns['injured'] ?? -1] ?? 0);

        $incident->setMortCivil($deathsMode === 'civil' ? $deaths : 0);
        $incident->setMortSecuriteMilitaire(0);
        $incident->setMortTerroriste(0);
        $incident->setBlesseCivil($deathsMode === 'civil' ? $injured : 0);
        $incident->setBlesseSecuriteMilitaire(0);
        $incident->setBlesseTerroriste(0);
        $incident->setDisparuCivil(0);
        $incident->setDisparuSecuriteMilitaire(0);
        $incident->setDisparuTerroriste(0);
        $incident->setOtages(0);
        $incident->setLiberes(0);
        $incident->setTerroristeArretes(0);

        // Totaux : toujours ceux du fichier (la ventilation détaillée est inconnue)
        $incident->setTotalDeces($deaths);
        $incident->setTotalBlesses($injured);
        $incident->setTotalDisparus(0);

        return $incident;
    }

    private function parseDate(mixed $value, string $year, string $month): \DateTime
    {
        if (is_numeric($value) && (float) $value > 0) {
            return \DateTime::createFromInterface(ExcelDate::excelToDateTimeObject((float) $value));
        }

        $text = trim((string) $value);
        if ($text !== '') {
            $timestamp = strtotime($text);
            if ($timestamp !== false) {
                return (new \DateTime())->setTimestamp($timestamp);
            }
        }

        // Repli : Year + Month (mois FR ou EN) → 1er du mois
        if ($year !== '' && $month !== '') {
            $months = [
                'janvier' => 1, 'january' => 1, 'février' => 2, 'fevrier' => 2, 'february' => 2,
                'mars' => 3, 'march' => 3, 'avril' => 4, 'april' => 4, 'mai' => 5, 'may' => 5,
                'juin' => 6, 'june' => 6, 'juillet' => 7, 'july' => 7, 'août' => 8, 'aout' => 8, 'august' => 8,
                'septembre' => 9, 'september' => 9, 'octobre' => 10, 'october' => 10,
                'novembre' => 11, 'november' => 11, 'décembre' => 12, 'decembre' => 12, 'december' => 12,
            ];
            $monthNum = $months[mb_strtolower($month)] ?? null;
            if ($monthNum !== null && ctype_digit($year)) {
                return new \DateTime(sprintf('%04d-%02d-01', (int) $year, $monthNum));
            }
        }

        throw new \InvalidArgumentException('Date invalide ou manquante (colonne Date).');
    }

    private function intVal(mixed $value): int
    {
        return max(0, (int) $value);
    }

    private function findRef(string $entityClass, string $label): ?object
    {
        $key = $entityClass.'|'.mb_strtoupper($label);
        if (array_key_exists($key, $this->refCache)) {
            return $this->refCache[$key];
        }

        $entity = $this->em->getRepository($entityClass)
            ->createQueryBuilder('e')
            ->where('UPPER(e.libelle) = :libelle')
            ->setParameter('libelle', mb_strtoupper($label))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $this->refCache[$key] = $entity;

        return $entity;
    }

    private function ensureRef(string $entityClass, string $label): object
    {
        $label = trim($label);
        $existing = $this->findRef($entityClass, $label);
        if ($existing !== null) {
            if (isset($this->createdRefs[$this->refLabel($entityClass, $label)])) {
                ++$this->createdRefs[$this->refLabel($entityClass, $label)];
            }

            return $existing;
        }

        $entity = new $entityClass();
        $entity->setLibelle($label);
        $this->em->persist($entity);

        $this->refCache[$entityClass.'|'.mb_strtoupper($label)] = $entity;
        $this->createdRefs[$this->refLabel($entityClass, $label)] = 1;

        return $entity;
    }

    private function refLabel(string $entityClass, string $label): string
    {
        $short = substr((string) strrchr($entityClass, '\\'), 1);

        return $short.' « '.$label.' »';
    }

    private function resolveUser(?string $email): ?User
    {
        $repository = $this->em->getRepository(User::class);

        if ($email !== null && $email !== '') {
            return $repository->findOneBy(['email' => $email]);
        }

        foreach ($repository->findAll() as $user) {
            if (in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
                return $user;
            }
        }

        return null;
    }

    /**
     * @param list<array{row: int, message: string}> $errors
     */
    private function writeErrorLog(array $errors): ?string
    {
        $dir = \dirname(__DIR__, 2).'/var/log';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return null;
        }

        $path = $dir.'/import_data_xlsx_errors_'.date('Ymd_His').'.csv';
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            return null;
        }

        fputcsv($handle, ['ligne', 'erreur'], ';');
        foreach ($errors as $error) {
            fputcsv($handle, [$error['row'], $error['message']], ';');
        }
        fclose($handle);

        return $path;
    }
}
