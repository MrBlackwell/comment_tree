<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 *
 * @method Comment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comment[]    findAll()
 * @method Comment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function save(Comment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
    public function findCommentsLower3rdLevel(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.rang <= 3')
            ->orderBy('c.createdAt', 'ASC')
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    public function getCommentId3rdLevelWithReplies(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.parentId')
            ->andWhere('c.rang = 4')
            ->distinct()
            ->getQuery()
            ->getArrayResult();
    }

    public function findCommentsUpper3rdLevel(int $thirdLevelRoot): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.thirdLevelRoot = :val')
            ->setParameter('val', $thirdLevelRoot)
            ->orderBy('c.createdAt', 'ASC')
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }
}
