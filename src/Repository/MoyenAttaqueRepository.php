<?php

namespace App\Repository;

use App\Entity\MoyenAttaque;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MoyenAttaque>
 *
 * @method MoyenAttaque|null find($id, $lockMode = null, $lockVersion = null)
 * @method MoyenAttaque|null findOneBy(array $criteria, array $orderBy = null)
 * @method MoyenAttaque[]    findAll()
 * @method MoyenAttaque[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MoyenAttaqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MoyenAttaque::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(MoyenAttaque $entity, bool $flush = true): void
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
    public function remove(MoyenAttaque $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return MoyenAttaque[] Returns an array of MoyenAttaque objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?MoyenAttaque
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
