<?php

namespace App\Tests\Repository;

use App\Domain\Entity\Account;
use App\Domain\Entity\Transfer;
use App\Domain\Repository\TransferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class TransferRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private TransferRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel(['environment' => 'test', 'debug' => true]);

        $container = self::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->repository = $container->get(TransferRepository::class);

        $this->resetSchema();
    }

    protected function tearDown(): void
    {
        $this->entityManager->close();
        parent::tearDown();
    }

    public function testFindReturnsPersistedTransfer(): void
    {
        $fromAccount = new Account('acct-from', 'Alice', 50000, 'USD');
        $toAccount = new Account('acct-to', 'Bob', 10000, 'USD');

        $this->entityManager->persist($fromAccount);
        $this->entityManager->persist($toAccount);

        $transfer = new Transfer($fromAccount, $toAccount, 1250, 'usd');
        $this->entityManager->persist($transfer);
        $this->entityManager->flush();
        $transferId = $transfer->getId();

        $this->entityManager->clear();

        $found = $this->repository->find($transferId);

        $this->assertInstanceOf(Transfer::class, $found);
        $this->assertSame($transferId, $found->getId());
        $this->assertSame('acct-from', $found->getFromAccount()->getId());
        $this->assertSame('acct-to', $found->getToAccount()->getId());
        $this->assertSame(1250, $found->getAmountCents());
        $this->assertSame('USD', $found->getCurrency());
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
