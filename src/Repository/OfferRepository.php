<?php

namespace App\Repository;

use App\Entity\Offer;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Offer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Offer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Offer[]    findAll()
 * @method Offer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OfferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Offer::class);
    }

    // /**
    //  * @return Offer[] Returns an array of Offer objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Offer
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */


    /**
     * @return \Doctrine\ORM\Query
     */
    public function getNoneOrderedOffersQuery(): \Doctrine\ORM\Query
    {
        $qb = $this->createQueryBuilder('o');
        $qb->innerJoin('o.product', 'p');
        $qb->innerJoin('o.source', 's');
        $qb->where('o.orderNumber IS NULL');
        $qb->addSelect('p.id AS productId');
        $qb->addSelect('s.id AS sourceId');
        $qb->orderBy('o.id', 'ASC');
        $qb->orderBy('p.id', 'ASC');
        return $qb->getQuery();
    }

    /**
     * @return Offer | null
     */
    public function findOneNoneOrderedOffer(): ?Offer
    {
        $qb = $this->createQueryBuilder('o');
        $qb->where('o.orderNumber IS NULL');
        $result = $qb->setMaxResults(1)->getQuery()->getResult();
        return $result[0] ?? null;
    }


    /**
     * @param Product $product
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findProductOffersCount(Product $product):int
    {
        $qb = $this->createQueryBuilder('o');
        $qb->where('o.product = :p');
        $qb->select('COUNT(o.id) AS offersCount');
        $qb->setParameter('p', $product);
        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Product $product
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findProductFreeSourceOffersCount(Product $product):int
    {
        $qb = $this->createQueryBuilder('o');
        $qb->where('o.product = :p');
        $qb->innerJoin('o.source', 's');
        $qb->andWhere('s.isPremium IS NULL OR s.isPremium = 0');
        $qb->select('COUNT(o.id) AS offersCount');
        $qb->setParameter('p', $product);
        return (int)$qb->getQuery()->getSingleScalarResult();
    }

}
