<?php

namespace App\Repository;

use App\Entity\Occurrence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Occurrence>
 *
 * @method Occurrence|null find($id, $lockMode = null, $lockVersion = null)
 * @method Occurrence|null findOneBy(array $criteria, array $orderBy = null)
 * @method Occurrence[]    findAll()
 * @method Occurrence[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OccurrenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Occurrence::class);
    }

    public function add(Occurrence $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Occurrence $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByTaxon(string $taxonRepository, int $taxonId): array
    {
        $occurrences = $this->findAll();

        return array_filter(
            $occurrences,
            static function (Occurrence $occurrence) use ($taxonRepository, $taxonId) {
                $taxon = $occurrence->getTaxon();

                return isset(
                        $taxon['taxon_repository'],
                        $taxon['taxonomic_id']
                    )
                    && $taxon['taxon_repository'] === $taxonRepository
                    && (int) $taxon['taxonomic_id'] === $taxonId;
            }
        );
    }

//    /**
//     * @return Occurrence[] Returns an array of Occurrence objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('o.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Occurrence
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
