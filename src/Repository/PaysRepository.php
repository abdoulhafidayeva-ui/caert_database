<?php

namespace App\Repository;

use App\Entity\Pays;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Pays>
 *
 * @method Pays|null find($id, $lockMode = null, $lockVersion = null)
 * @method Pays|null findOneBy(array $criteria, array $orderBy = null)
 * @method Pays[]    findAll()
 * @method Pays[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaysRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pays::class);
    }

    /**
     * @param string[] $regionLibelles Libellés de région (ex. « Centrale »). Vide = tous les pays.
     *
     * @return list<array{id: int, libelle: string}>
     */
    public function findForApiByRegionLibelles(array $regionLibelles): array
    {
        $regionLibelles = array_values(array_filter(array_map('trim', $regionLibelles)));

        $connection = $this->getEntityManager()->getConnection();
        if ($regionLibelles === []) {
            $ids = $connection->fetchFirstColumn(
                'SELECT MIN(id) FROM pays GROUP BY libelle ORDER BY libelle ASC'
            );
        } else {
            $ids = $connection->fetchFirstColumn(
                'SELECT MIN(p.id) FROM pays p INNER JOIN region r ON p.region_id = r.id WHERE r.libelle IN (?) GROUP BY p.libelle ORDER BY p.libelle ASC',
                [$regionLibelles],
                [\Doctrine\DBAL\ArrayParameterType::STRING]
            );
        }

        if ($ids === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('p')
            ->select('p.id', 'p.libelle')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', array_map('intval', $ids))
            ->orderBy('p.libelle', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'libelle' => (string) $row['libelle'],
        ], $rows);
    }

    /**
     * @return Pays[]
     */
    public function findUniqueByRegionLibelle(string $regionLibelle): array
    {
        $ids = $this->getEntityManager()->getConnection()->fetchFirstColumn(
            'SELECT MIN(p.id) FROM pays p INNER JOIN region r ON p.region_id = r.id WHERE r.libelle = :libelle GROUP BY p.libelle ORDER BY p.libelle ASC',
            ['libelle' => $regionLibelle]
        );

        if ($ids === []) {
            return [];
        }

        return $this->createQueryBuilder('p')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', array_map('intval', $ids))
            ->orderBy('p.libelle', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Pays[]
     */
    public function findAllUniqueByLibelle(): array
    {
        $ids = $this->getEntityManager()->getConnection()->fetchFirstColumn(
            'SELECT MIN(id) FROM pays GROUP BY libelle ORDER BY libelle ASC'
        );

        if ($ids === []) {
            return [];
        }

        return $this->createQueryBuilder('p')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', array_map('intval', $ids))
            ->orderBy('p.libelle', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findCanonicalByLibelleForRegion(?string $paysLibelle, string $regionLibelle): ?Pays
    {
        if ($paysLibelle === null || $paysLibelle === '') {
            return null;
        }

        foreach ($this->findUniqueByRegionLibelle($regionLibelle) as $pays) {
            if ($pays->getLibelle() === $paysLibelle) {
                return $pays;
            }
        }

        return null;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Pays $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Pays $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}
