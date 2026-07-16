<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Liste utilisateurs : plus récents d'abord, avec recherche et filtres.
     *
     * @param array{q?: string, profil?: string, active?: string, verified?: string} $filters
     */
    public function createAdminListQueryBuilder(array $filters = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC')
            ->addOrderBy('u.id', 'DESC');

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(u.name)', ':q'),
                    $qb->expr()->like('LOWER(u.prenoms)', ':q'),
                    $qb->expr()->like('LOWER(u.email)', ':q'),
                    $qb->expr()->like('LOWER(u.organisation)', ':q')
                )
            )->setParameter('q', '%'.mb_strtolower($q).'%');
        }

        $profil = trim((string) ($filters['profil'] ?? ''));
        if ($profil !== '') {
            $qb->andWhere('u.profil = :profil')
                ->setParameter('profil', $profil);
        }

        $active = (string) ($filters['active'] ?? '');
        if ($active === '1') {
            $qb->andWhere('u.enable = true');
        } elseif ($active === '0') {
            $qb->andWhere('u.enable = false OR u.enable IS NULL');
        }

        $verified = (string) ($filters['verified'] ?? '');
        if ($verified === '1') {
            $qb->andWhere('u.isVerified = true');
        } elseif ($verified === '0') {
            $qb->andWhere('u.isVerified = false OR u.isVerified IS NULL');
        }

        return $qb;
    }

    public function findOneByEmail($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
