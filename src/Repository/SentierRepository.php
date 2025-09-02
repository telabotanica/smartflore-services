<?php

namespace App\Repository;

use App\Entity\Sentier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sentier>
 *
 * @method Sentier|null find($id, $lockMode = null, $lockVersion = null)
 * @method Sentier|null findOneBy(array $criteria, array $orderBy = null)
 * @method Sentier[]    findAll()
 * @method Sentier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SentierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sentier::class);
    }

    public function add(Sentier $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Sentier $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByCriterias(array $criterias): array
    {
        $qb = $this->createQueryBuilder('s');
//dd($criterias);
        if (isset($criterias['status'])) {
            $qb->andWhere('s.status = :status')
                ->setParameter('status', $criterias['status']);
        }

        if (isset($criterias['show_deleted'])) {
            if ($criterias['show_deleted'] == false) {
                $qb->andWhere('s.date_suppression IS NULL');
            } elseif ($criterias['show_deleted'] == true) {
                $qb->andWhere('s.date_suppression IS NOT NULL');
            }
        }

        if (isset($criterias['nom']) && !empty($criterias['nom'])) {
            $qb->andWhere('s.nom LIKE :nom')
                ->setParameter('nom', '%' . $criterias['nom'] . '%');
        }

        if (isset($criterias['auteur_id']) && !empty($criterias['auteur_id'])) {
            $qb->andWhere('s.authorId = :auteur_id')
                ->setParameter('auteur_id', $criterias['auteur_id']);
        }

        if (isset($criterias['auteur']) && !empty($criterias['auteur'])) {
            $qb->andWhere(
                $qb->expr()->orX(
                    's.auteur LIKE :auteur',
                    's.auteur_email LIKE :auteur'
                )
            )
                ->setParameter('auteur', '%' . $criterias['auteur'] . '%');
        }

        if (isset($criterias['pmr'])) {
            $qb->andWhere('s.pmr = :pmr')
                ->setParameter('pmr', $criterias['pmr']);
        }

        if (!empty($criterias['ordre']) && in_array(strtoupper($criterias['ordre']), ['ASC', 'DESC'])) {
            $qb->orderBy('s.nom', strtoupper($criterias['ordre']));
        } else {
            $qb->orderBy('s.nom', 'ASC');
        }

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return Sentier[] Returns an array of Sentier objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Sentier
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
