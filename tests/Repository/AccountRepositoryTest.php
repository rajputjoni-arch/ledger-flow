<?php

namespace App\Tests\Repository;

use App\Domain\Entity\Account;
use App\Domain\Entity\Transfer;
use App\Domain\Repository\AccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AccountRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private AccountRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel(['environment' => 'test', 'debug' => true]);

        $container = self::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->repository = $container->get(AccountRepository::class);

        $this->resetSchema();
    }

    protected function tearDown(): void
    {
        $this->entityManager->close();
        parent::tearDown();
    }

    public function testPersistAndFindById(): void
    {
        $account = new Account('acct-1', 'Alice', 15000, 'usd');

        $this->repository->persist($account);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $found = $this->repository->findById('acct-1');

        $this->assertInstanceOf(Account::class, $found);
        $this->assertSame('acct-1', $found->getId());
        $this->assertSame('Alice', $found->getOwner());
        $this->assertSame('USD', $found->getCurrency());
        $this->assertSame(15000, $found->getBalanceCents());
    }

    public function testFindForUpdateReturnsEntityWhenPresent(): void
    {
        $account = new Account('acct-2', 'Bob', 23000, 'USD');

        $this->repository->persist($account);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->entityManager->beginTransaction();

        try {
            $found = $this->repository->findForUpdate('acct-2');

            $this->assertInstanceOf(Account::class, $found);
            $this->assertSame('acct-2', $found->getId());
        } finally {
            $this->entityManager->rollback();
        }
    }

    public function testFindForUpdateReturnsNullWhenMissing(): void
    {
        $this->entityManager->beginTransaction();

        try {
            $found = $this->repository->findForUpdate('missing-account');

            $this->assertNull($found);
        } finally {
            $this->entityManager->rollback();
        }
    }

    private function resetSchema(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $connection->executeStatement('DROP TABLE IF EXISTS transfers');
        $connection->executeStatement('DROP TABLE IF EXISTS accounts');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = [
            $this->entityManager->getClassMetadata(Account::class),
            $this->entityManager->getClassMetadata(Transfer::class),
        ];

        $schemaTool->createSchema($metadata);
    }
}
