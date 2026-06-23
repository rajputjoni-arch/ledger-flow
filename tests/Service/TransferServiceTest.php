<?php

namespace App\Tests\Service;

use App\Application\Service\TransferService;
use App\Domain\Entity\Account;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class TransferServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private TransferService $service;

    protected function setUp(): void
    {
        self::bootKernel(['environment' => 'test', 'debug' => true]);

        $container = self::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->service = $container->get(TransferService::class);

        $this->resetSchema();
    }

    protected function tearDown(): void
    {
        $this->entityManager->close();
        parent::tearDown();
    }

    public function testTransferRejectsSameSourceAndDestinationAccount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source and destination accounts must differ.');

        $this->service->transfer('acct-1', 'acct-1', 10.0, 'USD');
    }

    public function testTransferRejectsNonPositiveAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Transfer amount must be greater than zero.');

        $this->service->transfer('acct-1', 'acct-2', 0.0, 'USD');
    }

    public function testTransferThrowsWhenAccountIsMissing(): void
    {
        $fromAccount = new Account('acct-1', 'Alice', 10000, 'USD');
        $this->entityManager->persist($fromAccount);
        $this->entityManager->flush();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('One or more accounts were not found.');

        $this->service->transfer('acct-1', 'acct-2', 5.0, 'USD');
    }

    public function testTransferPersistsAndReturnsTransferDetailsOnSuccess(): void
    {
        $fromAccount = new Account('acct-1', 'Alice', 10000, 'USD');
        $toAccount = new Account('acct-2', 'Bob', 5000, 'USD');

        $this->entityManager->persist($fromAccount);
        $this->entityManager->persist($toAccount);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $result = $this->service->transfer('acct-1', 'acct-2', 25.50, 'usd');

        $this->assertNotEmpty($result['transactionId']);
        $this->assertSame('USD', $result['currency']);
        $this->assertSame('25.50', $result['amount']);
        $this->assertSame('74.50', $result['fromAccount']['balance']);
        $this->assertSame('75.50', $result['toAccount']['balance']);
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
            $this->entityManager->getClassMetadata(\App\Domain\Entity\Account::class),
            $this->entityManager->getClassMetadata(\App\Domain\Entity\Transfer::class),
        ];

        $schemaTool->createSchema($metadata);
    }
}
