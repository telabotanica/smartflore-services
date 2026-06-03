<?php

namespace App\Repository;

use App\Entity\Ping;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ping>
 *
 * @method Ping|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ping|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ping[]    findAll()
 * @method Ping[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ping::class);
    }

    public function save(Ping $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Ping $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findTodayPingByIpAndTrail(string $ip, int $trailId): ?Ping
    {
        $start = (new \DateTime())->setTime(0, 0, 0)->format('Y-m-d');

        return $this->createQueryBuilder('p')
            ->where('p.trail = :trail')
            ->andWhere('p.ip = :ip')
            ->andWhere('p.date LIKE :today')
            ->setParameter('trail', $trailId)
            ->setParameter('ip', $ip)
            ->setParameter('today', $start . '%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

//    /**
//     * @return Ping[] Returns an array of Ping objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Ping
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
