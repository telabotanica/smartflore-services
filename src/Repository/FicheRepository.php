<?php

namespace App\Repository;

use App\Entity\Fiche;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Fiche>
 *
 * @method Fiche|null find($id, $lockMode = null, $lockVersion = null)
 * @method Fiche|null findOneBy(array $criteria, array $orderBy = null)
 * @method Fiche[]    findAll()
 * @method Fiche[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FicheRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Fiche::class);
    }

    public function add(Fiche $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Fiche $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllPaginated($filtres)
    {
        $qb = $this->createQueryBuilder('f')
        ->select('f');

        $qb->andWhere('f.derniere_version = 1')
        ->andWhere("f.nt IS NOT NULL");

        $qb = $this->addFiltersToQuery($qb, $filtres);

        $debut = $filtres['debut'];
        if ($filtres['referentiel'] != '%' && $filtres['num_tax'] != '%') {
            $debut = 0;
        }

        $qb->setMaxResults($filtres['limite']);
//        $qb->setFirstResult($filtres['debut']);
        $qb->setFirstResult($debut);

        $qb->orderBy('f.tag', 'ASC');
//dd($qb -> getQuery()->getSQL());
        return $qb->getQuery()->getResult();
    }

    private function addFiltersToQuery($qb, $filters)
    {
        foreach ($filters as $key => $value) {
            if ($key == 'pages_existantes'){
                $qb->andWhere('f.tag LIKE :tag')
                ->setParameter('tag', '%SmartFlore%');
            }

            if ($key == 'referentiel' && $value != '%') {
                $qb->andWhere('f.referentiel = :referentiel')
                    ->setParameter('referentiel', $value);
            }

            if ($key == 'num_tax' && $value != '%') {
                $qb->andWhere('f.nt = :nt')
                    ->setParameter('nt', $value);
            }
        }

        return $qb;
    }

//    /**
//     * @return Fiche[] Returns an array of Fiche objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('f.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Fiche
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
