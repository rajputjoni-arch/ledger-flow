<?php

namespace App\Tests\Controller;

use App\Domain\Entity\Account;
use App\Domain\Entity\Transfer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TransferControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private string $apiToken;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->apiToken = (string) $container->getParameter('app.api_token');

        $this->resetSchema();
        $this->seedAccounts();
    }

    protected function tearDown(): void
    {
        $this->entityManager->close();
        parent::tearDown();
    }

    public function testRequiresApiKey(): void
    {
        $this->client->request('POST', '/api/v1/transfer', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'fromAccountId' => 'acct-1',
            'toAccountId' => 'acct-2',
            'amount' => 10,
            'currency' => 'USD',
        ], JSON_THROW_ON_ERROR));

        $this->assertResponseStatusCodeSame(401);

        $data = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Unauthorized request. Provide a valid X-Api-Key header.', $data['error']);
    }

    public function testRequiresIdempotencyKey(): void
    {
        $this->client->request('POST', '/api/v1/transfer', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Api-Key' => $this->apiToken,
        ], json_encode([
            'fromAccountId' => 'acct-1',
            'toAccountId' => 'acct-2',
            'amount' => 10,
            'currency' => 'USD',
        ], JSON_THROW_ON_ERROR));

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('The X-Idempotency-Key header is required.', $data['error']);
        $this->assertArrayHasKey('details', $data);
    }

    public function testInvalidTransferPayloadReturnsValidationErrors(): void
    {
        $this->client->request('POST', '/api/v1/transfer', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Api-Key' => $this->apiToken,
        ], json_encode([
            'fromAccountId' => '',
            'toAccountId' => '',
            'amount' => 0,
            'currency' => '',
        ], JSON_THROW_ON_ERROR));

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('errors', $data);
        $this->assertCount(4, $data['errors']);
        $this->assertStringContainsString('fromAccountId', $data['errors'][0]);
    }

    public function testTransferSucceedsWithValidApiKey(): void
    {
        $this->client->request('POST', '/api/v1/transfer', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Api-Key' => $this->apiToken,
            'HTTP_X-Idempotency-Key' => 'transfer-success-key',
        ], json_encode([
            'fromAccountId' => 'acct-1',
            'toAccountId' => 'acct-2',
            'amount' => 10,
            'currency' => 'USD',
        ], JSON_THROW_ON_ERROR));

        $this->assertResponseStatusCodeSame(200);

        $data = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('success', $data['status']);
        $this->assertArrayHasKey('transfer', $data);
        $this->assertSame('acct-1', $data['transfer']['fromAccount']['id']);
        $this->assertSame('acct-2', $data['transfer']['toAccount']['id']);
        $this->assertSame('90.00', $data['transfer']['fromAccount']['balance']);
        $this->assertSame('60.00', $data['transfer']['toAccount']['balance']);
    }

    public function testIdempotencyReplaysSameResponseWithoutDoubleTransfer(): void
    {
        $idempotencyKey = 'transfer-idempotency-'.bin2hex(random_bytes(8));

        $payload = json_encode([
            'fromAccountId' => 'acct-1',
            'toAccountId' => 'acct-2',
            'amount' => 7.5,
            'currency' => 'USD',
        ], JSON_THROW_ON_ERROR);

        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Api-Key' => $this->apiToken,
            'HTTP_X-Idempotency-Key' => $idempotencyKey,
        ];

        $this->client->request('POST', '/api/v1/transfer', [], [], $server, $payload);
        $this->assertResponseStatusCodeSame(200);
        $firstResponse = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->client->request('POST', '/api/v1/transfer', [], [], $server, $payload);
        $this->assertResponseStatusCodeSame(200);
        $secondResponse = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame($firstResponse, $secondResponse);
        $this->assertSame(
            $firstResponse['transfer']['transactionId'],
            $secondResponse['transfer']['transactionId']
        );

        $transferCount = (int) $this->entityManager
            ->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from(Transfer::class, 't')
            ->getQuery()
            ->getSingleScalarResult();

        $this->assertSame(1, $transferCount);
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

    private function seedAccounts(): void
    {
        $this->entityManager->persist(new Account('acct-1', 'Alice', 10000, 'USD'));
        $this->entityManager->persist(new Account('acct-2', 'Bob', 5000, 'USD'));
        $this->entityManager->flush();
        $this->entityManager->clear();
    }
}
