<?php

namespace App\Repository;

use App\Entity\AppParam;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AppParam|null find($id, $lockMode = null, $lockVersion = null)
 * @method AppParam|null findOneBy(array $criteria, array $orderBy = null)
 * @method AppParam[]    findAll()
 * @method AppParam[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AppParamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AppParam::class);
    }

    // /**
    //  * @return AppParam[] Returns an array of AppParam objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AppParam
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
