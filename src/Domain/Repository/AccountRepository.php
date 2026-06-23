<?php

namespace App\Domain\Repository;

use App\Domain\Entity\Account;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

final class AccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Account::class);
    }

    public function findForUpdate(string $accountId): ?Account
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.id = :id')
            ->setParameter('id', $accountId)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();
    }

    public function findById(string $accountId): ?Account
    {
        return $this->find($accountId);
    }

    public function persist(Account $account): void
    {
        $this->getEntityManager()->persist($account);
    }
}
